<?php
$activePage = 'setting-point'; // Nama halaman aktif untuk sidebar

// Memastikan file konfigurasi yang diperlukan tersedia
require_once '../config/database.php';
require_once '../config/session.php';

// Memastikan hanya admin yang dapat mengakses halaman ini
requireAdmin();

$db = getDB();

// --- Logika Penanganan POST (Simpan Pengaturan) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengambil dan membersihkan input dari form
    $min_belanja = (float)$_POST['min_belanja_dapat_poin'];
    $jumlah_poin = (int)$_POST['jumlah_poin_didapat'];
    $nilai_tukar = (float)$_POST['nilai_tukar_satu_poin'];
    
    // Validasi dasar: Jumlah Poin Didapat dan Nilai Tukar harus lebih besar dari 0
    if ($jumlah_poin > 0 && $nilai_tukar > 0 && $min_belanja >= 0) {
        // Query UPDATE untuk memperbarui pengaturan di baris dengan id = 1
        // Diasumsikan hanya ada satu baris pengaturan di tabel setting_poin
        $query = "UPDATE setting_poin SET 
                  min_belanja_dapat_poin = $min_belanja,
                  jumlah_poin_didapat = $jumlah_poin,
                  nilai_tukar_satu_poin = $nilai_tukar
                  WHERE id = 1";

        if ($db->query($query)) {
            setFlash('success', 'Pengaturan Poin berhasil diperbarui! 🎉');
        } else {
            // Set pesan error jika query gagal
            setFlash('error', 'Gagal memperbarui Pengaturan Poin! Silakan coba lagi.');
        }
    } else {
        // Set pesan error jika validasi gagal
        setFlash('error', 'Gagal memperbarui Pengaturan Poin! Pastikan "Jumlah Poin Didapat" dan "Nilai Tukar 1 Poin" lebih besar dari 0, dan "Minimum Belanja" tidak negatif.');
    }

    // Redirect untuk mencegah form resubmission
    header("Location: setting-point.php");
    exit();
}

// --- Logika Ambil Pengaturan Saat Ini ---
$setting_poin = [];
$query_setting = "SELECT * FROM setting_poin WHERE id = 1";
$result_setting = $db->query($query_setting);

if ($result_setting && $result_setting->num_rows > 0) {
    $setting_poin = $result_setting->fetch_assoc();
} else {
    // Nilai default jika data di database tidak ditemukan
    $setting_poin = [
        'min_belanja_dapat_poin' => 10000.00,
        'jumlah_poin_didapat' => 1,
        'nilai_tukar_satu_poin' => 100.00
    ];
    setFlash('warning', 'Data Pengaturan Poin tidak ditemukan di database. Nilai default digunakan.');
}

// Ambil pesan flash
$flash = getFlash();

// Ekstrak variabel untuk kemudahan di HTML
$min_belanja_dapat_poin = $setting_poin['min_belanja_dapat_poin'];
$jumlah_poin_didapat = $setting_poin['jumlah_poin_didapat'];
$nilai_tukar_satu_poin = $setting_poin['nilai_tukar_satu_poin'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Poin - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include 'includes/admin-styles.php'; ?>
</head>
<body class="bg-gray-100">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content p-4 md:p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Pengaturan Poin Member</h1>
                <p class="text-gray-600">Atur skema perolehan dan penukaran poin untuk pelanggan</p>
            </div>
        </div>
        
        <?php if ($flash): ?>
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'warning' ? 'yellow' : 'red') ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'warning' ? 'yellow' : 'red') ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'warning' ? 'yellow' : 'red') ?>-700 px-4 py-3 rounded-lg mb-6">
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 max-w-3xl">
            <h2 class="text-xl font-bold text-gray-800 mb-6">
                <i class="fas fa-cog text-purple-600 mr-2"></i>
                Skema Poin Pelanggan
            </h2>
            
            <form method="POST" action="">
                
                <h3 class="text-lg font-semibold text-purple-700 mb-3 border-b pb-2">Perolehan Poin (Reward) 🎁</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Minimum Belanja untuk Dapat Poin (Rp) *
                        </label>
                        <input 
                            type="number" 
                            name="min_belanja_dapat_poin" 
                            required 
                            min="0"
                            step="1" 
                            value="<?= number_format($min_belanja_dapat_poin, 0, '.', '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        >
                        <p class="text-sm text-gray-500 mt-1">Belanja minimal sejumlah ini (atau kelipatannya) akan mendapatkan poin.</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Jumlah Poin yang Didapat *
                        </label>
                        <input 
                            type="number" 
                            name="jumlah_poin_didapat" 
                            required 
                            min="1"
                            step="1" 
                            value="<?= (int)$jumlah_poin_didapat ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        >
                        <p class="text-sm text-gray-500 mt-1">Jumlah poin yang didapat per kelipatan minimum belanja.</p>
                    </div>
                </div>
                
                <h3 class="text-lg font-semibold text-pink-700 mb-3 mt-6 border-b pb-2">Penukaran Poin (Redeem) 💰</h3>
                <div class="mb-6 max-w-md">
                    <label class="block text-gray-700 font-semibold mb-2">
                        Nilai Tukar 1 Poin (Rp) *
                    </label>
                    <input 
                        type="number" 
                        name="nilai_tukar_satu_poin" 
                        required 
                        min="1"
                        step="1" 
                        value="<?= number_format($nilai_tukar_satu_poin, 0, '.', '') ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                    >
                    <p class="text-sm text-gray-500 mt-1">Nilai Rupiah untuk setiap 1 poin yang ditukarkan (misal: 100 = Rp 100).</p>
                </div>

                <div class="flex justify-end pt-4 border-t">
                    <button type="submit" class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-8 py-3 rounded-lg font-semibold hover:shadow-xl transition">
                        <i class="fas fa-save mr-2"></i>Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
        
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-lg mt-8 max-w-3xl">
            <p class="font-bold mb-2">Contoh Skema Aktif:</p>
            <p class="text-sm">
                Dengan pengaturan saat ini:
                <br>
                1. Setiap kelipatan belanja **Rp <?= number_format($min_belanja_dapat_poin, 0, ',', '.') ?>** akan mendapatkan **<?= $jumlah_poin_didapat ?> Poin**.
                <br>
                2. Setiap **1 Poin** yang ditukarkan bernilai diskon **Rp <?= number_format($nilai_tukar_satu_poin, 0, ',', '.') ?>**.
                <br>
                3. Jadi, jika pelanggan mengumpulkan **100 Poin**, mereka dapat menukarkannya dengan diskon **Rp <?= number_format(100 * $nilai_tukar_satu_poin, 0, ',', '.') ?>**.
            </p>
        </div>
        
    </div>
    
</body>
</html>