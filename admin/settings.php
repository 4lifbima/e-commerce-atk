<?php
$activePage = 'settings';
require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

$db = getDB();

// Handle update harga fotocopy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_fotocopy') {
        $id = (int)$_POST['id'];
        $harga = (float)$_POST['harga'];
        
        $query = "UPDATE harga_fotocopy SET harga = $harga WHERE id = $id";
        
        if ($db->query($query)) {
            setFlash('success', 'Harga fotocopy berhasil diupdate!');
        } else {
            setFlash('error', 'Gagal update harga fotocopy!');
        }
        
        header("Location: settings.php");
        exit();
    }
    
    if ($_POST['action'] === 'update_jilid') {
        $id = (int)$_POST['id'];
        $harga = (float)$_POST['harga'];
        
        $query = "UPDATE harga_jilid SET harga = $harga WHERE id = $id";
        
        if ($db->query($query)) {
            setFlash('success', 'Harga jilid berhasil diupdate!');
        } else {
            setFlash('error', 'Gagal update harga jilid!');
        }
        
        header("Location: settings.php");
        exit();
    }
}

// Get harga fotocopy
$query_fotocopy = "SELECT * FROM harga_fotocopy ORDER BY jenis_kertas, warna, bolak_balik";
$result_fotocopy = $db->query($query_fotocopy);

