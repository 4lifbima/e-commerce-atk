<?php
/**
 * Halaman List Produk
 * Tampilkan semua produk dengan filter & search
 */

require_once 'config/database.php';
require_once 'config/session.php';

$db = getDB();

// Pagination
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter & Search
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

// Build query
$where = ["p.is_active = 1"];
if ($search) {
    $where[] = "p.nama_produk LIKE '%$search%'";
}
if ($kategori_filter) {
    $where[] = "p.kategori_id = $kategori_filter";
}
$where_clause = implode(' AND ', $where);

// Sorting
$order = "p.created_at DESC";
switch($sort) {
    case 'termurah': $order = "p.harga ASC"; break;
    case 'termahal': $order = "p.harga DESC"; break;
    case 'nama': $order = "p.nama_produk ASC"; break;
}

// Get total
$query_total = "SELECT COUNT(*) as total FROM produk p WHERE $where_clause";
$total_produk = $db->query($query_total)->fetch_assoc()['total'];
$total_pages = ceil($total_produk / $limit);

// Get produk
$query = "SELECT p.*, k.nama_kategori 
          FROM produk p 
          JOIN kategori k ON p.kategori_id = k.id 
          WHERE $where_clause
          ORDER BY $order
          LIMIT $limit OFFSET $offset";
$result = $db->query($query);

// Get kategori
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori";
$result_kategori = $db->query($query_kategori);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk ATK - Copy&ATK Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
    </style>
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
                    <a href="produk.php" class="text-[#400dd9] transition">Produk</a>
                    <?php if (isLoggedIn()): ?>
                    <a href="fotocopy.php" class="text-gray-700 hover:text-[#400dd9] transition">Layanan Fotocopy</a>
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
                <a href="produk.php" class="block text-[#400dd9]">Produk</a>
                <?php if (isLoggedIn()): ?>
                <a href="fotocopy.php" class="block text-gray-700 hover:text-[#400dd9]">Layanan Fotocopy</a>
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
    
    <!-- Hero -->
    <div class="bg-[#400dd9] text-white py-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-2">Produk ATK Berkualitas</h1>
            <p class="text-purple-100">Temukan berbagai kebutuhan alat tulis dan perlengkapan kantor</p>
        </div>
    </div>
    
    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Sidebar Filter -->
            <div class="md:w-64 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                    <h2 class="text-xl font-bold mb-4">Filter & Cari</h2>
                    
                    <form method="GET" action="" class="space-y-4">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Cari Produk</label>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?= htmlspecialchars($search) ?>"
                                placeholder="Nama produk..." 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                            >
                        </div>
                        
                        <!-- Kategori -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Kategori</label>
                            <select name="kategori" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                                <option value="0">Semua Kategori</option>
                                <?php while ($kategori = $result_kategori->fetch_assoc()): ?>
                                <option value="<?= $kategori['id'] ?>" <?= $kategori_filter == $kategori['id'] ? 'selected' : '' ?>>
                                    <?= $kategori['nama_kategori'] ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <!-- Sort -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Urutkan</label>
                            <select name="sort" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                                <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                                <option value="termurah" <?= $sort === 'termurah' ? 'selected' : '' ?>>Termurah</option>
                                <option value="termahal" <?= $sort === 'termahal' ? 'selected' : '' ?>>Termahal</option>
                                <option value="nama" <?= $sort === 'nama' ? 'selected' : '' ?>>Nama A-Z</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="w-full bg-[#400dd9] text-white py-2 rounded-lg font-semibold hover:shadow-lg transition">
                            <i class="fas fa-search mr-2"></i>Terapkan
                        </button>
                        
                        <a href="produk.php" class="block w-full text-center bg-gray-200 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-300 transition">
                            <i class="fas fa-redo mr-2"></i>Reset
                        </a>
                    </form>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="flex-1">
                <div class="flex justify-between items-center mb-6">
                    <p class="text-gray-600">Menampilkan <?= $result->num_rows ?> dari <?= $total_produk ?> produk</p>
                </div>
                
                <?php if ($result->num_rows > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($produk = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                        <!-- Image -->
                        <div class="h-48 bg-gradient-to-br from-blue-100 to-cyan-100 flex items-center justify-center">
                            <?php if ($produk['foto']): ?>
                            <img src="uploads/<?= $produk['foto'] ?>" alt="<?= $produk['nama_produk'] ?>" class="h-full w-full object-cover">
                            <?php else: ?>
                            <i class="fas fa-image text-gray-300 text-6xl"></i>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Info -->
                        <div class="p-4">
                            <span class="text-xs bg-blue-100 text-purple-600 px-3 py-1 rounded-full">
                                <?= $produk['nama_kategori'] ?>
                            </span>
                            <h3 class="font-bold text-lg mt-2 mb-1"><?= $produk['nama_produk'] ?></h3>
                            <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?= $produk['deskripsi'] ?></p>
                            
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xl font-bold text-purple-600">
                                    Rp <?= number_format($produk['harga'], 0, ',', '.') ?>
                                </span>
                                <span class="text-xs <?= $produk['stok'] > 0 ? 'text-green-600' : 'text-red-600' ?>">
                                    <i class="fas fa-box"></i> Stok: <?= $produk['stok'] ?>
                                </span>
                            </div>
                            
                            <div class="flex gap-2">
                                <a href="detail-produk.php?id=<?= $produk['id'] ?>" class="flex-1 bg-gray-200 text-gray-700 text-center py-2 rounded-lg hover:bg-gray-300 transition">
                                    Detail
                                </a>
                                <?php if ($produk['stok'] > 0): ?>
                                    <?php if (isLoggedIn()): ?>
                                    <form method="POST" action="cart-action.php" class="flex-1">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="produk_id" value="<?= $produk['id'] ?>">
                                        <input type="hidden" name="jumlah" value="1">
                                        <button type="submit" class="w-full bg-[#400dd9] text-white py-2 rounded-lg hover:shadow-lg transition">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button onclick="window.location.href='login.php'" class="flex-1 bg-[#400dd9] text-white py-2 rounded-lg hover:shadow-lg transition">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                <button disabled class="flex-1 bg-gray-400 text-white py-2 rounded-lg cursor-not-allowed">
                                    Habis
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="flex justify-center gap-2 mt-8">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>&sort=<?= $sort ?>" 
                       class="<?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' ?> px-4 py-2 rounded-lg hover:shadow-lg transition">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                    <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg">Tidak ada produk ditemukan</p>
                    <a href="produk.php" class="inline-block mt-4 text-purple-600 hover:text-purple-700 font-semibold">
                        Lihat Semua Produk
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; 2025 Copy&ATK Premium. All rights reserved.</p>
        </div>
    </footer>
</body>
<script>
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
</script>
</html>