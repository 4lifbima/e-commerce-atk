<?php
$activePage = 'dashboard';
require_once '../config/database.php';
require_once '../config/session.php';

// Middleware - hanya admin yang bisa akses
requireAdmin();

$db = getDB();

// ---------------------------------------------------
// 1. STATISTIK UMUM (KARTU ATAS)
// ---------------------------------------------------

// Total produk
$total_produk = $db->query("SELECT COUNT(*) as total FROM produk WHERE is_active = 1")->fetch_assoc()['total'];

// Total pesanan
$total_pesanan = $db->query("SELECT COUNT(*) as total FROM pesanan")->fetch_assoc()['total'];

// Penjualan Bulan Ini
$query_penjualan_bulan = "SELECT COALESCE(SUM(total_harga), 0) as total 
                          FROM pesanan 
                          WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                          AND YEAR(created_at) = YEAR(CURRENT_DATE())
                          AND status != 'Dibatalkan'";
$penjualan_bulan = $db->query($query_penjualan_bulan)->fetch_assoc()['total'];

// Penjualan Hari Ini
$query_penjualan_hari = "SELECT COALESCE(SUM(total_harga), 0) as total 
                         FROM pesanan 
                         WHERE DATE(created_at) = CURRENT_DATE()
                         AND status != 'Dibatalkan'";
$penjualan_hari = $db->query($query_penjualan_hari)->fetch_assoc()['total'];


// ---------------------------------------------------
// 2. LOGIKA GRAFIK BERDASARKAN KATEGORI (REVISI 1)
// ---------------------------------------------------
$query_kategori = "SELECT k.nama_kategori, COALESCE(SUM(dp.subtotal), 0) as total_penjualan
                   FROM detail_pesanan dp
                   JOIN produk p ON dp.produk_id = p.id
                   JOIN kategori k ON p.kategori_id = k.id
                   JOIN pesanan ps ON dp.pesanan_id = ps.id
                   WHERE ps.status != 'Dibatalkan'
                   GROUP BY k.nama_kategori";
$result_kategori = $db->query($query_kategori);

$label_kategori = [];
$data_kategori = [];

while ($row = $result_kategori->fetch_assoc()) {
    $label_kategori[] = $row['nama_kategori'];
    $data_kategori[] = $row['total_penjualan'];
}


// ---------------------------------------------------
// 3. LOGIKA KEUNTUNGAN DENGAN FILTER (REVISI 2)
// ---------------------------------------------------
// Ambil filter dari URL, default 'semua'
$filter_profit = isset($_GET['filter_profit']) ? $_GET['filter_profit'] : 'semua';
$data_keuntungan = [];
$judul_profit = "";

// Rumus Keuntungan: (Harga Jual - Harga Beli) * Jumlah Terjual
// Kita asumsikan harga di detail_pesanan adalah harga jual saat itu.

switch ($filter_profit) {
    case 'produk':
        $judul_profit = "Keuntungan Per Produk";
        $query_profit = "SELECT p.nama_produk as label, 
                                SUM((dp.harga - p.harga_beli) * dp.jumlah) as profit
                         FROM detail_pesanan dp
                         JOIN produk p ON dp.produk_id = p.id
                         JOIN pesanan ps ON dp.pesanan_id = ps.id
                         WHERE ps.status = 'Selesai'
                         GROUP BY p.id, p.nama_produk
                         ORDER BY profit DESC LIMIT 10";
        break;

    case 'minggu':
        $judul_profit = "Keuntungan Per Minggu (Bulan Ini)";
        $query_profit = "SELECT CONCAT('Minggu ke-', WEEK(ps.created_at) - WEEK(DATE_SUB(ps.created_at, INTERVAL DAYOFMONTH(ps.created_at)-1 DAY)) + 1) as label,
                                SUM((dp.harga - p.harga_beli) * dp.jumlah) as profit
                         FROM detail_pesanan dp
                         JOIN produk p ON dp.produk_id = p.id
                         JOIN pesanan ps ON dp.pesanan_id = ps.id
                         WHERE ps.status = 'Selesai'
                         AND MONTH(ps.created_at) = MONTH(CURRENT_DATE())
                         AND YEAR(ps.created_at) = YEAR(CURRENT_DATE())
                         GROUP BY WEEK(ps.created_at)";
        break;

    case 'semua':
    default:
        $judul_profit = "Total Keuntungan Keseluruhan";
        // Kita tampilkan breakdown sederhana total saja
        $query_profit = "SELECT 'Semua Waktu' as label, 
                                SUM((dp.harga - p.harga_beli) * dp.jumlah) as profit
                         FROM detail_pesanan dp
                         JOIN produk p ON dp.produk_id = p.id
                         JOIN pesanan ps ON dp.pesanan_id = ps.id
                         WHERE ps.status = 'Selesai'";
        break;
}

