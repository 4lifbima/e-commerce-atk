<?php
$activePage = 'produk';
require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

$db = getDB();

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search & Filter
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

// Build query
$where = ["p.is_active = 1"];
if ($search) {
    $where[] = "p.nama_produk LIKE '%$search%'";
}
if ($kategori_filter) {
    $where[] = "p.kategori_id = $kategori_filter";
}
$where_clause = implode(' AND ', $where);

// Get total products
$query_total = "SELECT COUNT(*) as total FROM produk p WHERE $where_clause";
$total_produk = $db->query($query_total)->fetch_assoc()['total'];
$total_pages = ceil($total_produk / $limit);

// Get products
$query = "SELECT p.*, k.nama_kategori 
          FROM produk p 
          JOIN kategori k ON p.kategori_id = k.id 
          WHERE $where_clause
          ORDER BY p.created_at DESC 
          LIMIT $limit OFFSET $offset";
$result_produk = $db->query($query);

// Get kategori untuk filter
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori";
$result_kategori = $db->query($query_kategori);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Manajemen Produk</h1>
                <p class="text-gray-600">Kelola produk ATK Anda</p>
            </div>
            <a href="produk-tambah.php" class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg font-semibold hover:shadow-xl transition">
                <i class="fas fa-plus mr-2"></i>Tambah Produk
            </a>
        </div>
        
        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg mb-6">
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>
        
        <!-- Filter & Search -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <form method="GET" action="" class="flex flex-wrap gap-4">
                <!-- Search -->
                <div class="flex-1 min-w-[200px]">
                    <input 
                        type="text" 
                        name="search" 
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Cari produk..." 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                    >
                </div>
                
                <!-- Filter Kategori -->
                <div>
                    <select name="kategori" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                        <option value="0">Semua Kategori</option>
                        <?php while ($kategori = $result_kategori->fetch_assoc()): ?>
                        <option value="<?= $kategori['id'] ?>" <?= $kategori_filter == $kategori['id'] ? 'selected' : '' ?>>
                            <?= $kategori['nama_kategori'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <!-- Submit -->
                <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-search mr-2"></i>Cari
                </button>
                
                <!-- Reset -->
                <a href="produk.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </form>
        </div>
        
        <!-- Products Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">Foto</th>
                            <th class="px-6 py-4 text-left">Nama Produk</th>
                            <th class="px-6 py-4 text-left">Kategori</th>
                            <th class="px-6 py-4 text-right">Harga</th>
                            <th class="px-6 py-4 text-center">Stok</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_produk->num_rows > 0): ?>
                            <?php while ($produk = $result_produk->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <?php if ($produk['foto']): ?>
                                    <img src="../uploads/<?= $produk['foto'] ?>" alt="<?= $produk['nama_produk'] ?>" class="w-16 h-16 object-cover rounded-lg">
                                    <?php else: ?>
                                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-800"><?= $produk['nama_produk'] ?></p>
                                    <p class="text-sm text-gray-500"><?= substr($produk['deskripsi'], 0, 50) ?>...</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm">
                                        <?= $produk['nama_kategori'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-purple-600">
                                    Rp <?= number_format($produk['harga'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="<?= $produk['stok'] < 10 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?> px-3 py-1 rounded-full font-semibold">
                                        <?= $produk['stok'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <a href="produk-edit.php?id=<?= $produk['id'] ?>" 
                                           class="bg-blue-500 text-white px-3 py-2 rounded-lg hover:bg-blue-600 transition" 
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button 
                                            onclick="confirmDelete(<?= $produk['id'] ?>, '<?= addslashes($produk['nama_produk']) ?>')"
                                            class="bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition" 
                                            title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>Tidak ada produk ditemukan</p>
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
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>" 
                   class="<?= $i == $page ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700' ?> px-4 py-2 rounded-lg hover:shadow-lg transition">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl p-6">
                <i class="fas fa-box text-3xl mb-2"></i>
                <p class="text-blue-100 text-sm">Total Produk</p>
                <h3 class="text-4xl font-bold"><?= $total_produk ?></h3>
            </div>
            
            <?php
            $query_low_stock = "SELECT COUNT(*) as total FROM produk WHERE stok < 10 AND is_active = 1";
            $low_stock = $db->query($query_low_stock)->fetch_assoc()['total'];
            ?>
            <div class="bg-gradient-to-br from-red-500 to-red-600 text-white rounded-2xl p-6">
                <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                <p class="text-red-100 text-sm">Stok Menipis</p>
                <h3 class="text-4xl font-bold"><?= $low_stock ?></h3>
            </div>
            
            <?php
            $query_total_stok = "SELECT SUM(stok) as total FROM produk WHERE is_active = 1";
            $total_stok = $db->query($query_total_stok)->fetch_assoc()['total'];
            ?>
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl p-6">
                <i class="fas fa-warehouse text-3xl mb-2"></i>
                <p class="text-green-100 text-sm">Total Stok</p>
                <h3 class="text-4xl font-bold"><?= number_format($total_stok) ?></h3>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trash text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold mb-2">Hapus Produk?</h3>
                <p class="text-gray-600 mb-6">Produk <span id="productName" class="font-semibold"></span> akan dihapus. Tindakan ini tidak dapat dibatalkan.</p>
                <div class="flex gap-4">
                    <button onclick="closeDeleteModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Batal
                    </button>
                    <form id="deleteForm" method="POST" action="produk-delete.php" class="flex-1">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function confirmDelete(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('productName').textContent = name;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</body>
</html>