<?php
/**
 * Logout Handler
 * Menghapus session dan redirect ke homepage
 */

require_once 'config/session.php';

// Logout user
logoutUser();

// Set flash message
$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'Anda telah berhasil logout.'
];

// Redirect ke homepage
header("Location: ./");
exit();
?>