$result_profit = $db->query($query_profit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Copy&ATK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        #sidebar { transition: transform 0.3s ease; }
        @media (max-width: 768px) { #sidebar.hidden-mobile { transform: translateX(-100%); } }
    </style>
</head>
<body class="bg-gray-100">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-gray-600">Selamat datang, <?= $_SESSION['nama'] ?>!</p>
            </div>
            
            <button id="mobile-menu-btn" class="md:hidden bg-purple-600 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl shadow-xl p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-blue-100 text-sm">Penjualan Hari Ini</p>
                        <h3 class="text-2xl font-bold mt-2">Rp <?= number_format($penjualan_hari, 0, ',', '.') ?></h3>
                    </div>
                    <div class="bg-blue-400 w-10 h-10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl shadow-xl p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-green-100 text-sm">Penjualan Bulan Ini</p>
                        <h3 class="text-2xl font-bold mt-2">Rp <?= number_format($penjualan_bulan, 0, ',', '.') ?></h3>
                    </div>
                    <div class="bg-green-400 w-10 h-10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl shadow-xl p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-purple-100 text-sm">Total Pesanan</p>
                        <h3 class="text-2xl font-bold mt-2"><?= $total_pesanan ?></h3>
                    </div>
                    <div class="bg-purple-400 w-10 h-10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-2xl shadow-xl p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-orange-100 text-sm">Total Produk</p>
                        <h3 class="text-2xl font-bold mt-2"><?= $total_produk ?></h3>
                    </div>
                    <div class="bg-orange-400 w-10 h-10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold mb-4 text-gray-800">
                    <i class="fas fa-chart-pie text-purple-600 mr-2"></i>
                    Penjualan per Kategori
                </h3>
                <div class="relative h-64">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-coins text-yellow-500 mr-2"></i>
                        Laporan Keuntungan
                    </h3>
                    
                    <form method="GET" id="filterForm">
                        <select name="filter_profit" onchange="document.getElementById('filterForm').submit()" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-purple-500 focus:border-purple-500 block p-2">
                            <option value="semua" <?= $filter_profit == 'semua' ? 'selected' : '' ?>>Semua (Total)</option>
                            <option value="produk" <?= $filter_profit == 'produk' ? 'selected' : '' ?>>Per Produk</option>
                            <option value="minggu" <?= $filter_profit == 'minggu' ? 'selected' : '' ?>>Per Minggu (Bulan Ini)</option>
                        </select>
                    </form>
                </div>

                <h4 class="text-sm font-semibold text-gray-500 mb-3"><?= $judul_profit ?></h4>

                <div class="space-y-3 overflow-y-auto max-h-64">
                    <?php if ($result_profit && $result_profit->num_rows > 0): ?>
                        <?php while ($row = $result_profit->fetch_assoc()): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center space-x-3">
                                <div class="bg-yellow-100 text-yellow-600 w-8 h-8 rounded-full flex items-center justify-center">
                                    <i class="fas fa-dollar-sign text-sm"></i>
                                </div>
                                <span class="font-semibold text-gray-700"><?= $row['label'] ?></span>
                            </div>
                            <span class="text-green-600 font-bold">
                                + Rp <?= number_format($row['profit'], 0, ',', '.') ?>
                            </span>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-chart-bar text-4xl mb-2"></i>
                            <p>Belum ada data keuntungan (Pesanan Selesai)</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        </div>
    
    <script>
        // Mobile menu
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('hidden-mobile');
        });
        
        // CHART KATEGORI (Bar Chart)
        const ctxCategory = document.getElementById('categoryChart').getContext('2d');
        const categoryData = {
            labels: <?= json_encode($label_kategori) ?>,
            datasets: [{
                label: 'Total Penjualan (Rp)',
                data: <?= json_encode($data_kategori) ?>,
                backgroundColor: [
                    'rgba(147, 51, 234, 0.7)', // Purple
                    'rgba(59, 130, 246, 0.7)', // Blue
                    'rgba(16, 185, 129, 0.7)', // Green
                    'rgba(245, 158, 11, 0.7)', // Orange
                    'rgba(239, 68, 68, 0.7)'   // Red
                ],
                borderWidth: 1,
                borderRadius: 5
            }]
        };

        new Chart(ctxCategory, {
            type: 'bar', // Menggunakan Bar Chart agar lebih mudah dibandingkan
            data: categoryData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID'); // Format Rupiah di sumbu Y
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>