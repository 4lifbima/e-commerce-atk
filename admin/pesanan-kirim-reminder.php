<?php
/**
 * Admin - Kirim Reminder Pembayaran via WhatsApp
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

// Validasi input
if (empty($kode_pesanan)) {
    echo json_encode([
        'success' => false,
        'message' => 'Kode pesanan tidak valid'
    ]);
    exit();
}

try {
    // Kirim reminder pembayaran
    $result = kirimReminderPembayaran($kode_pesanan);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>