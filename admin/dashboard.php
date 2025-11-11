<?php
$activePage = 'dashboard';
require_once '../config/database.php';
require_once '../config/session.php';

// Middleware - hanya admin yang bisa akses
requireAdmin();

$db = getDB();

// Get statistik data
// Total produk
$query_total_produk = "SELECT COUNT(*) as total FROM produk WHERE is_active = 1";
$total_produk = $db->query($query_total_produk)->fetch_assoc()['total'];

// Total pesanan
$query_total_pesanan = "SELECT COUNT(*) as total FROM pesanan";
$total_pesanan = $db->query($query_total_pesanan)->fetch_assoc()['total'];

// Pesanan pending
$query_pending = "SELECT COUNT(*) as total FROM pesanan WHERE status = 'Pending'";
$pesanan_pending = $db->query($query_pending)->fetch_assoc()['total'];

// Total penjualan bulan ini
$query_penjualan_bulan = "SELECT COALESCE(SUM(total_harga), 0) as total 
                          FROM pesanan 
                          WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                          AND YEAR(created_at) = YEAR(CURRENT_DATE())
                          AND status != 'Dibatalkan'";
$penjualan_bulan = $db->query($query_penjualan_bulan)->fetch_assoc()['total'];

// Total penjualan hari ini
$query_penjualan_hari = "SELECT COALESCE(SUM(total_harga), 0) as total 
                         FROM pesanan 
                         WHERE DATE(created_at) = CURRENT_DATE()
                         AND status != 'Dibatalkan'";
$penjualan_hari = $db->query($query_penjualan_hari)->fetch_assoc()['total'];

// Pesanan terbaru (5 terakhir)
$query_pesanan_terbaru = "SELECT * FROM pesanan ORDER BY created_at DESC LIMIT 5";
$pesanan_terbaru = $db->query($query_pesanan_terbaru);

// Produk stok menipis (stok < 10)
$query_stok_menipis = "SELECT * FROM produk WHERE stok < 10 AND is_active = 1 ORDER BY stok ASC LIMIT 5";
$stok_menipis = $db->query($query_stok_menipis);

// Data untuk grafik penjualan 7 hari terakhir
$query_grafik = "SELECT DATE(created_at) as tanggal, 
                 COALESCE(SUM(total_harga), 0) as total 
                 FROM pesanan 
                 WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
                 AND status != 'Dibatalkan'
                 GROUP BY DATE(created_at)
                 ORDER BY tanggal ASC";
$result_grafik = $db->query($query_grafik);
$data_grafik = [];
while ($row = $result_grafik->fetch_assoc()) {
    $data_grafik[] = $row;
}

// Produk terlaris bulan ini
$query_terlaris = "SELECT p.nama_produk, SUM(dp.jumlah) as total_terjual
                   FROM detail_pesanan dp
                   JOIN produk p ON dp.produk_id = p.id
                   JOIN pesanan pe ON dp.pesanan_id = pe.id
                   WHERE MONTH(pe.created_at) = MONTH(CURRENT_DATE())
                   AND YEAR(pe.created_at) = YEAR(CURRENT_DATE())
                   AND pe.status != 'Dibatalkan'
                   GROUP BY p.id
                   ORDER BY total_terjual DESC
                   LIMIT 5";
