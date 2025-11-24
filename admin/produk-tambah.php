<?php
/**
 * Admin - Form Tambah Produk
 */

require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

$db = getDB();
$error = '';

// Proses tambah produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori_id = (int)$_POST['kategori_id'];
    $nama_produk = escape($_POST['nama_produk']);
    $deskripsi = escape($_POST['deskripsi']);
    $harga = (float)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    
    // Upload foto
    $foto_name = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $foto_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_path = '../uploads/' . $foto_name;
            
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                $error = 'Gagal upload foto!';
            }
        } else {
            $error = 'Format foto tidak valid! Gunakan JPG, PNG, atau GIF.';
        }
    }
    
    if (!$error) {
        $query = "INSERT INTO produk (kategori_id, nama_produk, deskripsi, harga, stok, foto) 
                  VALUES ($kategori_id, '$nama_produk', '$deskripsi', $harga, $stok, '$foto_name')";
        
        if ($db->query($query)) {
            setFlash('success', 'Produk berhasil ditambahkan!');
            header("Location: produk.php");
            exit();
        } else {
            $error = 'Gagal menambahkan produk!';
        }
    }
}

// Get kategori
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori";
$result_kategori = $db->query($query_kategori);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include 'includes/admin-styles.php'; ?>
</head>
<body class="bg-gray-100">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content p-4 md:p-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="produk.php" class="text-gray-600 hover:text-purple-600">
                    <i class="fas fa-arrow-left text-2xl"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Tambah Produk Baru</h1>
                    <p class="text-gray-600">Isi form di bawah untuk menambahkan produk</p>
                </div>
            </div>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?= $error ?>
        </div>
        <?php endif; ?>
        
        <!-- Form -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nama Produk -->
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">
                            Nama Produk *
                        </label>
                        <input 
                            type="text" 
                            name="nama_produk" 
                            required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                            placeholder="Contoh: Pulpen Pilot G2"
                        >
                    </div>
                    
                    <!-- Kategori -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Kategori *
                        </label>
                        <select 
                            name="kategori_id" 
                            required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        >
                            <option value="">Pilih Kategori</option>
                            <?php while ($kategori = $result_kategori->fetch_assoc()): ?>
                            <option value="<?= $kategori['id'] ?>"><?= $kategori['nama_kategori'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <!-- Harga -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Harga (Rp) *
                        </label>
                        <input 
                            type="number" 
                            name="harga" 
                            required 
                            min="0" 
                            step="0.01"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                            placeholder="5000"
                        >
                    </div>
                    
                    <!-- Stok -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Stok *
                        </label>
                        <input 
                            type="number" 
                            name="stok" 
                            required 
                            min="0"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                            placeholder="100"
                        >
                    </div>
                    
                    <!-- Foto -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Foto Produk
                        </label>
                        <input 
                            type="file" 
                            name="foto" 
                            accept="image/*"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        >
                        <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, GIF (Max 2MB)</p>
                    </div>
                    
                    <!-- Deskripsi -->
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">
                            Deskripsi
                        </label>
                        <textarea 
                            name="deskripsi" 
                            rows="4"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                            placeholder="Deskripsi produk..."
                        ></textarea>
                    </div>
                </div>
                
                <!-- Buttons -->
                <div class="flex gap-4 mt-8">
                    <button 
                        type="submit"
                        class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-semibold hover:shadow-xl transition"
                    >
                        <i class="fas fa-save mr-2"></i>
                        Simpan Produk
                    </button>
                    <a 
                        href="produk.php"
                        class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition text-center"
                    >
                        <i class="fas fa-times mr-2"></i>
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>