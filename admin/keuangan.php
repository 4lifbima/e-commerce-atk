<?php
$activePage = 'keuangan';

require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

$db = getDB();

// Handle tambah transaksi manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $jenis = escape($_POST['jenis']);
        $kategori = escape($_POST['kategori']);
        $deskripsi = escape($_POST['deskripsi']);
        $jumlah = (float)$_POST['jumlah'];
        $tanggal = escape($_POST['tanggal']);
        
        $query = "INSERT INTO keuangan (jenis, kategori, deskripsi, jumlah, tanggal) 
                  VALUES ('$jenis', '$kategori', '$deskripsi', $jumlah, '$tanggal')";
        
        if ($db->query($query)) {
            setFlash('success', 'Transaksi berhasil ditambahkan!');
        } else {
            setFlash('error', 'Gagal menambahkan transaksi!');
        }
        
        header("Location: keuangan.php");
        exit();
    }
    
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $query = "DELETE FROM keuangan WHERE id = $id";
        
        if ($db->query($query)) {
            setFlash('success', 'Transaksi berhasil dihapus!');
        }
        
        header("Location: keuangan.php");
        exit();
    }
}

// Filter tanggal
$start_date = isset($_GET['start']) ? escape($_GET['start']) : date('Y-m-01');
$end_date = isset($_GET['end']) ? escape($_GET['end']) : date('Y-m-d');

// Get rekap keuangan
$query_pemasukan = "SELECT COALESCE(SUM(jumlah), 0) as total 
                    FROM keuangan 
                    WHERE jenis = 'Pemasukan' 
                    AND tanggal BETWEEN '$start_date' AND '$end_date'";
$total_pemasukan = $db->query($query_pemasukan)->fetch_assoc()['total'];

$query_pengeluaran = "SELECT COALESCE(SUM(jumlah), 0) as total 
                      FROM keuangan 
                      WHERE jenis = 'Pengeluaran' 
                      AND tanggal BETWEEN '$start_date' AND '$end_date'";
$total_pengeluaran = $db->query($query_pengeluaran)->fetch_assoc()['total'];

$saldo = $total_pemasukan - $total_pengeluaran;

// Get transaksi
$query_transaksi = "SELECT * FROM keuangan 
                    WHERE tanggal BETWEEN '$start_date' AND '$end_date' 
                    ORDER BY tanggal DESC, created_at DESC";
$result_transaksi = $db->query($query_transaksi);

// Get data untuk chart (7 hari terakhir)
$query_chart = "SELECT 
                DATE(tanggal) as tgl,
                SUM(CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE 0 END) as pemasukan,
                SUM(CASE WHEN jenis = 'Pengeluaran' THEN jumlah ELSE 0 END) as pengeluaran
                FROM keuangan 
                WHERE tanggal >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
                GROUP BY tgl
                ORDER BY tgl ASC";
