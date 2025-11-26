<?php
/**
 * Admin - Update Status Pesanan
 * Dengan integrasi notifikasi WhatsApp otomatis
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once 'fonnte.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $pesanan_id = (int)$_POST['id'];
    $status_baru = escape($_POST['status']);
    
    $db = getDB();
    
    // Ambil data pesanan lama (untuk cek status lama)
    $query_check = "SELECT * FROM pesanan WHERE id = $pesanan_id";
    $result_check = $db->query($query_check);
    
    if ($result_check && $result_check->num_rows > 0) {
        $pesanan = $result_check->fetch_assoc();
        $status_lama = $pesanan['status'];
        $kode_pesanan = $pesanan['kode_pesanan'];
        
        // Update status pesanan
        $query = "UPDATE pesanan SET status = '$status_baru', updated_at = NOW() WHERE id = $pesanan_id";
        
        if ($db->query($query)) {
            // Cek apakah status berubah
            if ($status_lama !== $status_baru) {
                // Kirim notifikasi WhatsApp
                try {
                    $wa_result = kirimNotifikasiUpdateStatus($kode_pesanan, $status_baru);
                    
                    if ($wa_result['success']) {
                        setFlash('success', "Status pesanan berhasil diupdate dari <strong>{$status_lama}</strong> menjadi <strong>{$status_baru}</strong>! ✅ Notifikasi WhatsApp telah dikirim ke customer.");
                    } else {
                        setFlash('success', "Status pesanan berhasil diupdate dari <strong>{$status_lama}</strong> menjadi <strong>{$status_baru}</strong>! ⚠️ Namun notifikasi WhatsApp gagal dikirim: " . $wa_result['message']);
                    }
                } catch (Exception $e) {
                    // Jika gagal kirim WA, tetap berhasil update status
                    setFlash('success', "Status pesanan berhasil diupdate dari <strong>{$status_lama}</strong> menjadi <strong>{$status_baru}</strong>! ⚠️ Namun notifikasi WhatsApp gagal dikirim.");
                    error_log("Gagal kirim WA untuk pesanan $kode_pesanan: " . $e->getMessage());
                }
            } else {
                setFlash('info', 'Status pesanan tidak berubah.');
            }
        } else {
            setFlash('error', 'Gagal update status pesanan! Error: ' . $db->error);
        }
    } else {
        setFlash('error', 'Pesanan tidak ditemukan!');
    }
    
    // Redirect back
    if (isset($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: pesanan.php");
    }
} else {
    header("Location: pesanan.php");
}

exit();
?>