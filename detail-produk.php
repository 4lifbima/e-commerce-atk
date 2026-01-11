<?php
/**
 * Detail Produk
 */

require_once 'config/database.php';
require_once 'config/session.php';

$db = getDB();
$produk_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get produk
$query = "SELECT p.*, k.nama_kategori 
          FROM produk p 
          JOIN kategori k ON p.kategori_id = k.id 
          WHERE p.id = $produk_id AND p.is_active = 1";
$result = $db->query($query);

if ($result->num_rows === 0) {
    header("Location: produk.php");
    exit();
}

$produk = $result->fetch_assoc();

// Get produk terkait (kategori sama)
$query_related = "SELECT * FROM produk 
                  WHERE kategori_id = {$produk['kategori_id']} 
                  AND id != $produk_id 
                  AND is_active = 1 
                  LIMIT 4";
$related = $db->query($query_related);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $produk['nama_produk'] ?> - Copy&ATK Premium</title>
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
                    
                    <?php if (isLoggedIn()): ?>
                    <a href="cart.php" class="relative text-gray-700 hover:text-[#400dd9] transition">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <?php if (getCartCount() > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            <?= getCartCount() ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    
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
                <?php if (isLoggedIn()): ?>
                <a href="cart.php" class="block text-gray-700 hover:text-[#400dd9]">Keranjang (<?= getCartCount() ?>)</a>
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
    
    <!-- Breadcrumb -->
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex items-center text-sm text-gray-600">
            <a href="index.php" class="hover:text-blue-600">Home</a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <a href="produk.php" class="hover:text-blue-600">Produk</a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <span class="text-blue-600"><?= $produk['nama_produk'] ?></span>
        </div>
    </div>
    
    <!-- Product Detail -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            <!-- Image -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <div class="aspect-square bg-gradient-to-br from-blue-100 to-pink-100 rounded-xl flex items-center justify-center">
                    <?php if ($produk['foto']): ?>
                    <img src="uploads/<?= $produk['foto'] ?>" alt="<?= $produk['nama_produk'] ?>" class="max-w-full max-h-full object-contain">
                    <?php else: ?>
                    <i class="fas fa-image text-gray-300 text-9xl"></i>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Info -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <span class="bg-blue-100 text-blue-600 px-4 py-1 rounded-full text-sm font-semibold">
                    <?= $produk['nama_kategori'] ?>
                </span>
                
                <h1 class="text-3xl font-bold text-gray-800 mt-4 mb-2"><?= $produk['nama_produk'] ?></h1>
                
                <div class="flex items-center gap-4 mb-6">
                    <span class="text-4xl font-bold text-blue-600">
                        Rp <?= number_format($produk['harga'], 0, ',', '.') ?>
                    </span>
                    <span class="text-sm <?= $produk['stok'] > 0 ? 'text-green-600' : 'text-red-600' ?> font-semibold">
                        <i class="fas fa-box mr-1"></i>
                        <?= $produk['stok'] > 0 ? "Stok: {$produk['stok']}" : "Stok Habis" ?>
                    </span>
                </div>
                
                <div class="border-t border-b py-4 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-2">Deskripsi Produk:</h3>
                    <p class="text-gray-600"><?= $produk['deskripsi'] ?: 'Tidak ada deskripsi untuk produk ini.' ?></p>
                </div>
                
                <?php if ($produk['stok'] > 0): ?>
                    <?php if (isLoggedIn()): ?>
                    <form method="POST" action="cart-action.php" class="space-y-4">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="produk_id" value="<?= $produk['id'] ?>">
                        
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Jumlah:</label>
                            <div class="flex items-center gap-4">
                                <button type="button" onclick="decreaseQty()" class="bg-gray-200 text-gray-700 w-10 h-10 rounded-lg hover:bg-gray-300 transition">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input 
                                    type="number" 
                                    id="jumlah" 
                                    name="jumlah" 
                                    value="1" 
                                    min="1" 
                                    max="<?= $produk['stok'] ?>"
                                    class="w-20 text-center px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"
                                >
                                <button type="button" onclick="increaseQty()" class="bg-gray-200 text-gray-700 w-10 h-10 rounded-lg hover:bg-gray-300 transition">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button 
                            type="submit"
                            class="w-full bg-[#400dd9] text-white py-4 rounded-xl font-bold text-lg hover:shadow-2xl transition"
                        >
                            <i class="fas fa-cart-plus mr-2"></i>
                            Tambah ke Keranjang
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="space-y-4">
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg">
                            <i class="fas fa-info-circle mr-2"></i>
                            Silakan login terlebih dahulu untuk menambahkan produk ke keranjang
                        </div>
                        <button 
                            onclick="window.location.href='login.php'"
                            class="w-full bg-[#400dd9] text-white py-4 rounded-xl font-bold text-lg hover:shadow-2xl transition"
                        >
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Login untuk Berbelanja
                        </button>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Produk ini sedang habis stok
                </div>
                <?php endif; ?>
                
                <div class="mt-6 space-y-2 text-sm text-gray-600">
                    <p><i class="fas fa-check text-green-600 mr-2"></i> Produk Original</p>
                    <p><i class="fas fa-check text-green-600 mr-2"></i> Gratis Konsultasi</p>
                    <p><i class="fas fa-check text-green-600 mr-2"></i> Pengiriman Cepat</p>
                </div>
            </div>
        </div>
        
        <!-- Produk Terkait -->
        <?php if ($related->num_rows > 0): ?>
        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Produk Terkait</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php while ($item = $related->fetch_assoc()): ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition">
                    <div class="h-48 bg-gradient-to-br from-blue-100 to-pink-100 flex items-center justify-center">
                        <?php if ($item['foto']): ?>
                        <img src="uploads/<?= $item['foto'] ?>" alt="<?= $item['nama_produk'] ?>" class="h-full w-full object-cover">
                        <?php else: ?>
                        <i class="fas fa-image text-gray-300 text-4xl"></i>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2"><?= $item['nama_produk'] ?></h3>
                        <p class="text-xl font-bold text-blue-600 mb-3">
                            Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                        </p>
                        <a href="detail-produk.php?id=<?= $item['id'] ?>" class="block w-full text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                            Lihat Detail
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
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
    
    <script>
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const jumlahInput = document.getElementById('jumlah');
        const maxStock = <?= $produk['stok'] ?>;

                // Mobile Menu Toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        function decreaseQty() {
            let val = parseInt(jumlahInput.value);
            if (val > 1) {
                jumlahInput.value = val - 1;
            }
        }
        
        function increaseQty() {
            let val = parseInt(jumlahInput.value);
            if (val < maxStock) {
                jumlahInput.value = val + 1;
            }
        }
    </script>
    <?php include 'components/chatbot-widget.php'; ?>
</body>
</html>