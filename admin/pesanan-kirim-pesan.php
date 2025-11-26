<?php
/**
 * Admin - Kirim Pesan Custom via WhatsApp
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once 'fonnte.php';

requireAdmin();

header('Content-Type: application/json');

// Validasi POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

$kode_pesanan = isset($_POST['kode_pesanan']) ? escape($_POST['kode_pesanan']) : '';
$pesan = isset($_POST['pesan']) ? trim($_POST['pesan']) : '';

// Validasi input
if (empty($kode_pesanan) || empty($pesan)) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak lengkap'
    ]);
    exit();
}

try {
    // Kirim pesan custom
    $result = kirimPesanCustom($kode_pesanan, $pesan);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>