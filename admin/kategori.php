<?php
$activePage = 'kategori';
require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

$db = getDB();

// Handle tambah kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $nama = escape($_POST['nama_kategori']);
        $deskripsi = escape($_POST['deskripsi']);
        
        $query = "INSERT INTO kategori (nama_kategori, deskripsi) VALUES ('$nama', '$deskripsi')";
        if ($db->query($query)) {
            setFlash('success', 'Kategori berhasil ditambahkan!');
        }
    }
    
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $nama = escape($_POST['nama_kategori']);
        $deskripsi = escape($_POST['deskripsi']);
        
        $query = "UPDATE kategori SET nama_kategori = '$nama', deskripsi = '$deskripsi' WHERE id = $id";
        if ($db->query($query)) {
            setFlash('success', 'Kategori berhasil diupdate!');
        }
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Cek apakah ada produk dengan kategori ini
        $check = $db->query("SELECT COUNT(*) as total FROM produk WHERE kategori_id = $id");
        $total = $check->fetch_assoc()['total'];
        
        if ($total > 0) {
            setFlash('error', "Tidak dapat menghapus kategori karena masih ada $total produk yang menggunakan kategori ini!");
        } else {
            $query = "DELETE FROM kategori WHERE id = $id";
            if ($db->query($query)) {
                setFlash('success', 'Kategori berhasil dihapus!');
            }
        }
    }
    
    header("Location: kategori.php");
    exit();
}

// Get semua kategori dengan jumlah produk
$query = "SELECT k.*, COUNT(p.id) as jumlah_produk 
          FROM kategori k 
          LEFT JOIN produk p ON k.id = p.kategori_id 
          GROUP BY k.id 
          ORDER BY k.nama_kategori";
$result = $db->query($query);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include 'includes/admin-styles.php'; ?>
</head>
<body class="bg-gray-100">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content p-4 md:p-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Manajemen Kategori</h1>
                <p class="text-gray-600">Kelola kategori produk</p>
            </div>
            <button onclick="showAddModal()" class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg font-semibold hover:shadow-xl transition">
                <i class="fas fa-plus mr-2"></i>Tambah Kategori
            </button>
        </div>
        
        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg mb-6">
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>
        
        <!-- Kategori Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($kategori = $result->fetch_assoc()): ?>
            <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition">
                <div class="flex justify-between items-start mb-4">
                    <div class="bg-purple-100 w-12 h-12 rounded-full flex items-center justify-center">
                        <i class="fas fa-tag text-purple-600 text-xl"></i>
                    </div>
                    <div class="flex gap-2">
                        <button onclick='showEditModal(<?= json_encode($kategori) ?>)' class="text-blue-600 hover:text-blue-700">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="confirmDelete(<?= $kategori['id'] ?>, '<?= addslashes($kategori['nama_kategori']) ?>', <?= $kategori['jumlah_produk'] ?>)" class="text-red-600 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2"><?= $kategori['nama_kategori'] ?></h3>
                <p class="text-gray-600 text-sm mb-4"><?= $kategori['deskripsi'] ?: 'Tidak ada deskripsi' ?></p>
                <div class="flex items-center text-purple-600">
                    <i class="fas fa-box mr-2"></i>
                    <span class="font-semibold"><?= $kategori['jumlah_produk'] ?> Produk</span>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold mb-6">Tambah Kategori</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Nama Kategori *</label>
                    <input type="text" name="nama_kategori" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"></textarea>
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
    
    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold mb-6">Edit Kategori</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Nama Kategori *</label>
                    <input type="text" name="nama_kategori" id="edit_nama" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"></textarea>
                </div>
                <div class="flex gap-4">
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-semibold hover:shadow-xl transition">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trash text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold mb-2">Hapus Kategori?</h3>
                <p class="text-gray-600 mb-6">Kategori <span id="delete_name" class="font-semibold"></span> akan dihapus.</p>
                <div class="flex gap-4">
                    <button onclick="closeDeleteModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Batal
                    </button>
                    <form id="deleteForm" method="POST" action="" class="flex-1">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }
        
        function showEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama').value = data.nama_kategori;
            document.getElementById('edit_deskripsi').value = data.deskripsi || '';
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        function confirmDelete(id, name, jumlahProduk) {
            if (jumlahProduk > 0) {
                alert('Tidak dapat menghapus kategori karena masih ada ' + jumlahProduk + ' produk yang menggunakan kategori ini!');
                return;
            }
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>
</body>
</html>