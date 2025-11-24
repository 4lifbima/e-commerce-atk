<?php
/**
 * Admin - Laporan Penjualan
 * Export ke PDF dan Excel
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireAdmin();

$db = getDB();

// Filter
$start_date = isset($_GET['start']) ? escape($_GET['start']) : date('Y-m-01');
$end_date = isset($_GET['end']) ? escape($_GET['end']) : date('Y-m-d');
$jenis_laporan = isset($_GET['jenis']) ? escape($_GET['jenis']) : 'penjualan';

// Get data & summary based on jenis laporan
$result = null;
$summary = null;

if ($jenis_laporan === 'penjualan') {
    $query = "SELECT 
                p.*,
                COALESCE((SELECT SUM(jumlah) FROM detail_pesanan WHERE pesanan_id = p.id), 0) as total_item
              FROM pesanan p
              WHERE DATE(p.created_at) BETWEEN ? AND ?
              AND p.status != 'Dibatalkan'
              ORDER BY p.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $query_summary = "SELECT 
                        COUNT(*) as total_transaksi,
                        SUM(total_harga) as total_penjualan,
                        AVG(total_harga) as rata_rata
                      FROM pesanan
                      WHERE DATE(created_at) BETWEEN ? AND ?
                      AND status != 'Dibatalkan'";
    $stmt2 = $db->prepare($query_summary);
    $stmt2->bind_param('ss', $start_date, $end_date);
    $stmt2->execute();
    $summary = $stmt2->get_result()->fetch_assoc();

} elseif ($jenis_laporan === 'produk_terlaris') {
    $query = "SELECT 
                p.nama_produk,
                k.nama_kategori,
                SUM(dp.jumlah) as total_terjual,
                SUM(dp.subtotal) as total_pendapatan,
                AVG(dp.harga) as harga_rata2
              FROM detail_pesanan dp
              JOIN produk p ON dp.produk_id = p.id
              JOIN kategori k ON p.kategori_id = k.id
              JOIN pesanan pe ON dp.pesanan_id = pe.id
              WHERE DATE(pe.created_at) BETWEEN ? AND ?
              AND pe.status != 'Dibatalkan'
              GROUP BY p.id
              ORDER BY total_terjual DESC
              LIMIT 20";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

} elseif ($jenis_laporan === 'keuangan') {
    $query = "SELECT * FROM keuangan 
              WHERE tanggal BETWEEN ? AND ?
              ORDER BY tanggal DESC, created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $query_summary = "SELECT 
                        SUM(CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE 0 END) as total_pemasukan,
                        SUM(CASE WHEN jenis = 'Pengeluaran' THEN jumlah ELSE 0 END) as total_pengeluaran
                      FROM keuangan
                      WHERE tanggal BETWEEN ? AND ?";
    $stmt2 = $db->prepare($query_summary);
    $stmt2->bind_param('ss', $start_date, $end_date);
    $stmt2->execute();
    $summary = $stmt2->get_result()->fetch_assoc();
    if ($summary) {
        $summary['saldo'] = $summary['total_pemasukan'] - $summary['total_pengeluaran'];
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include 'includes/admin-styles.php'; ?>
</head>
<body class="bg-gray-100">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content p-4 md:p-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Laporan & Export</h1>
            <p class="text-gray-600">Export laporan ke PDF atau Excel</p>
        </div>
        
        <!-- Filter -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <form method="GET" action="" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-gray-700 font-semibold mb-2">Jenis Laporan</label>
                    <select name="jenis" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                        <option value="penjualan" <?= $jenis_laporan === 'penjualan' ? 'selected' : '' ?>>Laporan Penjualan</option>
                        <option value="produk_terlaris" <?= $jenis_laporan === 'produk_terlaris' ? 'selected' : '' ?>>Produk Terlaris</option>
                        <option value="keuangan" <?= $jenis_laporan === 'keuangan' ? 'selected' : '' ?>>Laporan Keuangan</option>
                    </select>
                </div>
                
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-gray-700 font-semibold mb-2">Tanggal Mulai</label>
                    <input type="date" name="start" value="<?= htmlspecialchars($start_date) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                </div>
                
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-gray-700 font-semibold mb-2">Tanggal Akhir</label>
                    <input type="date" name="end" value="<?= htmlspecialchars($end_date) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </div>
            </form>
            
            <!-- Export Buttons -->
            <div class="flex gap-4 mt-4 pt-4 border-t">
                <a href="export-pdf.php?jenis=<?= urlencode($jenis_laporan) ?>&start=<?= urlencode($start_date) ?>&end=<?= urlencode($end_date) ?>" 
                   target="_blank"
                   class="flex-1 bg-red-600 text-white py-3 rounded-lg text-center font-semibold hover:bg-red-700 transition">
                    <i class="fas fa-file-pdf mr-2"></i>Export PDF
                </a>
                <a href="export-excel.php?jenis=<?= urlencode($jenis_laporan) ?>&start=<?= urlencode($start_date) ?>&end=<?= urlencode($end_date) ?>" 
                   class="flex-1 bg-green-600 text-white py-3 rounded-lg text-center font-semibold hover:bg-green-700 transition">
                    <i class="fas fa-file-excel mr-2"></i>Export Excel
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <?php if ($jenis_laporan === 'penjualan' && $summary): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl p-6">
                <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                <p class="text-blue-100 text-sm">Total Transaksi</p>
                <h3 class="text-4xl font-bold"><?= number_format($summary['total_transaksi']) ?></h3>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl p-6">
                <i class="fas fa-money-bill-wave text-3xl mb-2"></i>
                <p class="text-green-100 text-sm">Total Penjualan</p>
                <h3 class="text-2xl font-bold">Rp <?= number_format($summary['total_penjualan'], 0, ',', '.') ?></h3>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl p-6">
                <i class="fas fa-chart-line text-3xl mb-2"></i>
                <p class="text-purple-100 text-sm">Rata-rata</p>
                <h3 class="text-2xl font-bold">Rp <?= number_format($summary['rata_rata'], 0, ',', '.') ?></h3>
            </div>
        </div>

        <?php elseif ($jenis_laporan === 'keuangan' && $summary): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl p-6">
                <i class="fas fa-arrow-up text-3xl mb-2"></i>
                <p class="text-green-100 text-sm">Total Pemasukan</p>
                <h3 class="text-2xl font-bold">Rp <?= number_format($summary['total_pemasukan'], 0, ',', '.') ?></h3>
            </div>
            <div class="bg-gradient-to-br from-red-500 to-red-600 text-white rounded-2xl p-6">
                <i class="fas fa-arrow-down text-3xl mb-2"></i>
                <p class="text-red-100 text-sm">Total Pengeluaran</p>
                <h3 class="text-2xl font-bold">Rp <?= number_format($summary['total_pengeluaran'], 0, ',', '.') ?></h3>
            </div>
            <div class="bg-gradient-to-br from-<?= $summary['saldo'] >= 0 ? 'blue' : 'orange' ?>-500 to-<?= $summary['saldo'] >= 0 ? 'blue' : 'orange' ?>-600 text-white rounded-2xl p-6">
                <i class="fas fa-wallet text-3xl mb-2"></i>
                <p class="text-<?= $summary['saldo'] >= 0 ? 'blue' : 'orange' ?>-100 text-sm">Saldo</p>
                <h3 class="text-2xl font-bold">Rp <?= number_format(abs($summary['saldo']), 0, ',', '.') ?></h3>
            </div>
        </div>
        <?php endif; ?>

        <!-- Data Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <?php if ($jenis_laporan === 'penjualan'): ?>
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm">Tanggal</th>
                            <th class="px-6 py-3 text-left text-sm">Kode Pesanan</th>
                            <th class="px-6 py-3 text-left text-sm">Customer</th>
                            <th class="px-6 py-3 text-center text-sm">Item</th>
                            <th class="px-6 py-3 text-right text-sm">Total</th>
                            <th class="px-6 py-3 text-center text-sm">Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at']))) ?></td>
                            <td class="px-6 py-3 font-mono text-sm"><?= htmlspecialchars($row['kode_pesanan']) ?></td>
                            <td class="px-6 py-3 text-sm"><?= htmlspecialchars($row['nama_customer']) ?></td>
                            <td class="px-6 py-3 text-center text-sm"><?= $row['total_item'] ?></td>
                            <td class="px-6 py-3 text-right text-sm font-semibold">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                            <td class="px-6 py-3 text-center text-sm"><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php elseif ($jenis_laporan === 'produk_terlaris'): ?>
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm">Rank</th>
                            <th class="px-6 py-3 text-left text-sm">Nama Produk</th>
                            <th class="px-6 py-3 text-left text-sm">Kategori</th>
                            <th class="px-6 py-3 text-center text-sm">Total Terjual</th>
                            <th class="px-6 py-3 text-right text-sm">Total Pendapatan</th>
                            <th class="px-6 py-3 text-right text-sm">Harga Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; while ($row = $result->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <span class="bg-purple-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">
                                    <?= $rank++ ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 font-semibold"><?= htmlspecialchars($row['nama_produk']) ?></td>
                            <td class="px-6 py-3 text-sm"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                            <td class="px-6 py-3 text-center font-bold text-green-600"><?= $row['total_terjual'] ?></td>
                            <td class="px-6 py-3 text-right font-semibold">Rp <?= number_format($row['total_pendapatan'], 0, ',', '.') ?></td>
                            <td class="px-6 py-3 text-right text-sm">Rp <?= number_format($row['harga_rata2'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php elseif ($jenis_laporan === 'keuangan'): ?>
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm">Tanggal</th>
                            <th class="px-6 py-3 text-center text-sm">Jenis</th>
                            <th class="px-6 py-3 text-left text-sm">Kategori</th>
                            <th class="px-6 py-3 text-left text-sm">Deskripsi</th>
                            <th class="px-6 py-3 text-right text-sm">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm"><?= htmlspecialchars(date('d/m/Y', strtotime($row['tanggal']))) ?></td>
                            <td class="px-6 py-3 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $row['jenis'] === 'Pemasukan' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                    <?= htmlspecialchars($row['jenis']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm font-semibold"><?= htmlspecialchars($row['kategori']) ?></td>
                            <td class="px-6 py-3 text-sm"><?= htmlspecialchars($row['deskripsi']) ?></td>
                            <td class="px-6 py-3 text-right font-bold <?= $row['jenis'] === 'Pemasukan' ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $row['jenis'] === 'Pemasukan' ? '+' : '-' ?> Rp <?= number_format($row['jumlah'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
