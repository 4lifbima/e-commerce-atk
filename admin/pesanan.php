<?php
$activePage = 'pesanan';
require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

$db = getDB();

// Filter
$status_filter = isset($_GET['status']) ? escape($_GET['status']) : '';
$search = isset($_GET['search']) ? escape($_GET['search']) : '';

// Build query
$where = ["1=1"];
if ($status_filter) {
    $where[] = "status = '$status_filter'";
}
if ($search) {
    $where[] = "(kode_pesanan LIKE '%$search%' OR nama_customer LIKE '%$search%')";
}
$where_clause = implode(' AND ', $where);

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total
$query_total = "SELECT COUNT(*) as total FROM pesanan WHERE $where_clause";
$total_pesanan = $db->query($query_total)->fetch_assoc()['total'];
$total_pages = ceil($total_pesanan / $limit);

// Get pesanan
$query = "SELECT * FROM pesanan 
          WHERE $where_clause 
          ORDER BY created_at DESC 
          LIMIT $limit OFFSET $offset";
$result = $db->query($query);

// Get statistik
$stats = [
    'total' => $db->query("SELECT COUNT(*) as total FROM pesanan")->fetch_assoc()['total'],
    'pending' => $db->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'Pending'")->fetch_assoc()['total'],
    'diproses' => $db->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'Diproses'")->fetch_assoc()['total'],
    'selesai' => $db->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'Selesai'")->fetch_assoc()['total']
];

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Manajemen Pesanan</h1>
            <p class="text-gray-600">Kelola semua pesanan pelanggan</p>
        </div>
        
        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg mb-6">
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl p-6">
                <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                <p class="text-blue-100 text-sm">Total Pesanan</p>
                <h3 class="text-4xl font-bold"><?= $stats['total'] ?></h3>
            </div>
            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-2xl p-6">
                <i class="fas fa-clock text-3xl mb-2"></i>
                <p class="text-yellow-100 text-sm">Pending</p>
                <h3 class="text-4xl font-bold"><?= $stats['pending'] ?></h3>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl p-6">
                <i class="fas fa-cog text-3xl mb-2"></i>
                <p class="text-purple-100 text-sm">Diproses</p>
                <h3 class="text-4xl font-bold"><?= $stats['diproses'] ?></h3>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl p-6">
                <i class="fas fa-check-circle text-3xl mb-2"></i>
                <p class="text-green-100 text-sm">Selesai</p>
                <h3 class="text-4xl font-bold"><?= $stats['selesai'] ?></h3>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <form method="GET" action="" class="flex flex-wrap gap-4">
                <input 
                    type="text" 
                    name="search" 
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Cari kode pesanan atau nama customer..." 
                    class="flex-1 min-w-[200px] px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                >
                <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                    <option value="">Semua Status</option>
                    <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Diproses" <?= $status_filter === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                    <option value="Selesai" <?= $status_filter === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="Dibatalkan" <?= $status_filter === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
                <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-search mr-2"></i>Cari
                </button>
                <a href="pesanan.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </form>
        </div>
        
        <!-- Orders Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">Kode Pesanan</th>
                            <th class="px-6 py-4 text-left">Customer</th>
                            <th class="px-6 py-4 text-left">Telepon</th>
                            <th class="px-6 py-4 text-right">Total</th>
                            <th class="px-6 py-4 text-center">Pembayaran</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Tanggal</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($pesanan = $result->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <span class="font-mono font-semibold text-purple-600"><?= $pesanan['kode_pesanan'] ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold"><?= $pesanan['nama_customer'] ?></p>
                                    <p class="text-sm text-gray-500"><?= $pesanan['email_customer'] ?></p>
                                </td>
                                <td class="px-6 py-4"><?= $pesanan['telepon_customer'] ?></td>
                                <td class="px-6 py-4 text-right font-semibold text-purple-600">
                                    Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">
                                        <?= $pesanan['metode_pembayaran'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                                        <?php 
                                        if ($pesanan['status'] === 'Pending') echo 'bg-yellow-100 text-yellow-700';
                                        elseif ($pesanan['status'] === 'Diproses') echo 'bg-blue-100 text-blue-700';
                                        elseif ($pesanan['status'] === 'Selesai') echo 'bg-green-100 text-green-700';
                                        else echo 'bg-red-100 text-red-700';
                                        ?>">
                                        <?= $pesanan['status'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-600">
                                    <?= date('d/m/Y H:i', strtotime($pesanan['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <a href="pesanan-detail.php?id=<?= $pesanan['id'] ?>" 
                                           class="bg-blue-500 text-white px-3 py-2 rounded-lg hover:bg-blue-600 transition" 
                                           title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($pesanan['status'] !== 'Selesai' && $pesanan['status'] !== 'Dibatalkan'): ?>
                                        <button 
                                            onclick="showStatusModal(<?= $pesanan['id'] ?>, '<?= $pesanan['kode_pesanan'] ?>', '<?= $pesanan['status'] ?>')"
                                            class="bg-purple-500 text-white px-3 py-2 rounded-lg hover:bg-purple-600 transition" 
                                            title="Update Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>Tidak ada pesanan</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t flex justify-center gap-2">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>" 
                   class="<?= $i == $page ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700' ?> px-4 py-2 rounded-lg hover:shadow-lg transition">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold mb-4">Update Status Pesanan</h3>
            <p class="text-gray-600 mb-6">Pesanan: <span id="modal_kode" class="font-semibold"></span></p>
            <form method="POST" action="pesanan-update.php">
                <input type="hidden" name="id" id="modal_id">
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Status Baru</label>
                    <select name="status" id="modal_status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                        <option value="Pending">Pending</option>
                        <option value="Diproses">Diproses</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Dibatalkan">Dibatalkan</option>
                    </select>
                </div>
                <div class="flex gap-4">
                    <button type="button" onclick="closeStatusModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-semibold hover:shadow-xl transition">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showStatusModal(id, kode, currentStatus) {
            document.getElementById('modal_id').value = id;
            document.getElementById('modal_kode').textContent = kode;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }
    </script>
</body>
</html>