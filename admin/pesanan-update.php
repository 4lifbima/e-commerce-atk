<?php
/**
 * Admin - Update Status Pesanan
 */

require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $pesanan_id = (int)$_POST['id'];
    $status = escape($_POST['status']);
    
    $db = getDB();
    
    $query = "UPDATE pesanan SET status = '$status' WHERE id = $pesanan_id";
    
    if ($db->query($query)) {
        setFlash('success', 'Status pesanan berhasil diupdate!');
    } else {
        setFlash('error', 'Gagal update status pesanan!');
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