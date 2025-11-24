<?php
/**
 * Admin - Manajemen Kupon/Promo
 */

require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $kode_kupon = strtoupper(escape($_POST['kode_kupon']));
        $nama_kupon = escape($_POST['nama_kupon']);
        $deskripsi = escape($_POST['deskripsi']);
        $jenis_diskon = escape($_POST['jenis_diskon']);
        $nilai_diskon = (float)$_POST['nilai_diskon'];
        $min_pembelian = (float)$_POST['min_pembelian'];
        $max_diskon = !empty($_POST['max_diskon']) ? (float)$_POST['max_diskon'] : 'NULL';
        $kuota = !empty($_POST['kuota']) ? (int)$_POST['kuota'] : 'NULL';
        $tgl_mulai = escape($_POST['tgl_mulai']);
        $tgl_selesai = escape($_POST['tgl_selesai']);
        
        $query = "INSERT INTO kupon (kode_kupon, nama_kupon, deskripsi, jenis_diskon, nilai_diskon, min_pembelian, max_diskon, kuota, tgl_mulai, tgl_selesai) 
                  VALUES ('$kode_kupon', '$nama_kupon', '$deskripsi', '$jenis_diskon', $nilai_diskon, $min_pembelian, $max_diskon, $kuota, '$tgl_mulai', '$tgl_selesai')";
        
        if ($db->query($query)) {
            setFlash('success', 'Kupon berhasil ditambahkan!');
        } else {
            setFlash('error', 'Gagal menambahkan kupon: ' . $db->error);
        }
        
        header("Location: kupon.php");
        exit();
    }
    
    if ($_POST['action'] === 'toggle_status') {
        $id = (int)$_POST['id'];
        $query = "UPDATE kupon SET status_aktif = NOT status_aktif WHERE id = $id";
        
        if ($db->query($query)) {
            setFlash('success', 'Status kupon berhasil diupdate!');
        }
        
        header("Location: kupon.php");
        exit();
    }
    
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $query = "DELETE FROM kupon WHERE id = $id";
        
        if ($db->query($query)) {
            setFlash('success', 'Kupon berhasil dihapus!');
        }
        
        header("Location: kupon.php");
        exit();
    }
}

// Get all kupon
$query = "SELECT * FROM kupon ORDER BY created_at DESC";
$result = $db->query($query);

// Get stats
$query_stats = "SELECT 
                COUNT(*) as total_kupon,
                SUM(CASE WHEN status_aktif = 1 AND CURRENT_DATE() BETWEEN tgl_mulai AND tgl_selesai THEN 1 ELSE 0 END) as aktif,
                SUM(kuota_terpakai) as total_digunakan
                FROM kupon";
