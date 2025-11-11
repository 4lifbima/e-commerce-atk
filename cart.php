<?php
/**
 * Halaman Keranjang Belanja
 */

require_once 'config/database.php';
require_once 'config/session.php';

$db = getDB();
$cart = getCart();
$cart_total = getCartTotal();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Copy&ATK Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-2">
                        <i class="fas fa-print text-[#400dd9] text-2xl"></i>
                        <span class="text-xl font-bold text-[#400dd9]">
                            Copy&ATK Premium
                        </span>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-[#400dd9] transition">Beranda</a>
                    <a href="produk.php" class="text-gray-700 hover:text-[#400dd9] transition">Produk</a>
                    <a href="fotocopy.php" class="text-gray-700 hover:text-[#400dd9] transition">Layanan Fotocopy</a>
                    
                    <a href="cart.php" class="relative text-[#400dd9] transition">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <?php if (getCartCount() > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            <?= getCartCount() ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    
                    <?php if (isLoggedIn()): ?>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-gray-700 hover:text-[#400dd9]">
                                <i class="fas fa-user-circle text-xl"></i>
                                <span><?= $_SESSION['nama'] ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                                <?php if (isAdmin()): ?>
                                <a href="admin/dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Dashboard Admin</a>
                                <?php else: ?>
                                <a href="pesanan-saya.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Pesanan Saya</a>
                                <?php endif; ?>
                                <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="bg-[#400dd9] text-white px-6 py-2 rounded-full hover:bg-[#350ba1] transition shadow-md">
                            Login
                        </a>
                    <?php endif; ?>
                </div>
                
                <button id="mobile-menu-btn" class="md:hidden text-gray-700">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t">
            <div class="px-4 py-3 space-y-3">
                <a href="index.php" class="block text-gray-700 hover:text-[#400dd9]">Beranda</a>
                <a href="produk.php" class="block text-gray-700 hover:text-[#400dd9]">Produk</a>
                <a href="fotocopy.php" class="block text-gray-700 hover:text-[#400dd9]">Layanan Fotocopy</a>
                <a href="cart.php" class="block text-[#400dd9]">Keranjang (<?= getCartCount() ?>)</a>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php" class="block text-gray-700 hover:text-[#400dd9]">Dashboard Admin</a>
                    <?php else: ?>
                    <a href="pesanan-saya.php" class="block text-gray-700 hover:text-[#400dd9]">Pesanan Saya</a>
                    <?php endif; ?>
                    <a href="logout.php" class="block text-red-600">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="block text-[#400dd9] font-semibold">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <!-- Flash Message -->
    <?php if ($flash): ?>
    <div class="max-w-7xl mx-auto px-4 mt-4">
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg">
            <?= $flash['message'] ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">
            <i class="fas fa-shopping-cart text-purple-600 mr-2"></i>
            Keranjang Belanja
        </h1>
        
        <?php if (empty($cart)): ?>
            <!-- Empty Cart -->
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <i class="fas fa-shopping-cart text-gray-300 text-8xl mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Keranjang Kosong</h2>
                <p class="text-gray-600 mb-6">Belum ada produk dalam keranjang Anda</p>
                <a href="produk.php" class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 text-white px-8 py-3 rounded-full font-semibold hover:shadow-xl transition">
                    <i class="fas fa-shopping-bag mr-2"></i>
                    Mulai Belanja
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2 space-y-4">
                    <?php foreach ($cart as $produk_id => $item): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <!-- Image -->
                            <div class="flex-shrink-0">
                                <?php if ($item['foto']): ?>
                                <img src="uploads/<?= $item['foto'] ?>" alt="<?= $item['nama'] ?>" class="w-24 h-24 object-cover rounded-lg">
                                <?php else: ?>
                                <div class="w-24 h-24 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400 text-2xl"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Info -->
                            <div class="flex-1">
                                <h3 class="font-bold text-lg text-gray-800 mb-1"><?= $item['nama'] ?></h3>
                                <p class="text-purple-600 font-semibold mb-4">
                                    Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                                </p>
                                
                                <div class="flex flex-wrap items-center gap-4">
                                    <!-- Quantity -->
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="cart-action.php" class="inline">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="produk_id" value="<?= $produk_id ?>">
                                            <input type="hidden" name="jumlah" value="<?= $item['jumlah'] - 1 ?>">
                                            <button type="submit" class="bg-gray-200 text-gray-700 w-8 h-8 rounded-lg hover:bg-gray-300 transition">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </form>
                                        
                                        <span class="w-12 text-center font-semibold"><?= $item['jumlah'] ?></span>
                                        
                                        <form method="POST" action="cart-action.php" class="inline">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="produk_id" value="<?= $produk_id ?>">
                                            <input type="hidden" name="jumlah" value="<?= $item['jumlah'] + 1 ?>">
                                            <button type="submit" class="bg-gray-200 text-gray-700 w-8 h-8 rounded-lg hover:bg-gray-300 transition">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- Subtotal -->
                                    <div class="flex-1 text-right">
                                        <p class="text-sm text-gray-600">Subtotal</p>
                                        <p class="text-xl font-bold text-purple-600">
                                            Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?>
                                        </p>
                                    </div>
                                    
                                    <!-- Remove -->
                                    <form method="POST" action="cart-action.php" class="inline">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="produk_id" value="<?= $produk_id ?>">
                                        <button 
                                            type="submit" 
                                            onclick="return confirm('Hapus produk dari keranjang?')"
                                            class="text-red-600 hover:text-red-700 transition"
                                            title="Hapus">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Clear Cart -->
                    <div class="flex justify-between items-center">
                        <a href="produk.php" class="text-purple-600 hover:text-purple-700 font-semibold">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Lanjut Belanja
                        </a>
                        <form method="POST" action="cart-action.php" class="inline">
                            <input type="hidden" name="action" value="clear">
                            <button 
                                type="submit" 
                                onclick="return confirm('Kosongkan keranjang?')"
                                class="text-red-600 hover:text-red-700 font-semibold">
                                <i class="fas fa-trash mr-2"></i>
                                Kosongkan Keranjang
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-4">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Ringkasan Belanja</h2>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal (<?= getCartCount() ?> item)</span>
                                <span class="font-semibold">Rp <?= number_format($cart_total, 0, ',', '.') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Ongkos Kirim</span>
                                <span class="font-semibold text-green-600">GRATIS</span>
                            </div>
                            <div class="border-t pt-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-bold text-gray-800">Total</span>
                                    <span class="text-2xl font-bold text-purple-600">
                                        Rp <?= number_format($cart_total, 0, ',', '.') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <a href="checkout.php" class="block w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-4 rounded-xl font-bold text-center text-lg hover:shadow-2xl transition">
                            <i class="fas fa-shopping-bag mr-2"></i>
                            Checkout
                        </a>
                        
                        <div class="mt-6 space-y-2 text-sm text-gray-600">
                            <p><i class="fas fa-shield-alt text-green-600 mr-2"></i> Pembayaran Aman</p>
                            <p><i class="fas fa-truck text-green-600 mr-2"></i> Gratis Ongkir</p>
                            <p><i class="fas fa-undo text-green-600 mr-2"></i> Garansi 100%</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; 2025 Copy&ATK Premium. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>