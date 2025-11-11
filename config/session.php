<?php
/**
 * Session Management & Authentication Helper
 * File ini menangani session, autentikasi, dan middleware
 */

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 * @return boolean
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Cek apakah user adalah admin
 * @return boolean
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Cek apakah user adalah customer
 * @return boolean
 */
function isCustomer() {
    return isLoggedIn() && $_SESSION['role'] === 'customer';
}

/**
 * Redirect jika tidak login (Middleware)
 * @param string $redirect_to URL tujuan redirect
 */
function requireLogin($redirect_to = '/login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect_to");
        exit();
    }
}

/**
 * Redirect jika bukan admin (Middleware untuk halaman admin)
 * @param string $redirect_to URL tujuan redirect
 */
function requireAdmin($redirect_to = '/login.php') {
    if (!isAdmin()) {
        header("Location: $redirect_to");
        exit();
    }
}

/**
 * Login user (set session)
 * @param array $user Data user dari database
 */
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
}

/**
 * Logout user (destroy session)
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Get current user data
 * @return array|null
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'nama' => $_SESSION['nama'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}

/**
 * Initialize shopping cart in session
 */
function initCart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

/**
 * Add product to cart
 * @param int $produk_id
 * @param int $jumlah
 * @param array $produk_data
 */
function addToCart($produk_id, $jumlah, $produk_data) {
    initCart();
    
    if (isset($_SESSION['cart'][$produk_id])) {
        $_SESSION['cart'][$produk_id]['jumlah'] += $jumlah;
    } else {
        $_SESSION['cart'][$produk_id] = [
            'id' => $produk_id,
            'nama' => $produk_data['nama_produk'],
            'harga' => $produk_data['harga'],
            'foto' => $produk_data['foto'],
            'jumlah' => $jumlah
        ];
    }
}

/**
 * Update cart item quantity
 * @param int $produk_id
 * @param int $jumlah
 */
function updateCart($produk_id, $jumlah) {
    initCart();
    
    if ($jumlah <= 0) {
        removeFromCart($produk_id);
    } else {
        if (isset($_SESSION['cart'][$produk_id])) {
            $_SESSION['cart'][$produk_id]['jumlah'] = $jumlah;
        }
    }
}

/**
 * Remove product from cart
 * @param int $produk_id
 */
function removeFromCart($produk_id) {
    initCart();
    unset($_SESSION['cart'][$produk_id]);
}

/**
 * Get cart items
 * @return array
 */
function getCart() {
    initCart();
    return $_SESSION['cart'];
}

/**
 * Get cart total
 * @return float
 */
function getCartTotal() {
    initCart();
    $total = 0;
    
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['harga'] * $item['jumlah'];
    }
    
    return $total;
}

/**
 * Get cart count
 * @return int
 */
function getCartCount() {
    initCart();
    $count = 0;
    
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['jumlah'];
    }
    
    return $count;
}

/**
 * Clear cart
 */
function clearCart() {
    $_SESSION['cart'] = [];
}

/**
 * Set flash message
 * @param string $type (success, error, warning, info)
 * @param string $message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
?>