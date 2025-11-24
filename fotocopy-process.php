<?php
/**
 * Fotocopy Order Processing
 * Memproses pemesanan fotocopy dan menyimpan ke database
 */

require_once 'config/database.php';
require_once 'config/session.php';

$db = getDB();

// Validasi POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: fotocopy.php");
    exit();
}

// Ambil data dari form
$nama = escape($_POST['nama']);
$email = escape($_POST['email'] ?? '');
$telepon = escape($_POST['telepon']);
$alamat = escape($_POST['alamat']);
$jumlah_lembar = (int)$_POST['jumlah_lembar'];
$jenis_kertas = escape($_POST['jenis_kertas']);
$warna = escape($_POST['warna']);
$bolak_balik = (int)$_POST['bolak_balik'];
$jilid = escape($_POST['jilid']);
$keterangan = escape($_POST['keterangan'] ?? '');
$metode_pembayaran = escape($_POST['metode_pembayaran']);

// Validasi input
if (empty($nama) || empty($telepon) || empty($alamat) || $jumlah_lembar < 1) {
    setFlash('error', 'Data tidak lengkap! Pastikan semua field wajib terisi.');
    header("Location: fotocopy.php");
    exit();
}

// Get harga fotocopy berdasarkan spesifikasi
$key = $jenis_kertas . '_' . $warna . '_' . $bolak_balik;
$query_harga = "SELECT harga FROM harga_fotocopy 
                WHERE jenis_kertas = '$jenis_kertas' 
                AND warna = '$warna' 
                AND bolak_balik = $bolak_balik";

$result_harga = $db->query($query_harga);

if ($result_harga->num_rows === 0) {
    setFlash('error', 'Spesifikasi fotocopy tidak valid!');
    header("Location: fotocopy.php");
    exit();
}

$harga_row = $result_harga->fetch_assoc();
$harga_per_lembar = $harga_row['harga'];
$biaya_fotocopy = $jumlah_lembar * $harga_per_lembar;

// Get harga jilid
$biaya_jilid = 0;
if ($jilid !== 'Tidak') {
    $query_jilid = "SELECT harga FROM harga_jilid 
                    WHERE jenis_jilid = '$jilid'";
    $result_jilid = $db->query($query_jilid);
    
    if ($result_jilid->num_rows > 0) {
        $jilid_row = $result_jilid->fetch_assoc();
        $biaya_jilid = $jilid_row['harga'];
    }
}

// Total biaya fotocopy
$subtotal = $biaya_fotocopy + $biaya_jilid;

// Generate kode pesanan unik
$kode_pesanan = 'FOTOCOPY-' . date('Ymd') . '-' . rand(10000, 99999);

// Cek apakah kode sudah ada
$check_query = "SELECT id FROM pesanan WHERE kode_pesanan = '$kode_pesanan'";
while ($db->query($check_query)->num_rows > 0) {
    $kode_pesanan = 'FOTOCOPY-' . date('Ymd') . '-' . rand(10000, 99999);
}

// User ID (jika login)
$user_id = isLoggedIn() ? $_SESSION['user_id'] : 'NULL';

// Handle file upload
$file_dokumen = null;
if (isset($_FILES['file_dokumen']) && $_FILES['file_dokumen']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['file_dokumen'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    // Validasi ukuran file
    if ($file['size'] > $max_size) {
        setFlash('error', 'Ukuran file tidak boleh lebih dari 10MB!');
        header("Location: fotocopy.php");
        exit();
    }
    
    // Validasi tipe file
    $allowed_types = ['application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        setFlash('error', 'File harus berformat PDF!');
        header("Location: fotocopy.php");
        exit();
    }
    
    // Buat folder uploads jika belum ada
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }
    
    // Generate nama file
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_dokumen = 'fotocopy_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
    $file_path = 'uploads/' . $file_dokumen;
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        setFlash('error', 'Gagal upload file!');
        header("Location: fotocopy.php");
        exit();
    }
}

// Begin transaction
$db->begin_transaction();

try {
    // 1. Insert pesanan (induk)
    $query_pesanan = "INSERT INTO pesanan (
        user_id, 
        kode_pesanan, 
        nama_customer, 
        email_customer, 
        telepon_customer, 
        alamat_pengiriman, 
        total_harga, 
        metode_pembayaran, 
        status, 
        catatan
    ) VALUES (
        $user_id,
        '$kode_pesanan',
        '$nama',
        '$email',
        '$telepon',
        '$alamat',
        $subtotal,
        '$metode_pembayaran',
        'Pending',
        'Pesanan Fotocopy'
    )";
    
    if (!$db->query($query_pesanan)) {
        throw new Exception('Gagal menyimpan pesanan: ' . $db->error);
    }
    
    $pesanan_id = $db->insert_id;
    
    // 2. Insert pesanan fotocopy detail
    $query_fotocopy = "INSERT INTO pesanan_fotocopy (
        pesanan_id,
        jumlah_lembar,
        jenis_kertas,
        warna,
        bolak_balik,
        harga_per_lembar,
        jilid,
        biaya_jilid,
        subtotal,
        keterangan_tambahan
    ) VALUES (
        $pesanan_id,
        $jumlah_lembar,
        '$jenis_kertas',
        '$warna',
        $bolak_balik,
        $harga_per_lembar,
        '$jilid',
        $biaya_jilid,
        $subtotal,
        '$keterangan'
    )";
    
    if (!$db->query($query_fotocopy)) {
        throw new Exception('Gagal menyimpan detail fotocopy: ' . $db->error);
    }
    
    // 3. Insert ke keuangan (pemasukan otomatis)
    $query_keuangan = "INSERT INTO keuangan (
        jenis, 
        kategori, 
        deskripsi, 
        jumlah, 
        tanggal, 
        pesanan_id
    ) VALUES (
        'Pemasukan',
        'Fotocopy',
        'Pesanan fotocopy - $kode_pesanan',
        $subtotal,
        CURRENT_DATE(),
        $pesanan_id
    )";
    
    if (!$db->query($query_keuangan)) {
        throw new Exception('Gagal menyimpan ke keuangan: ' . $db->error);
    }
    
    // Commit transaction
    $db->commit();
    
    // Set flash message
    setFlash('success', 'Pesanan fotocopy berhasil dibuat! Silakan lanjut ke invoice.');
    
    // Redirect ke invoice
    header("Location: invoice.php?kode=$kode_pesanan");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    $db->rollback();
    
    // Hapus file jika ada
    if ($file_dokumen && file_exists('uploads/' . $file_dokumen)) {
        unlink('uploads/' . $file_dokumen);
    }
    
    setFlash('error', $e->getMessage());
    header("Location: fotocopy.php");
    exit();
}

$db->close();
?>
