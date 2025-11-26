<?php
/**
 * Checkout Process
 * Memproses pemesanan, menyimpan ke database, dan mengirim notifikasi WhatsApp
 */

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/fonnte.php'; // Include konfigurasi Fonnte

$db = getDB();
$cart = getCart();

// Validasi cart tidak kosong
if (empty($cart)) {
    setFlash('error', 'Keranjang belanja kosong!');
    header("Location: cart.php");
    exit();
}

// Validasi POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}

// Ambil data dari form
$nama = escape($_POST['nama']);
$email = escape($_POST['email'] ?? '');
$telepon = escape($_POST['telepon']);
$alamat = escape($_POST['alamat']);
$catatan = escape($_POST['catatan'] ?? '');
$metode_pembayaran = escape($_POST['metode_pembayaran']);

// Ambil data kupon jika ada
$kupon_id = !empty($_POST['kupon_id']) ? intval($_POST['kupon_id']) : null;
$nilai_diskon = !empty($_POST['nilai_diskon']) ? intval($_POST['nilai_diskon']) : 0;
$kode_kupon = null;

// Jika ada kupon, ambil kode kuponnya
if ($kupon_id) {
    $query_kupon = "SELECT kode_kupon FROM kupon WHERE id = $kupon_id";
    $result_kupon = $db->query($query_kupon);
    if ($result_kupon && $result_kupon->num_rows > 0) {
        $row_kupon = $result_kupon->fetch_assoc();
        $kode_kupon = $row_kupon['kode_kupon'];
    }
}

// Validasi input
if (empty($nama) || empty($telepon) || empty($alamat)) {
    setFlash('error', 'Data tidak lengkap! Nama, telepon, dan alamat wajib diisi.');
    header("Location: checkout.php");
    exit();
}

// Generate kode pesanan unik
$kode_pesanan = 'ORD' . date('Ymd') . rand(1000, 9999);

// Cek apakah kode sudah ada (untuk memastikan unique)
$check_query = "SELECT id FROM pesanan WHERE kode_pesanan = '$kode_pesanan'";
while ($db->query($check_query)->num_rows > 0) {
    $kode_pesanan = 'ORD' . date('Ymd') . rand(1000, 9999);
}

// Hitung total
$subtotal = getCartTotal();
$total_setelah_diskon = $subtotal - $nilai_diskon;

// User ID (jika login)
$user_id = isLoggedIn() ? $_SESSION['user_id'] : 'NULL';

// Begin transaction
$db->begin_transaction();

