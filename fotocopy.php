<?php
/**
 * Halaman Pemesanan Fotocopy
 * Form interaktif dengan kalkulasi otomatis biaya
 */

require_once 'config/database.php';
require_once 'config/session.php';

$db = getDB();

// Ambil data user jika sudah login
$user_data = null;
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $query_user = "SELECT * FROM users WHERE id = $user_id";
    $result_user = $db->query($query_user);
    if ($result_user && $result_user->num_rows > 0) {
        $user_data = $result_user->fetch_assoc();
    }
}

// Ambil data harga fotocopy
$query_harga = "SELECT * FROM harga_fotocopy ORDER BY jenis_kertas, warna, bolak_balik";
$harga_fotocopy = $db->query($query_harga);

// Ambil data harga jilid
$query_jilid = "SELECT * FROM harga_jilid";
$harga_jilid = $db->query($query_jilid);

// Convert ke array untuk JavaScript
$harga_array = [];
while ($row = $harga_fotocopy->fetch_assoc()) {
    $key = $row['jenis_kertas'] . '_' . $row['warna'] . '_' . $row['bolak_balik'];
    $harga_array[$key] = $row['harga'];
}

$jilid_array = [];
while ($row = $harga_jilid->fetch_assoc()) {
    $jilid_array[$row['jenis_jilid']] = $row['harga'];
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Fotocopy - Copy&ATK Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-[#f9f9fb] min-h-screen">
    <!-- Background Blur Orbs -->
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-purple-400 rounded-full opacity-30 filter blur-3xl animate-blob"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-pink-400 rounded-full opacity-30 filter blur-3xl animate-blob animation-delay-2000"></div>
        <div class="absolute top-1/2 left-1/2 w-96 h-96 bg-blue-400 rounded-full opacity-30 filter blur-3xl animate-blob animation-delay-4000"></div>
    </div>
         
    <!-- Simple Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-2">
                        <i class="fas fa-print text-[#400dd9] text-2xl"></i>
                        <span class="text-xl font-bold text-[#400dd9]">
                            Copy&ATK Premium
                        </span>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-[#400dd9] transition">Beranda</a>
                    <a href="produk.php" class="text-gray-700 hover:text-[#400dd9] transition">Produk</a>
                    <a href="fotocopy.php" class="text-gray-700 hover:text-[#400dd9] transition">Layanan Fotocopy</a>
                    
                    <a href="cart.php" class="relative text-gray-700 hover:text-[#400dd9] transition">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <?php if (getCartCount() > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            <?= getCartCount() ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    
                    <?php if (isLoggedIn()): ?>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-gray-700 hover:text-[#400dd9]">
                                <i class="fas fa-user-circle text-xl"></i>
                                <span><?= $_SESSION['nama'] ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                                <?php if (isAdmin()): ?>
                                <a href="admin/dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Dashboard Admin</a>
                                <?php else: ?>
                                <a href="pesanan-saya.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Pesanan Saya</a>
                                <?php endif; ?>
                                <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="bg-[#400dd9] text-white px-6 py-2 rounded-full hover:bg-[#350ba1] transition shadow-md">
                            Login
                        </a>
                    <?php endif; ?>
                </div>
                
                <button id="mobile-menu-btn" class="md:hidden text-gray-700">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t">
            <div class="px-4 py-3 space-y-3">
                <a href="index.php" class="block text-gray-700 hover:text-[#400dd9]">Beranda</a>
                <a href="produk.php" class="block text-gray-700 hover:text-[#400dd9]">Produk</a>
                <a href="fotocopy.php" class="block text-gray-700 hover:text-[#400dd9]">Layanan Fotocopy</a>
                <a href="cart.php" class="block text-gray-700 hover:text-[#400dd9]">Keranjang (<?= getCartCount() ?>)</a>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php" class="block text-gray-700 hover:text-[#400dd9]">Dashboard Admin</a>
                    <?php else: ?>
                    <a href="pesanan-saya.php" class="block text-gray-700 hover:text-[#400dd9]">Pesanan Saya</a>
                    <?php endif; ?>
                    <a href="logout.php" class="block text-red-600">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="block text-[#400dd9] font-semibold">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Flash Message -->
    <?php if ($flash): ?>
    <div class="max-w-4xl mx-auto px-4 mt-4">
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg">
            <?= $flash['message'] ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Hero Section -->
    <div class="bg-[#400dd9] text-white py-12">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                <i class="fas fa-print mr-3"></i>
                Layanan Fotocopy Profesional
            </h1>
            <p class="text-xl text-purple-100">
                Hasil berkualitas tinggi, harga terjangkau, proses cepat!
            </p>
        </div>
    </div>
    
    <!-- Form Pemesanan -->
    <div class="max-w-4xl mx-auto px-4 py-12">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <form id="fotocopyForm" method="POST" action="fotocopy-process.php" enctype="multipart/form-data">
                
                <!-- Data Customer -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold mb-4 text-gray-800 border-b pb-2">
                        <i class="fas fa-user text-purple-600 mr-2"></i>
                        Data Pemesan
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Nama Lengkap *</label>
                            <input type="text" name="nama" required 
                                   value="<?= isLoggedIn() ? $_SESSION['nama'] : '' ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Email</label>
                            <input type="email" name="email" 
                
                <!-- Spesifikasi Fotocopy -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold mb-4 text-gray-800 border-b pb-2">
                        <i class="fas fa-file-alt text-purple-600 mr-2"></i>
                        Spesifikasi Fotocopy
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Jumlah Lembar -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Jumlah Lembar *</label>
                            <input type="number" id="jumlah_lembar" name="jumlah_lembar" min="1" value="1" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                        </div>
                        
                        <!-- Jenis Kertas -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Jenis Kertas *</label>
                            <select id="jenis_kertas" name="jenis_kertas" required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                                <option value="A4">A4 (21 x 29.7 cm)</option>
                                <option value="F4">F4 (21.5 x 33 cm)</option>
                                <option value="A3">A3 (29.7 x 42 cm)</option>
                            </select>
                        </div>
                        
                        <!-- Warna -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Jenis Cetakan *</label>
                            <select id="warna" name="warna" required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                                <option value="Hitam Putih">Hitam Putih</option>
                                <option value="Berwarna">Berwarna (Full Color)</option>
                            </select>
                        </div>
                        
                        <!-- Bolak Balik -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Cetak Bolak-Balik?</label>
                            <select id="bolak_balik" name="bolak_balik" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                                <option value="0">Tidak (Satu Sisi)</option>
                                <option value="1">Ya (Dua Sisi)</option>
                            </select>
                        </div>
                        
                        <!-- Jilid -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Jilid</label>
                            <select id="jilid" name="jilid" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                                <option value="Tidak">Tidak Perlu Jilid</option>
                                <option value="Spiral">Spiral (Rp 5.000)</option>
                                <option value="Lakban">Lakban (Rp 3.000)</option>
                                <option value="Hardcover">Hardcover (Rp 15.000)</option>
                            </select>
                        </div>
                        
                        <!-- Upload File (Optional) -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">
                                Upload File (Optional)
                                <span class="text-xs text-gray-500 font-normal">PDF, Max 10MB</span>
                            </label>
                            <input type="file" name="file_dokumen" accept=".pdf" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                        </div>
                    </div>
                    
                    <!-- Keterangan Tambahan -->
                    <div class="mt-4">
                        <label class="block text-gray-700 font-semibold mb-2">Keterangan Tambahan</label>
                        <textarea name="keterangan" rows="3" 
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                                  placeholder="Misal: ukuran margin, perlu distaples, dll"></textarea>
                    </div>
                </div>
                
                <!-- Ringkasan Biaya -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl p-6 mb-6">
                    <h2 class="text-2xl font-bold mb-4 text-gray-800">
                        <i class="fas fa-calculator text-purple-600 mr-2"></i>
                        Ringkasan Biaya
                    </h2>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Biaya Fotocopy:</span>
                            <span id="biaya_fotocopy" class="font-semibold text-lg">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Biaya Jilid:</span>
                            <span id="biaya_jilid" class="font-semibold text-lg">Rp 0</span>
                        </div>
                        <div class="border-t border-purple-200 pt-3 mt-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xl font-bold text-gray-800">Total Biaya:</span>
                                <span id="total_biaya" class="text-3xl font-bold text-purple-600">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Metode Pembayaran -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-3">Metode Pembayaran *</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-purple-600 transition">
                            <input type="radio" name="metode_pembayaran" value="COD" required class="mr-3 w-5 h-5 text-purple-600">
                            <div>
                                <p class="font-semibold">Bayar di Tempat (COD)</p>
                                <p class="text-sm text-gray-600">Bayar saat pengambilan</p>
                            </div>
                        </label>
                        <label class="flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-purple-600 transition">
                            <input type="radio" name="metode_pembayaran" value="Transfer" class="mr-3 w-5 h-5 text-purple-600">
                            <div>
                                <p class="font-semibold">Transfer Bank</p>
                                <p class="text-sm text-gray-600">Konfirmasi setelah transfer</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="flex gap-4">
                    <button type="submit" 
                            class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-2xl transition">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Kirim Pesanan
                    </button>
                    <a href="index.php" 
                       class="flex-1 bg-gray-200 text-gray-700 py-4 rounded-xl font-bold text-lg hover:bg-gray-300 transition text-center">
                        <i class="fas fa-times mr-2"></i>
                        Batal
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Info Section -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-clock text-purple-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-lg mb-2">Proses Cepat</h3>
                <p class="text-gray-600 text-sm">Selesai dalam 1-2 jam</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-lg mb-2">Kualitas Terjamin</h3>
                <p class="text-gray-600 text-sm">Hasil tajam & jernih</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-dollar-sign text-blue-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-lg mb-2">Harga Terjangkau</h3>
                <p class="text-gray-600 text-sm">Mulai Rp 200/lembar</p>
            </div>
        </div>
    </div>
    
    <!-- JavaScript untuk Kalkulasi -->
    <script>
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        // Data harga dari PHP
        const hargaFotocopy = <?= json_encode($harga_array) ?>;
        const hargaJilid = <?= json_encode($jilid_array) ?>;
        
        // Elements
        const jumlahLembar = document.getElementById('jumlah_lembar');
        const jenisKertas = document.getElementById('jenis_kertas');
        const warna = document.getElementById('warna');
        const bolakBalik = document.getElementById('bolak_balik');
        const jilid = document.getElementById('jilid');
        
        const biayaFotocopyEl = document.getElementById('biaya_fotocopy');
        const biayaJilidEl = document.getElementById('biaya_jilid');
        const totalBiayaEl = document.getElementById('total_biaya');
        
        // Function untuk format rupiah
        function formatRupiah(angka) {
            return 'Rp ' + angka.toLocaleString('id-ID');
        }
        
                // Mobile Menu Toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Function untuk kalkulasi
        function hitungBiaya() {
            const jml = parseInt(jumlahLembar.value) || 0;
            const kertas = jenisKertas.value;
            const wrn = warna.value;
            const bb = bolakBalik.value;
            const jld = jilid.value;
            
            // Hitung biaya fotocopy
            const key = kertas + '_' + wrn + '_' + bb;
            const hargaPerLembar = hargaFotocopy[key] || 0;
            const biayaFC = jml * hargaPerLembar;
            
            // Hitung biaya jilid
            const biayaJLD = hargaJilid[jld] || 0;
            
            // Total
            const total = biayaFC + biayaJLD;
            
            // Update display
            biayaFotocopyEl.textContent = formatRupiah(biayaFC);
            biayaJilidEl.textContent = formatRupiah(biayaJLD);
            totalBiayaEl.textContent = formatRupiah(total);
        }
        
        // Event listeners
        jumlahLembar.addEventListener('input', hitungBiaya);
        jenisKertas.addEventListener('change', hitungBiaya);
        warna.addEventListener('change', hitungBiaya);
        bolakBalik.addEventListener('change', hitungBiaya);
        jilid.addEventListener('change', hitungBiaya);
        
        // Hitung pertama kali
        hitungBiaya();
    </script>
</body>
</html>