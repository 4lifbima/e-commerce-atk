<?php
/**
 * Process POS Transaction
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireAdmin();

$db = getDB();

// Get POST data
$nama_customer = escape($_POST['nama_customer'] ?? '');
$telepon_customer = escape($_POST['telepon_customer'] ?? '');
$metode_pembayaran = escape($_POST['metode_pembayaran'] ?? 'Tunai');
$kode_kupon = escape($_POST['kode_kupon'] ?? '');
$cart_data = json_decode($_POST['cart_data'] ?? '[]', true);
$total_harga = floatval($_POST['total_harga'] ?? 0);
$nilai_diskon = floatval($_POST['nilai_diskon'] ?? 0);

// Validation
if (empty($nama_customer) || empty($cart_data) || count($cart_data) === 0) {
    setFlash('error', 'Data tidak lengkap!');
    header('Location: pos.php');
    exit;
}

// Generate kode pesanan
$kode_pesanan = 'POS' . date('YmdHis') . rand(1000, 9999);

// Get kupon data if exists
$kupon_id = null;
if (!empty($kode_kupon)) {
    $query_kupon = "SELECT id FROM kupon WHERE kode_kupon = '$kode_kupon' AND status_aktif = 1 LIMIT 1";
    $result_kupon = $db->query($query_kupon);
    if ($result_kupon && $result_kupon->num_rows > 0) {
        $kupon_id = $result_kupon->fetch_assoc()['id'];
    }
}

// Begin transaction
$db->begin_transaction();

try {
    // 1. Insert pesanan (tipe_transaksi = 'Offline')
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
        tipe_transaksi,
        catatan
    ) VALUES (
        NULL,
        '$kode_pesanan',
        '$nama_customer',
        NULL,
        '$telepon_customer',
        'Pembelian Langsung di Toko',
        $total_harga,
        $kupon_id_sql,
        $kode_kupon_sql,
        $nilai_diskon,
        '$metode_pembayaran',
        'Selesai',
        'Offline',
        'Transaksi POS - Pelanggan datang langsung'
    )";
    
    if (!$db->query($query_pesanan)) {
        throw new Exception('Gagal menyimpan pesanan: ' . $db->error);
    }
    
    $pesanan_id = $db->insert_id;
    
    // 2. Insert detail pesanan dan update stok
    foreach ($cart_data as $item) {
        $produk_id = intval($item['id']);
        $nama_produk = escape($item['nama']);
        $harga = floatval($item['harga']);
        $jumlah = intval($item['jumlah']);
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
        'Penjualan ATK (POS)',
        'Penjualan produk POS - Order $kode_pesanan',
        $total_harga,
        CURRENT_DATE(),
        $pesanan_id
    )";
    
    if (!$db->query($query_keuangan)) {
        throw new Exception('Gagal menyimpan ke keuangan: ' . $db->error);
    }
    
    // Commit transaction
    $db->commit();
    
    setFlash('success', "Transaksi berhasil! Kode Pesanan: $kode_pesanan");
    header('Location: pos.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction
    $db->rollback();
    setFlash('error', $e->getMessage());
    header('Location: pos.php');
    exit;
}