$result_chart = $db->query($query_chart);
$chart_data = [];
while ($row = $result_chart->fetch_assoc()) {
    $chart_data[] = $row;
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Keuangan - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php include 'includes/admin-styles.php'; ?>
</head>
<body class="bg-gray-100">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content p-4 md:p-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Manajemen Keuangan</h1>
                <p class="text-gray-600">Laporan pemasukan & pengeluaran</p>
            </div>
            <button onclick="showAddModal()" class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg font-semibold hover:shadow-xl transition">
                <i class="fas fa-plus mr-2"></i>Tambah Transaksi
            </button>
        </div>
        
        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg mb-6">
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>
        
        <!-- Filter -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-gray-700 font-semibold mb-2">Tanggal Mulai</label>
                    <input 
                        type="date" 
                        name="start" 
                        value="<?= $start_date ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                    >
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-gray-700 font-semibold mb-2">Tanggal Akhir</label>
                    <input 
                        type="date" 
                        name="end" 
                        value="<?= $end_date ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                    >
                </div>
                <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <a href="keuangan.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </form>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-green-100 text-sm">Total Pemasukan</p>
                        <h3 class="text-3xl font-bold mt-2">Rp <?= number_format($total_pemasukan, 0, ',', '.') ?></h3>
                    </div>
                    <div class="bg-green-400 w-12 h-12 rounded-lg flex items-center justify-center">
                        <i class="fas fa-arrow-up text-2xl"></i>
                    </div>
                </div>
                <p class="text-green-100 text-xs">
                    <i class="fas fa-calendar mr-1"></i>
                    <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>
                </p>
            </div>
            
            <div class="bg-gradient-to-br from-red-500 to-red-600 text-white rounded-2xl p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-red-100 text-sm">Total Pengeluaran</p>
                        <h3 class="text-3xl font-bold mt-2">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></h3>
                    </div>
                    <div class="bg-red-400 w-12 h-12 rounded-lg flex items-center justify-center">
                        <i class="fas fa-arrow-down text-2xl"></i>
                    </div>
                </div>
                <p class="text-red-100 text-xs">
                    <i class="fas fa-calendar mr-1"></i>
                    <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>
                </p>
            </div>
            
            <div class="bg-gradient-to-br from-<?= $saldo >= 0 ? 'blue' : 'orange' ?>-500 to-<?= $saldo >= 0 ? 'blue' : 'orange' ?>-600 text-white rounded-2xl p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-<?= $saldo >= 0 ? 'blue' : 'orange' ?>-100 text-sm">Saldo</p>
                        <h3 class="text-3xl font-bold mt-2">Rp <?= number_format(abs($saldo), 0, ',', '.') ?></h3>
                    </div>
                    <div class="bg-<?= $saldo >= 0 ? 'blue' : 'orange' ?>-400 w-12 h-12 rounded-lg flex items-center justify-center">
                        <i class="fas fa-wallet text-2xl"></i>
                    </div>
                </div>
                <p class="text-<?= $saldo >= 0 ? 'blue' : 'orange' ?>-100 text-xs">
                    <?= $saldo >= 0 ? 'Surplus' : 'Defisit' ?>
                </p>
            </div>
        </div>
        
        <!-- Chart -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-chart-line text-purple-600 mr-2"></i>
                Grafik Keuangan (7 Hari Terakhir)
            </h2>
            <canvas id="financeChart"></canvas>
        </div>
        
        <!-- Transactions Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-list text-purple-600 mr-2"></i>
                    Riwayat Transaksi
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jenis</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Deskripsi</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Jumlah</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_transaksi->num_rows > 0): ?>
                            <?php while ($trans = $result_transaksi->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm">
                                    <?= date('d/m/Y', strtotime($trans['tanggal'])) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $trans['jenis'] === 'Pemasukan' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                        <?= $trans['jenis'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold"><?= $trans['kategori'] ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= $trans['deskripsi'] ?: '-' ?></td>
                                <td class="px-6 py-4 text-right font-bold <?= $trans['jenis'] === 'Pemasukan' ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $trans['jenis'] === 'Pemasukan' ? '+' : '-' ?> Rp <?= number_format($trans['jumlah'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if (!$trans['pesanan_id']): ?>
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $trans['id'] ?>">
                                        <button 
                                            type="submit" 
                                            onclick="return confirm('Hapus transaksi ini?')"
                                            class="text-red-600 hover:text-red-700"
                                            title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-gray-400 text-xs">Auto</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    Tidak ada transaksi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold mb-6">Tambah Transaksi</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Jenis *</label>
                    <select name="jenis" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                        <option value="Pemasukan">Pemasukan</option>
                        <option value="Pengeluaran">Pengeluaran</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Kategori *</label>
                    <input type="text" name="kategori" required placeholder="Misal: Gaji, Sewa, Listrik" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Jumlah (Rp) *</label>
                    <input type="number" name="jumlah" required min="0" step="0.01" placeholder="50000" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Tanggal *</label>
                    <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" placeholder="Keterangan transaksi..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"></textarea>
                </div>
                
                <div class="flex gap-4">
                    <button type="button" onclick="closeAddModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-semibold hover:shadow-xl transition">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }
        
        // Chart
        const ctx = document.getElementById('financeChart').getContext('2d');
        const chartData = <?= json_encode($chart_data) ?>;
        
        const labels = chartData.map(item => {
            const date = new Date(item.tgl);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        });
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pemasukan',
                    data: chartData.map(item => parseFloat(item.pemasukan)),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Pengeluaran',
                    data: chartData.map(item => parseFloat(item.pengeluaran)),
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>