$stats = $db->query($query_stats)->fetch_assoc();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kupon - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Manajemen Kupon & Promo</h1>
                <p class="text-gray-600">Kelola kode promo dan diskon</p>
            </div>
            <button onclick="showAddModal()" class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg font-semibold hover:shadow-xl transition">
                <i class="fas fa-plus mr-2"></i>Tambah Kupon
            </button>
        </div>
        
        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg mb-6">
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl p-6">
                <i class="fas fa-ticket-alt text-3xl mb-2"></i>
                <p class="text-blue-100 text-sm">Total Kupon</p>
                <h3 class="text-4xl font-bold"><?= $stats['total_kupon'] ?></h3>
            </div>
            
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl p-6">
                <i class="fas fa-check-circle text-3xl mb-2"></i>
                <p class="text-green-100 text-sm">Kupon Aktif</p>
                <h3 class="text-4xl font-bold"><?= $stats['aktif'] ?></h3>
            </div>
            
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl p-6">
                <i class="fas fa-users text-3xl mb-2"></i>
                <p class="text-purple-100 text-sm">Total Digunakan</p>
                <h3 class="text-4xl font-bold"><?= $stats['total_digunakan'] ?></h3>
            </div>
        </div>
        
        <!-- Kupon List -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">Kode Kupon</th>
                            <th class="px-6 py-4 text-left">Nama Kupon</th>
                            <th class="px-6 py-4 text-center">Jenis Diskon</th>
                            <th class="px-6 py-4 text-center">Nilai</th>
                            <th class="px-6 py-4 text-center">Min. Belanja</th>
                            <th class="px-6 py-4 text-center">Kuota</th>
                            <th class="px-6 py-4 text-center">Periode</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($kupon = $result->fetch_assoc()): ?>
                            <?php
                            $is_expired = date('Y-m-d') > $kupon['tgl_selesai'];
                            $is_future = date('Y-m-d') < $kupon['tgl_mulai'];
                            $is_active = $kupon['status_aktif'] && !$is_expired && !$is_future;
                            ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <span class="font-mono font-bold text-purple-600"><?= $kupon['kode_kupon'] ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold"><?= $kupon['nama_kupon'] ?></p>
                                    <p class="text-xs text-gray-500"><?= substr($kupon['deskripsi'], 0, 50) ?>...</p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-xs font-semibold">
                                        <?= $kupon['jenis_diskon'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-green-600">
                                    <?= $kupon['jenis_diskon'] === 'Persen' ? $kupon['nilai_diskon'] . '%' : 'Rp ' . number_format($kupon['nilai_diskon'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 text-center text-sm">
                                    Rp <?= number_format($kupon['min_pembelian'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($kupon['kuota']): ?>
                                    <span class="text-sm"><?= $kupon['kuota_terpakai'] ?> / <?= $kupon['kuota'] ?></span>
                                    <?php else: ?>
                                    <span class="text-sm text-gray-500">Unlimited</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center text-xs">
                                    <?= date('d/m/Y', strtotime($kupon['tgl_mulai'])) ?><br>
                                    s/d<br>
                                    <?= date('d/m/Y', strtotime($kupon['tgl_selesai'])) ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($is_expired): ?>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-semibold">Expired</span>
                                    <?php elseif ($is_future): ?>
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold">Belum Aktif</span>
                                    <?php elseif ($is_active): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Aktif</span>
                                    <?php else: ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $kupon['id'] ?>">
                                            <button type="submit" class="bg-yellow-500 text-white px-3 py-2 rounded-lg hover:bg-yellow-600 transition" title="Toggle Status">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="inline" onsubmit="return confirm('Hapus kupon ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $kupon['id'] ?>">
                                            <button type="submit" class="bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                    Belum ada kupon
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
        <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-4 my-8">
            <h3 class="text-2xl font-bold mb-6">Tambah Kupon Baru</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Kode Kupon *</label>
                        <input type="text" name="kode_kupon" required class="w-full px-4 py-2 border rounded-lg uppercase" placeholder="DISKON10">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Jenis Diskon *</label>
                        <select name="jenis_diskon" required class="w-full px-4 py-2 border rounded-lg" onchange="toggleMaxDiskon(this.value)">
                            <option value="Persen">Persentase (%)</option>
                            <option value="Nominal">Nominal (Rp)</option>
                        </select>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">Nama Kupon *</label>
                        <input type="text" name="nama_kupon" required class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">Deskripsi</label>
                        <textarea name="deskripsi" rows="2" class="w-full px-4 py-2 border rounded-lg"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Nilai Diskon *</label>
                        <input type="number" name="nilai_diskon" required min="0" step="0.01" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <div id="max_diskon_div">
                        <label class="block text-gray-700 font-semibold mb-2">Max Diskon (opsional)</label>
                        <input type="number" name="max_diskon" min="0" step="0.01" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Min. Pembelian *</label>
                        <input type="number" name="min_pembelian" required min="0" step="0.01" value="0" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Kuota (opsional)</label>
                        <input type="number" name="kuota" min="1" class="w-full px-4 py-2 border rounded-lg" placeholder="Kosongkan untuk unlimited">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Tanggal Mulai *</label>
                        <input type="date" name="tgl_mulai" required class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Tanggal Selesai *</label>
                        <input type="date" name="tgl_selesai" required class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
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
        
        function toggleMaxDiskon(jenis) {
            const maxDiskonDiv = document.getElementById('max_diskon_div');
            if (jenis === 'Persen') {
                maxDiskonDiv.classList.remove('hidden');
            } else {
                maxDiskonDiv.classList.add('hidden');
            }
        }
    </script>
</body>
</html>