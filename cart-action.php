<?php
/**
 * Cart Actions Handler
 * Menangani aksi tambah, update, dan hapus item dari cart
 */

require_once 'config/database.php';
require_once 'config/session.php';

$db = getDB();

// Cek action
if (!isset($_POST['action'])) {
    header("Location: index.php");
    exit();
}

$action = $_POST['action'];

switch ($action) {
    case 'add':
        // Tambah produk ke cart
        $produk_id = (int)$_POST['produk_id'];
        $jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 1;
        
        // Get product data
        $query = "SELECT * FROM produk WHERE id = $produk_id AND is_active = 1";
        $result = $db->query($query);
        
        if ($result->num_rows > 0) {
            $produk = $result->fetch_assoc();
            
            // Cek stok
            if ($produk['stok'] >= $jumlah) {
                addToCart($produk_id, $jumlah, $produk);
                setFlash('success', 'Produk berhasil ditambahkan ke keranjang!');
            } else {
                setFlash('error', 'Stok produk tidak mencukupi!');
            }
        } else {
            setFlash('error', 'Produk tidak ditemukan!');
        }
        
        header("Location: " . $_SERVER['HTTP_REFERER']);
        break;
        
    case 'update':
        // Update jumlah item di cart
        $produk_id = (int)$_POST['produk_id'];
        $jumlah = (int)$_POST['jumlah'];
        
        // Validate stok
        $query = "SELECT stok FROM produk WHERE id = $produk_id";
        $result = $db->query($query);
        
        if ($result->num_rows > 0) {
            $produk = $result->fetch_assoc();
            
            if ($jumlah <= $produk['stok']) {
                updateCart($produk_id, $jumlah);
                setFlash('success', 'Keranjang berhasil diupdate!');
            } else {
                setFlash('error', 'Jumlah melebihi stok yang tersedia!');
            }
        }
        
        header("Location: cart.php");
        break;
        
    case 'remove':
        // Hapus item dari cart
        $produk_id = (int)$_POST['produk_id'];
        removeFromCart($produk_id);
        setFlash('success', 'Produk berhasil dihapus dari keranjang!');
        header("Location: cart.php");
        break;
        
    case 'clear':
        // Kosongkan cart
        clearCart();
        setFlash('success', 'Keranjang berhasil dikosongkan!');
        header("Location: cart.php");
        break;
        
    default:
        header("Location: index.php");
        break;
}
?>