<?php
/**
 * Pesanan Saya
 * Halaman riwayat pesanan untuk customer yang sudah login
 */

require_once 'config/database.php';
require_once 'config/session.php';

// Redirect jika belum login
requireLogin('login.php');

$db = getDB();
$user_id = $_SESSION['user_id'];

// Filter status
$status_filter = isset($_GET['status']) ? escape($_GET['status']) : '';

// Build query
$where = "user_id = $user_id";
if ($status_filter) {
    $where .= " AND status = '$status_filter'";
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total pesanan
$query_total = "SELECT COUNT(*) as total FROM pesanan WHERE $where";
$total_pesanan = $db->query($query_total)->fetch_assoc()['total'];
$total_pages = ceil($total_pesanan / $limit);

// Get pesanan
$query = "SELECT * FROM pesanan 
          WHERE $where 
          ORDER BY created_at DESC 
          LIMIT $limit OFFSET $offset";
$result = $db->query($query);

// Get statistik customer
$stats = [
    'total' => $db->query("SELECT COUNT(*) as total FROM pesanan WHERE user_id = $user_id")->fetch_assoc()['total'],
    'pending' => $db->query("SELECT COUNT(*) as total FROM pesanan WHERE user_id = $user_id AND status = 'Pending'")->fetch_assoc()['total'],
    'diproses' => $db->query("SELECT COUNT(*) as total FROM pesanan WHERE user_id = $user_id AND status = 'Diproses'")->fetch_assoc()['total'],
    'selesai' => $db->query("SELECT COUNT(*) as total FROM pesanan WHERE user_id = $user_id AND status = 'Selesai'")->fetch_assoc()['total']
];

// Total belanja
$query_total_belanja = "SELECT COALESCE(SUM(total_harga), 0) as total 
                        FROM pesanan 
                        WHERE user_id = $user_id 
                        AND status != 'Dibatalkan'";
$total_belanja = $db->query($query_total_belanja)->fetch_assoc()['total'];

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Copy&ATK Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-card {
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center space-x-2">
                    <i class="fas fa-print text-purple-600 text-2xl"></i>
                    <span class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                        Copy&ATK Premium
                    </span>
                </a>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-purple-600 transition">Beranda</a>
                    <a href="produk.php" class="text-gray-700 hover:text-purple-600 transition">Produk</a>
                    <a href="cart.php" class="relative text-gray-700 hover:text-purple-600 transition">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <?php if (getCartCount() > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            <?= getCartCount() ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-700 hover:text-purple-600">
                            <i class="fas fa-user-circle text-xl"></i>
                            <span><?= $_SESSION['nama'] ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                            <a href="pesanan-saya.php" class="block px-4 py-2 text-purple-600 bg-purple-50 font-semibold">
                                <i class="fas fa-shopping-bag mr-2"></i>Pesanan Saya
                            </a>
                            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="md:hidden text-gray-700">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t">
            <div class="px-4 py-3 space-y-3">
                <a href="index.php" class="block text-gray-700">Beranda</a>
                <a href="produk.php" class="block text-gray-700">Produk</a>
                <a href="cart.php" class="block text-gray-700">Keranjang (<?= getCartCount() ?>)</a>
                <a href="pesanan-saya.php" class="block text-purple-600 font-semibold">Pesanan Saya</a>
                <a href="logout.php" class="block text-red-600">Logout</a>
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
    
    <!-- Header -->
    <div class="bg-[#400dd9] text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold mb-2">
                        <i class="fas fa-shopping-bag mr-2"></i>
                        Pesanan Saya
                    </h1>
                    <p class="text-purple-100">Kelola dan lacak pesanan Anda</p>
                </div>
                <div class="hidden md:block">
                    <div class="bg-white/20 rounded-2xl p-4">
                        <p class="text-sm text-purple-100 mb-1">Total Belanja</p>
                        <p class="text-2xl font-bold">Rp <?= number_format($total_belanja, 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-4 text-center">
                <i class="fas fa-shopping-cart text-blue-600 text-3xl mb-2"></i>
                <p class="text-gray-600 text-sm">Total Pesanan</p>
                <p class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?></p>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-4 text-center">
                <i class="fas fa-clock text-yellow-600 text-3xl mb-2"></i>
                <p class="text-gray-600 text-sm">Pending</p>
                <p class="text-2xl font-bold text-yellow-600"><?= $stats['pending'] ?></p>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-4 text-center">
                <i class="fas fa-cog text-blue-600 text-3xl mb-2"></i>
                <p class="text-gray-600 text-sm">Diproses</p>
                <p class="text-2xl font-bold text-blue-600"><?= $stats['diproses'] ?></p>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-4 text-center">
                <i class="fas fa-check-circle text-green-600 text-3xl mb-2"></i>
                <p class="text-gray-600 text-sm">Selesai</p>
                <p class="text-2xl font-bold text-green-600"><?= $stats['selesai'] ?></p>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="bg-white rounded-2xl shadow-lg p-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <a href="pesanan-saya.php" class="<?= !$status_filter ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700' ?> px-4 py-2 rounded-lg font-semibold hover:shadow-lg transition">
                    <i class="fas fa-list mr-2"></i>Semua
                </a>
                <a href="?status=Pending" class="<?= $status_filter === 'Pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700' ?> px-4 py-2 rounded-lg font-semibold hover:shadow-lg transition">
                    <i class="fas fa-clock mr-2"></i>Pending
                </a>
                <a href="?status=Diproses" class="<?= $status_filter === 'Diproses' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' ?> px-4 py-2 rounded-lg font-semibold hover:shadow-lg transition">
                    <i class="fas fa-cog mr-2"></i>Diproses
                </a>
                <a href="?status=Selesai" class="<?= $status_filter === 'Selesai' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700' ?> px-4 py-2 rounded-lg font-semibold hover:shadow-lg transition">
                    <i class="fas fa-check-circle mr-2"></i>Selesai
                </a>
                <a href="?status=Dibatalkan" class="<?= $status_filter === 'Dibatalkan' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700' ?> px-4 py-2 rounded-lg font-semibold hover:shadow-lg transition">
                    <i class="fas fa-times-circle mr-2"></i>Dibatalkan
                </a>
            </div>
        </div>
        
        <!-- Orders List -->
        <?php if ($result->num_rows > 0): ?>
            <div class="space-y-4">
                <?php while ($pesanan = $result->fetch_assoc()): ?>
                    <?php
                    // Get detail produk untuk pesanan ini
                    $pesanan_id = $pesanan['id'];
                    $query_detail = "SELECT dp.*, p.foto FROM detail_pesanan dp 
                                    LEFT JOIN produk p ON dp.produk_id = p.id
                                    WHERE dp.pesanan_id = $pesanan_id LIMIT 3";
                    $detail = $db->query($query_detail);
                    
                    // Cek apakah ada fotocopy
                    $query_fc = "SELECT * FROM pesanan_fotocopy WHERE pesanan_id = $pesanan_id";
                    $has_fotocopy = $db->query($query_fc)->num_rows > 0;
                    
                    // Total item
                    $query_count = "SELECT SUM(jumlah) as total FROM detail_pesanan WHERE pesanan_id = $pesanan_id";
                    $total_items = $db->query($query_count)->fetch_assoc()['total'] ?? 0;
                    ?>
                    
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden order-card">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <i class="fas fa-receipt text-purple-600 text-2xl"></i>
                                <div>
                                    <p class="text-sm text-gray-600">Kode Pesanan</p>
                                    <p class="font-bold text-lg text-gray-800"><?= $pesanan['kode_pesanan'] ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">Tanggal</p>
                                    <p class="font-semibold text-gray-800"><?= date('d/m/Y', strtotime($pesanan['created_at'])) ?></p>
                                </div>
                                <span class="px-4 py-2 rounded-full text-sm font-bold
                                    <?php 
                                    if ($pesanan['status'] === 'Pending') echo 'bg-yellow-100 text-yellow-700';
                                    elseif ($pesanan['status'] === 'Diproses') echo 'bg-blue-100 text-blue-700';
                                    elseif ($pesanan['status'] === 'Selesai') echo 'bg-green-100 text-green-700';
                                    else echo 'bg-red-100 text-red-700';
                                    ?>">
                                    <?= $pesanan['status'] ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Body -->
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Items Preview -->
                                <div class="md:col-span-2">
                                    <h3 class="font-semibold text-gray-800 mb-3">Item Pesanan:</h3>
                                    
                                    <?php if ($detail->num_rows > 0): ?>
                                    <div class="space-y-2">
                                        <?php while ($item = $detail->fetch_assoc()): ?>
                                        <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg">
                                            <?php if ($item['foto']): ?>
                                            <img src="uploads/<?= $item['foto'] ?>" alt="<?= $item['nama_produk'] ?>" class="w-12 h-12 object-cover rounded">
                                            <?php else: ?>
                                            <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div class="flex-1">
                                                <p class="font-semibold text-sm"><?= $item['nama_produk'] ?></p>
                                                <p class="text-xs text-gray-600"><?= $item['jumlah'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></p>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                        
                                        <?php if ($total_items > 3): ?>
                                        <p class="text-sm text-gray-600 text-center py-2">
                                            +<?= $total_items - 3 ?> item lainnya
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($has_fotocopy): ?>
                                    <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                        <p class="text-sm font-semibold text-blue-800">
                                            <i class="fas fa-print mr-2"></i>
                                            Termasuk order fotocopy
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Order Info -->
                                <div class="space-y-3">
                                    <div class="bg-purple-50 rounded-lg p-4">
                                        <p class="text-sm text-gray-600 mb-1">Total Pembayaran</p>
                                        <p class="text-2xl font-bold text-purple-600">
                                            Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?>
                                        </p>
                                    </div>
                                    
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="space-y-2 text-sm">
                                            <div class="flex items-center justify-between">
                                                <span class="text-gray-600">Metode Bayar:</span>
                                                <span class="font-semibold"><?= $pesanan['metode_pembayaran'] ?></span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-gray-600">Total Item:</span>
                                                <span class="font-semibold"><?= $total_items ?> item</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <a href="invoice.php?kode=<?= $pesanan['kode_pesanan'] ?>" 
                                       class="block w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white text-center py-3 rounded-lg font-semibold hover:shadow-xl transition">
                                        <i class="fas fa-file-invoice mr-2"></i>
                                        Lihat Invoice
                                    </a>
                                    
                                    <?php if ($pesanan['status'] === 'Pending'): ?>
                                    <p class="text-xs text-center text-gray-600">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Pesanan sedang menunggu konfirmasi
                                    </p>
                                    <?php elseif ($pesanan['status'] === 'Diproses'): ?>
                                    <p class="text-xs text-center text-blue-600">
                                        <i class="fas fa-spinner fa-spin mr-1"></i>
                                        Pesanan sedang diproses
                                    </p>
                                    <?php elseif ($pesanan['status'] === 'Selesai'): ?>
                                    <p class="text-xs text-center text-green-600">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Pesanan sudah selesai
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="flex justify-center gap-2 mt-8">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&status=<?= urlencode($status_filter) ?>" 
                   class="<?= $i == $page ? 'bg-purple-600 text-white' : 'bg-white text-gray-700' ?> px-4 py-2 rounded-lg hover:shadow-lg transition border">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Empty State -->
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <i class="fas fa-shopping-bag text-gray-300 text-8xl mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Belum Ada Pesanan</h2>
                <p class="text-gray-600 mb-6">Anda belum memiliki pesanan <?= $status_filter ? 'dengan status ' . $status_filter : '' ?></p>
                <a href="produk.php" class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 text-white px-8 py-3 rounded-full font-semibold hover:shadow-xl transition">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    Mulai Belanja
                </a>
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
        // Mobile Menu Toggle
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>