try {
    // 1. Insert pesanan
    $kupon_id_sql = $kupon_id ? $kupon_id : 'NULL';
    $kode_kupon_sql = $kode_kupon ? "'$kode_kupon'" : 'NULL';
    
    $query_pesanan = "INSERT INTO pesanan (
        user_id, 
        kode_pesanan, 
        nama_customer, 
        email_customer, 
        telepon_customer, 
        alamat_pengiriman, 
        total_harga,
        kupon_id,
        kode_kupon,
        nilai_diskon,
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
        $total_setelah_diskon,
        $kupon_id_sql,
        $kode_kupon_sql,
        $nilai_diskon,
        '$metode_pembayaran',
        'Pending',
        '$catatan'
    )";
    
    if (!$db->query($query_pesanan)) {
        throw new Exception('Gagal menyimpan pesanan: ' . $db->error);
    }
    
    $pesanan_id = $db->insert_id;
    
    // 2. Insert detail pesanan dan update stok
    foreach ($cart as $produk_id => $item) {
        $nama_produk = escape($item['nama']);
        $harga = $item['harga'];
        $jumlah = $item['jumlah'];
        $subtotal = $harga * $jumlah;
        
        // Insert detail pesanan
        $query_detail = "INSERT INTO detail_pesanan (
            pesanan_id, 
            produk_id, 
            nama_produk, 
            harga, 
            jumlah, 
            subtotal
        ) VALUES (
            $pesanan_id,
            $produk_id,
            '$nama_produk',
            $harga,
            $jumlah,
            $subtotal
        )";
        
        if (!$db->query($query_detail)) {
            throw new Exception('Gagal menyimpan detail pesanan: ' . $db->error);
        }
        
        // Update stok produk
        $query_stok = "UPDATE produk 
                       SET stok = stok - $jumlah 
                       WHERE id = $produk_id 
                       AND stok >= $jumlah";
        
        if (!$db->query($query_stok)) {
            throw new Exception('Gagal update stok produk: ' . $db->error);
        }
        
        // Cek apakah stok berhasil dikurangi
        if ($db->affected_rows === 0) {
            throw new Exception('Stok produk ' . $item['nama'] . ' tidak mencukupi!');
        }
    }
    
    // 3. Update penggunaan kupon jika ada
    if ($kupon_id) {
        $query_update_kupon = "UPDATE kupon SET kuota_terpakai = kuota_terpakai + 1 WHERE id = $kupon_id";
        $db->query($query_update_kupon);
    }
    
    // 4. Insert ke keuangan (pemasukan otomatis)
    $query_keuangan = "INSERT INTO keuangan (
        jenis, 
        kategori, 
        deskripsi, 
        jumlah, 
        tanggal, 
        pesanan_id
    ) VALUES (
        'Pemasukan',
        'Penjualan ATK',
        'Penjualan produk - Order $kode_pesanan',
        $total_setelah_diskon,
        CURRENT_DATE(),
        $pesanan_id
    )";
    
    if (!$db->query($query_keuangan)) {
        throw new Exception('Gagal mencatat keuangan: ' . $db->error);
    }
    
    // Commit transaction
    $db->commit();
    
    // ========================================
    // KIRIM NOTIFIKASI WHATSAPP
    // ========================================
    try {
        // Siapkan data items untuk pesan
        $items = [];
        foreach ($cart as $item) {
            $items[] = [
                'nama' => $item['nama'],
                'jumlah' => $item['jumlah'],
                'harga' => $item['harga'],
                'subtotal' => $item['harga'] * $item['jumlah']
            ];
        }
        
        // Data pesanan untuk notifikasi
        $orderData = [
            'no_pesanan' => $kode_pesanan,
            'tanggal' => date('d/m/Y H:i'),
            'status' => 'Pending',
            'nama' => $nama,
            'telepon' => $telepon,
            'alamat' => $alamat,
            'catatan' => $catatan,
            'items' => $items,
            'subtotal' => $subtotal,
            'diskon' => $nilai_diskon,
            'total' => $total_setelah_diskon,
            'metode_pembayaran' => $metode_pembayaran
        ];
        
        // Generate pesan WhatsApp
        $message = generatePesanNotifikasiPesanan($orderData);
        
        // Format nomor WhatsApp
        $whatsappNumber = formatWhatsAppNumber($telepon);
        
        // Kirim notifikasi WhatsApp
        $waResult = kirimWhatsApp($whatsappNumber, $message);
        
        // Log hasil pengiriman (optional)
        if ($waResult['success']) {
            // Berhasil kirim WA
            $log_status = 'sent';
            $log_response = isset($waResult['response']) ? json_encode($waResult['response']) : 'Success';
        } else {
            // Gagal kirim WA (tapi pesanan tetap tersimpan)
            $log_status = 'failed';
            $log_response = isset($waResult['error']) ? $waResult['error'] : 'Unknown error';
        }
        
        // Simpan log notifikasi ke database (optional)
        $query_log = "INSERT INTO notifikasi_log (
            pesanan_id, 
            tipe, 
            nomor_tujuan, 
            status, 
            response, 
            tanggal_kirim
        ) VALUES (
            $pesanan_id,
            'whatsapp',
            '$whatsappNumber',
            '$log_status',
            '" . escape($log_response) . "',
            NOW()
        )";
        
        $db->query($query_log); // Tidak perlu throw error jika log gagal
        
    } catch (Exception $e) {
        // Jika gagal kirim WA, pesanan tetap berhasil
        // Hanya log error saja
        error_log("Gagal kirim WhatsApp untuk pesanan $kode_pesanan: " . $e->getMessage());
    }
    
    // Clear cart
    clearCart();
    
    // Set success message
    $success_msg = 'Pesanan berhasil dibuat! Kode pesanan: ' . $kode_pesanan;
    if ($nilai_diskon > 0) {
        $success_msg .= ' (Hemat Rp ' . number_format($nilai_diskon, 0, ',', '.') . ' dengan kupon!)';
    }
    
    // Tambah info WA jika berhasil terkirim
    if (isset($waResult) && $waResult['success']) {
        $success_msg .= ' Notifikasi telah dikirim ke WhatsApp Anda.';
    }
    
    setFlash('success', $success_msg);
    
    // Redirect ke invoice
    header("Location: invoice.php?kode=$kode_pesanan");
    exit();
    
} catch (Exception $e) {
    // Rollback jika ada error
    $db->rollback();
    
    // Set error message
    setFlash('error', 'Terjadi kesalahan: ' . $e->getMessage());
    header("Location: checkout.php");
    exit();
}
?>