$produk_terlaris = $db->query($query_terlaris);
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
        /* Sidebar animation */
        #sidebar {
            transition: transform 0.3s ease;
        }
        
        @media (max-width: 768px) {
            #sidebar.hidden-mobile {
                transform: translateX(-100%);
            }
        }
        
        /* Smooth transitions */
        .transition-all {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100">
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Top Bar -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-gray-600">Selamat datang, <?= $_SESSION['nama'] ?>!</p>
            </div>
            
            <button id="mobile-menu-btn" class="md:hidden bg-purple-600 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Card 1: Penjualan Hari Ini -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl shadow-xl p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-blue-100 text-sm">Penjualan Hari Ini</p>
                        <h3 class="text-3xl font-bold mt-2">Rp <?= number_format($penjualan_hari, 0, ',', '.') ?></h3>
                    </div>
                    <div class="bg-blue-400 w-12 h-12 rounded-lg flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-2xl"></i>
                    </div>
                </div>
                <p class="text-blue-100 text-xs">
                    <i class="fas fa-calendar-day mr-1"></i>
                    <?= date('d F Y') ?>
                </p>
            </div>
            
            <!-- Card 2: Penjualan Bulan Ini -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl shadow-xl p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-green-100 text-sm">Penjualan Bulan Ini</p>
                        <h3 class="text-3xl font-bold mt-2">Rp <?= number_format($penjualan_bulan, 0, ',', '.') ?></h3>
                    </div>
                    <div class="bg-green-400 w-12 h-12 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-green-100 text-xs">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    <?= date('F Y') ?>
                </p>
            </div>
            
            <!-- Card 3: Total Pesanan -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl shadow-xl p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-purple-100 text-sm">Total Pesanan</p>
                        <h3 class="text-3xl font-bold mt-2"><?= $total_pesanan ?></h3>
                    </div>
                    <div class="bg-purple-400 w-12 h-12 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-2xl"></i>
                    </div>
                </div>
                <p class="text-purple-100 text-xs">
                    <i class="fas fa-clock mr-1"></i>
                    <?= $pesanan_pending ?> Menunggu Konfirmasi
                </p>
            </div>
            
            <!-- Card 4: Total Produk -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-2xl shadow-xl p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-orange-100 text-sm">Total Produk Aktif</p>
                        <h3 class="text-3xl font-bold mt-2"><?= $total_produk ?></h3>
                    </div>
                    <div class="bg-orange-400 w-12 h-12 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-2xl"></i>
                    </div>
                </div>
                <p class="text-orange-100 text-xs">
                    <i class="fas fa-check-circle mr-1"></i>
                    Produk Tersedia
                </p>
            </div>
        </div>
        
        <!-- Charts & Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Grafik Penjualan -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold mb-4 text-gray-800">
                    <i class="fas fa-chart-area text-purple-600 mr-2"></i>
                    Grafik Penjualan (7 Hari Terakhir)
                </h3>
                <canvas id="salesChart"></canvas>
            </div>
            
            <!-- Produk Terlaris -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold mb-4 text-gray-800">
                    <i class="fas fa-fire text-orange-600 mr-2"></i>
                    Produk Terlaris Bulan Ini
                </h3>
                <div class="space-y-3">
                    <?php if ($produk_terlaris->num_rows > 0): ?>
                        <?php $rank = 1; ?>
                        <?php while ($produk = $produk_terlaris->fetch_assoc()): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <span class="bg-purple-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">
                                    <?= $rank++ ?>
                                </span>
                                <span class="font-semibold text-gray-700"><?= $produk['nama_produk'] ?></span>
                            </div>
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-semibold">
                                <?= $produk['total_terjual'] ?> terjual
                            </span>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-4">Belum ada data penjualan</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Pesanan Terbaru -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold mb-4 text-gray-800">
                    <i class="fas fa-clock text-blue-600 mr-2"></i>
                    Pesanan Terbaru
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2 text-sm font-semibold text-gray-600">Kode</th>
                                <th class="text-left py-2 text-sm font-semibold text-gray-600">Customer</th>
                                <th class="text-left py-2 text-sm font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($pesanan = $pesanan_terbaru->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 text-sm font-mono"><?= $pesanan['kode_pesanan'] ?></td>
                                <td class="py-3 text-sm"><?= $pesanan['nama_customer'] ?></td>
                                <td class="py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        <?php 
                                        if ($pesanan['status'] === 'Pending') echo 'bg-yellow-100 text-yellow-700';
                                        elseif ($pesanan['status'] === 'Diproses') echo 'bg-blue-100 text-blue-700';
                                        elseif ($pesanan['status'] === 'Selesai') echo 'bg-green-100 text-green-700';
                                        else echo 'bg-red-100 text-red-700';
                                        ?>">
                                        <?= $pesanan['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-center">
                    <a href="pesanan.php" class="text-purple-600 hover:text-purple-700 font-semibold text-sm">
                        Lihat Semua Pesanan <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <!-- Stok Menipis -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold mb-4 text-gray-800">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                    Stok Menipis
                </h3>
                <div class="space-y-3">
                    <?php if ($stok_menipis->num_rows > 0): ?>
                        <?php while ($produk = $stok_menipis->fetch_assoc()): ?>
                        <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-200">
                            <div>
                                <p class="font-semibold text-gray-700"><?= $produk['nama_produk'] ?></p>
                                <p class="text-sm text-gray-500">Harga: Rp <?= number_format($produk['harga'], 0, ',', '.') ?></p>
                            </div>
                            <span class="bg-red-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                Stok: <?= $produk['stok'] ?>
                            </span>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-4">Semua stok aman!</p>
                    <?php endif; ?>
                </div>
                <div class="mt-4 text-center">
                    <a href="produk.php" class="text-purple-600 hover:text-purple-700 font-semibold text-sm">
                        Kelola Produk <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden-mobile');
        });
        
        // Chart.js - Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesData = <?= json_encode($data_grafik) ?>;
        
        const labels = salesData.map(item => {
            const date = new Date(item.tanggal);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        });
        
        const data = salesData.map(item => parseFloat(item.total));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Penjualan (Rp)',
                    data: data,
                    borderColor: 'rgb(147, 51, 234)',
                    backgroundColor: 'rgba(147, 51, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(147, 51, 234)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgb(147, 51, 234)',
                        padding: 12,
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>