// Get harga jilid
$query_jilid = "SELECT * FROM harga_jilid ORDER BY id";
$result_jilid = $db->query($query_jilid);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include 'includes/admin-styles.php'; ?>
</head>
<body class="bg-gray-100">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content p-4 md:p-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Pengaturan</h1>
            <p class="text-gray-600">Kelola harga fotocopy dan jilid</p>
        </div>
        
        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg mb-6">
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Harga Fotocopy -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-print text-purple-600 mr-2"></i>
                        Harga Fotocopy
                    </h2>
                    <span class="bg-purple-100 text-purple-600 px-3 py-1 rounded-full text-xs font-semibold">
                        Per Lembar
                    </span>
                </div>
                
                <div class="space-y-4">
                    <?php while ($harga = $result_fotocopy->fetch_assoc()): ?>
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-4 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="bg-purple-600 text-white px-2 py-1 rounded text-xs font-bold">
                                        <?= $harga['jenis_kertas'] ?>
                                    </span>
                                    <span class="bg-<?= $harga['warna'] === 'Berwarna' ? 'pink' : 'gray' ?>-600 text-white px-2 py-1 rounded text-xs font-bold">
                                        <?= $harga['warna'] ?>
                                    </span>
                                    <?php if ($harga['bolak_balik']): ?>
                                    <span class="bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">
                                        2 Sisi
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-600">
                                    <?= $harga['jenis_kertas'] ?> - 
                                    <?= $harga['warna'] ?> 
                                    <?= $harga['bolak_balik'] ? '(Bolak-Balik)' : '(Satu Sisi)' ?>
                                </p>
                            </div>
                        </div>
                        
                        <form method="POST" action="" class="flex gap-2">
                            <input type="hidden" name="action" value="update_fotocopy">
                            <input type="hidden" name="id" value="<?= $harga['id'] ?>">
                            <div class="flex-1">
                                <input 
                                    type="number" 
                                    name="harga" 
                                    value="<?= $harga['harga'] ?>"
                                    min="0"
                                    step="50"
                                    required
                                    class="w-full px-4 py-2 border-2 border-purple-300 rounded-lg focus:outline-none focus:border-purple-600 font-semibold text-purple-600"
                                >
                            </div>
                            <button 
                                type="submit"
                                class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition"
                                title="Update">
                                <i class="fas fa-save"></i>
                            </button>
                        </form>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Info -->
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 text-xl mr-3 mt-1"></i>
                        <div>
                            <p class="font-semibold text-blue-800 mb-1">Informasi</p>
                            <p class="text-sm text-blue-700">
                                Harga fotocopy akan otomatis dikalkulasi pada form pemesanan fotocopy.
                                Pastikan harga yang diinput sudah sesuai.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Harga Jilid -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-book text-green-600 mr-2"></i>
                        Harga Jilid
                    </h2>
                    <span class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-xs font-semibold">
                        Per Dokumen
                    </span>
                </div>
                
                <div class="space-y-4">
                    <?php while ($jilid = $result_jilid->fetch_assoc()): ?>
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg"><?= $jilid['jenis_jilid'] ?></h3>
                                <p class="text-sm text-gray-600">
                                    <?php
                                    $desc = [
                                        'Tidak' => 'Tanpa jilid',
                                        'Spiral' => 'Jilid spiral plastik',
                                        'Lakban' => 'Jilid lakban samping',
                                        'Hardcover' => 'Jilid hardcover premium'
                                    ];
                                    echo $desc[$jilid['jenis_jilid']] ?? '';
                                    ?>
                                </p>
                            </div>
                            <i class="fas fa-<?= $jilid['jenis_jilid'] === 'Tidak' ? 'times' : 'book' ?> text-green-600 text-2xl"></i>
                        </div>
                        
                        <form method="POST" action="" class="flex gap-2">
                            <input type="hidden" name="action" value="update_jilid">
                            <input type="hidden" name="id" value="<?= $jilid['id'] ?>">
                            <div class="flex-1">
                                <input 
                                    type="number" 
                                    name="harga" 
                                    value="<?= $jilid['harga'] ?>"
                                    min="0"
                                    step="100"
                                    required
                                    class="w-full px-4 py-2 border-2 border-green-300 rounded-lg focus:outline-none focus:border-green-600 font-semibold text-green-600"
                                >
                            </div>
                            <button 
                                type="submit"
                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition"
                                title="Update">
                                <i class="fas fa-save"></i>
                            </button>
                        </form>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Info -->
                <div class="mt-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-orange-600 text-xl mr-3 mt-1"></i>
                        <div>
                            <p class="font-semibold text-orange-800 mb-1">Perhatian</p>
                            <p class="text-sm text-orange-700">
                                Perubahan harga akan mempengaruhi semua pesanan baru.
                                Pesanan lama tetap menggunakan harga saat pemesanan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <?php
            // Get stats
            $query_total_fotocopy = "SELECT COUNT(*) as total FROM pesanan_fotocopy";
            $total_fotocopy = $db->query($query_total_fotocopy)->fetch_assoc()['total'];
            
            $query_revenue_fotocopy = "SELECT COALESCE(SUM(subtotal), 0) as total FROM pesanan_fotocopy";
            $revenue_fotocopy = $db->query($query_revenue_fotocopy)->fetch_assoc()['total'];
            
            $query_avg_order = "SELECT COALESCE(AVG(subtotal), 0) as avg FROM pesanan_fotocopy";
            $avg_order = $db->query($query_avg_order)->fetch_assoc()['avg'];
            ?>
            
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl p-6">
                <i class="fas fa-print text-3xl mb-2"></i>
                <p class="text-purple-100 text-sm">Total Order Fotocopy</p>
                <h3 class="text-4xl font-bold"><?= number_format($total_fotocopy) ?></h3>
            </div>
            
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl p-6">
                <i class="fas fa-money-bill-wave text-3xl mb-2"></i>
                <p class="text-green-100 text-sm">Total Revenue Fotocopy</p>
                <h3 class="text-2xl font-bold">Rp <?= number_format($revenue_fotocopy, 0, ',', '.') ?></h3>
            </div>
            
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl p-6">
                <i class="fas fa-chart-line text-3xl mb-2"></i>
                <p class="text-blue-100 text-sm">Rata-rata Order</p>
                <h3 class="text-2xl font-bold">Rp <?= number_format($avg_order, 0, ',', '.') ?></h3>
            </div>
        </div>
        
        <!-- Additional Settings Section -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mt-8">
            <h2 class="text-xl font-bold text-gray-800 mb-6">
                <i class="fas fa-cogs text-gray-600 mr-2"></i>
                Pengaturan Lainnya
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Informasi Toko -->
                <div class="p-4 border-2 border-gray-200 rounded-xl">
                    <h3 class="font-bold text-gray-800 mb-3">
                        <i class="fas fa-store text-purple-600 mr-2"></i>
                        Informasi Toko
                    </h3>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-semibold">Nama:</span> Copy&ATK Premium</p>
                        <p><span class="font-semibold">Telepon:</span> 0812-3456-7890</p>
                        <p><span class="font-semibold">Email:</span> info@copyatk.com</p>
                        <p><span class="font-semibold">Alamat:</span> Manado, Sulawesi Utara</p>
                    </div>
                </div>
                
                <!-- Jam Operasional -->
                <div class="p-4 border-2 border-gray-200 rounded-xl">
                    <h3 class="font-bold text-gray-800 mb-3">
                        <i class="fas fa-clock text-blue-600 mr-2"></i>
                        Jam Operasional
                    </h3>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-semibold">Senin - Jumat:</span> 08:00 - 20:00</p>
                        <p><span class="font-semibold">Sabtu:</span> 09:00 - 18:00</p>
                        <p><span class="font-semibold">Minggu:</span> 10:00 - 15:00</p>
                        <p class="text-red-600 font-semibold mt-2">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            Tutup pada hari libur nasional
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>