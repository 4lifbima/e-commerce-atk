<?php
/**
 * Admin - Manajemen Hadiah Poin
 */
$activePage = 'hadiah-poin';
require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $nama_hadiah = escape($_POST['nama_hadiah']);
        $poin_dibutuhkan = (int)$_POST['poin_dibutuhkan'];
        $stok = (int)$_POST['stok'];
        $deskripsi = escape($_POST['deskripsi']);
        
        // Upload gambar
        $gambar_url = '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['gambar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'reward_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                // Upload ke folder uploads di root (sesuai struktur produk)
                // Note: script ini ada di admin/, jadi uploads ada di ../uploads/
                $upload_path = '../uploads/' . $new_filename;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    // Simpan path relatif untuk database (sesuai penggunaan di hadiah.php: uploads/namafile)
                    $gambar_url = 'uploads/' . $new_filename;
                } else {
                    setFlash('error', 'Gagal upload gambar!');
                    header("Location: hadiah-poin.php");
                    exit();
                }
            } else {
                setFlash('error', 'Format gambar tidak valid! Gunakan JPG, PNG, atau GIF.');
                header("Location: hadiah-poin.php");
                exit();
            }
        }
        
        $query = "INSERT INTO hadiah_poin (nama_hadiah, poin_dibutuhkan, stok, deskripsi, gambar_url) 
                  VALUES ('$nama_hadiah', $poin_dibutuhkan, $stok, '$deskripsi', '$gambar_url')";
        
        if ($db->query($query)) {
            setFlash('success', 'Hadiah berhasil ditambahkan!');
        } else {
            setFlash('error', 'Gagal menambahkan hadiah: ' . $db->error);
        }
        
        header("Location: hadiah-poin.php");
        exit();
    }
    
    if ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $nama_hadiah = escape($_POST['nama_hadiah']);
        $poin_dibutuhkan = (int)$_POST['poin_dibutuhkan'];
        $stok = (int)$_POST['stok'];
        $deskripsi = escape($_POST['deskripsi']);
        
        // Cek jika ada upload gambar baru
        $gambar_sql = "";
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['gambar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'reward_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $upload_path = '../uploads/' . $new_filename;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $gambar_url = 'uploads/' . $new_filename;
                    $gambar_sql = ", gambar_url = '$gambar_url'";
                }
            }
        }
        
        $query = "UPDATE hadiah_poin SET 
                  nama_hadiah = '$nama_hadiah', 
                  poin_dibutuhkan = $poin_dibutuhkan, 
                  stok = $stok, 
                  deskripsi = '$deskripsi' 
                  $gambar_sql
                  WHERE id = $id";
        
        if ($db->query($query)) {
            setFlash('success', 'Hadiah berhasil diperbarui!');
        } else {
            setFlash('error', 'Gagal memperbarui hadiah: ' . $db->error);
        }
        
        header("Location: hadiah-poin.php");
        exit();
    }
    
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        // Optional: Hapus file gambar jika perlu
        
        $query = "DELETE FROM hadiah_poin WHERE id = $id";
        
        if ($db->query($query)) {
            setFlash('success', 'Hadiah berhasil dihapus!');
        } else {
            setFlash('error', 'Gagal menghapus hadiah: ' . $db->error);
        }
        
        header("Location: hadiah-poin.php");
        exit();
    }
}

// Get all rewards
$query = "SELECT * FROM hadiah_poin ORDER BY poin_dibutuhkan ASC";
$result = $db->query($query);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Hadiah Poin - Admin</title>
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
                <h1 class="text-3xl font-bold text-gray-800">Manajemen Hadiah Poin</h1>
                <p class="text-gray-600">Kelola katalog hadiah penukaran poin</p>
            </div>
            <button onclick="showAddModal()" class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg font-semibold hover:shadow-xl transition">
                <i class="fas fa-plus mr-2"></i>Tambah Hadiah
            </button>
        </div>
        
        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg mb-6">
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>
        
        <!-- Rewards List -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">Gambar</th>
                            <th class="px-6 py-4 text-left">Nama Hadiah</th>
                            <th class="px-6 py-4 text-center">Poin Dibutuhkan</th>
                            <th class="px-6 py-4 text-center">Stok</th>
                            <th class="px-6 py-4 text-left">Deskripsi</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($hadiah = $result->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <?php if ($hadiah['gambar_url']): ?>
                                    <img src="../<?= htmlspecialchars($hadiah['gambar_url']) ?>" alt="Reward" class="w-16 h-16 object-cover rounded-lg shadow-sm">
                                    <?php else: ?>
                                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400">
                                        <i class="fas fa-image text-xl"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-gray-800"><?= htmlspecialchars($hadiah['nama_hadiah']) ?></p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full font-bold text-sm">
                                        <i class="fas fa-coins mr-1"></i><?= number_format($hadiah['poin_dibutuhkan'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="<?= $hadiah['stok'] > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> px-3 py-1 rounded-full font-bold text-sm">
                                        <?= number_format($hadiah['stok'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                                    <?= htmlspecialchars($hadiah['deskripsi']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-2">
                                        <button onclick='showEditModal(<?= json_encode($hadiah) ?>)' class="bg-blue-500 text-white px-3 py-2 rounded-lg hover:bg-blue-600 transition" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Hapus hadiah ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $hadiah['id'] ?>">
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
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    Belum ada hadiah poin. Silakan tambahkan hadiah baru.
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
            <h3 class="text-2xl font-bold mb-6">Tambah Hadiah Baru</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">Nama Hadiah *</label>
                        <input type="text" name="nama_hadiah" required class="w-full px-4 py-2 border rounded-lg" placeholder="Contoh: Voucher Belanja 50rb">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Poin Dibutuhkan *</label>
                        <input type="number" name="poin_dibutuhkan" required min="1" class="w-full px-4 py-2 border rounded-lg" placeholder="100">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Stok Awal *</label>
                        <input type="number" name="stok" required min="0" class="w-full px-4 py-2 border rounded-lg" placeholder="10">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">Gambar Hadiah</label>
                        <input type="file" name="gambar" accept="image/*" class="w-full px-4 py-2 border rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, GIF (Max 2MB)</p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">Deskripsi</label>
                        <textarea name="deskripsi" rows="3" class="w-full px-4 py-2 border rounded-lg" placeholder="Deskripsi singkat hadiah..."></textarea>
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

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
        <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-4 my-8">
            <h3 class="text-2xl font-bold mb-6">Edit Hadiah</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">Nama Hadiah *</label>
                        <input type="text" name="nama_hadiah" id="edit_nama" required class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Poin Dibutuhkan *</label>
                        <input type="number" name="poin_dibutuhkan" id="edit_poin" required min="1" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Stok *</label>
                        <input type="number" name="stok" id="edit_stok" required min="0" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">Ganti Gambar (Opsional)</label>
                        <input type="file" name="gambar" accept="image/*" class="w-full px-4 py-2 border rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">Biarkan kosong jika tidak ingin mengubah gambar</p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" rows="3" class="w-full px-4 py-2 border rounded-lg"></textarea>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-blue-500 text-white py-3 rounded-lg font-semibold hover:shadow-xl transition">
                        Simpan Perubahan
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

        function showEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama').value = data.nama_hadiah;
            document.getElementById('edit_poin').value = data.poin_dibutuhkan;
            document.getElementById('edit_stok').value = data.stok;
            document.getElementById('edit_deskripsi').value = data.deskripsi;
            
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        // Close modals on outside click
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
