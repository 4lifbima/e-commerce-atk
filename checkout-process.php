<?php
/**
 * Checkout Process
 * Memproses pemesanan dan menyimpan ke database
 */

require_once 'config/database.php';
require_once 'config/session.php';

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
$total_harga = getCartTotal();

// User ID (jika login)
$user_id = isLoggedIn() ? $_SESSION['user_id'] : 'NULL';

// Begin transaction
$db->begin_transaction();

try {
    // 1. Insert pesanan
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
        $total_harga,
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
        'Penjualan ATK',
        'Penjualan produk - Order $kode_pesanan',
        $total_harga,
        CURRENT_DATE(),
        $pesanan_id
    )";
    
    if (!$db->query($query_keuangan)) {
        throw new Exception('Gagal mencatat keuangan: ' . $db->error);
    }
    
    // Commit transaction
    $db->commit();
    
    // Clear cart
    clearCart();
    
    // Set success message
    setFlash('success', 'Pesanan berhasil dibuat! Kode pesanan: ' . $kode_pesanan);
    
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