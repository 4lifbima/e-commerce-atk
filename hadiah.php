<?php
/**
 * Halaman Katalog Hadiah Poin (Customer/Member)
 * Digunakan untuk menampilkan daftar hadiah dan memungkinkan penukaran poin.
 */

// Menggunakan path yang Anda sediakan
require_once 'config/database.php';
require_once 'config/session.php';

// Pastikan user sudah login sebagai customer
requireLogin('login.php');
if (!isCustomer()) {
    // Jika login tapi bukan customer, arahkan ke dashboard yang sesuai (atau login)
    header("Location: dashboard.php"); 
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Variabel untuk menyimpan pesan flash
$success_message = getFlashMessage('success');
$error_message = getFlashMessage('error');

// --- Fungsi Helper ---

/**
 * Mendapatkan poin saat ini milik user
 * @param mysqli $conn
 * @param int $user_id
 * @return int Poin user
 */
function get_user_points($conn, $user_id) {
    $poin = 0;
    // Query yang aman dengan prepared statement
    $stmt = $conn->prepare("SELECT poin FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $poin = (int)$row['poin'];
    }
    $stmt->close();
    return $poin;
}

/**
 * Mendapatkan semua data hadiah
 * @param mysqli $conn
 * @return array Daftar hadiah
 */
function get_all_rewards($conn) {
    $hadiah = [];
    $query = "SELECT id, nama_hadiah, poin_dibutuhkan, stok, gambar_url, deskripsi FROM hadiah_poin ORDER BY poin_dibutuhkan ASC";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $hadiah[] = $row;
        }
    }
    return $hadiah;
}

// --- LOGIKA PENUKARAN ---

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'redeem') {
    $reward_id = (int)($_POST['reward_id'] ?? 0);
    $poin_user = get_user_points($conn, $user_id);

    // Mulai Transaksi
    $conn->begin_transaction();
    try {
        if ($reward_id <= 0) {
             throw new Exception("Permintaan penukaran tidak valid.");
        }

        // Mengunci baris hadiah untuk mencegah race condition (FOR UPDATE)
        $stmt = $conn->prepare("SELECT nama_hadiah, poin_dibutuhkan, stok FROM hadiah_poin WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $reward_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reward = $result->fetch_assoc();
        $stmt->close();

        if (!$reward) {
            throw new Exception("Hadiah tidak ditemukan.");
        }

        $poin_dibutuhkan = (int)$reward['poin_dibutuhkan'];
        $stok = (int)$reward['stok'];
        $nama_hadiah = $reward['nama_hadiah'];

        if ($stok <= 0) {
            throw new Exception("Maaf, stok hadiah '{$nama_hadiah}' telah habis.");
        } elseif ($poin_user < $poin_dibutuhkan) {
            throw new Exception("Poin Anda ({$poin_user}) tidak cukup untuk menukar hadiah '{$nama_hadiah}' ({$poin_dibutuhkan} Poin).");
        }

        // 1. Kurangi Poin User
        $new_poin = $poin_user - $poin_dibutuhkan;
        $stmt_poin = $conn->prepare("UPDATE users SET poin = ? WHERE id = ?");
        $stmt_poin->bind_param("ii", $new_poin, $user_id);
        $stmt_poin->execute();
        $stmt_poin->close();

        // 2. Kurangi Stok Hadiah
        $new_stok = $stok - 1;
        $stmt_stok = $conn->prepare("UPDATE hadiah_poin SET stok = ? WHERE id = ?");
        $stmt_stok->bind_param("ii", $new_stok, $reward_id);
        $stmt_stok->execute();
        $stmt_stok->close();

        // 3. Catat Riwayat Penukaran
        $keterangan = "Penukaran Hadiah: {$nama_hadiah} (ID Hadiah: {$reward_id})";
        $tipe_keluar = 'Keluar'; 
        $stmt_history = $conn->prepare("INSERT INTO history_poin (user_id, jumlah_poin, jenis, keterangan) VALUES (?, ?, ?, ?)");
        $stmt_history->bind_param("iiss", $user_id, $poin_dibutuhkan, $tipe_keluar, $keterangan);
        $stmt_history->execute();
        $stmt_history->close();

        // Commit Transaksi
        $conn->commit();
        setFlashMessage('success', "Selamat! Anda berhasil menukar '{$nama_hadiah}'. Poin Anda saat ini: " . number_format($new_poin, 0, ',', '.'));

    } catch (Exception $e) {
        $conn->rollback();
        // Set pesan error
        setFlashMessage('error', "Gagal menukar hadiah: " . $e->getMessage());
    }
    
    // Redirect untuk menghindari resubmit form
    header("Location: hadiah.php");
    exit();
}

// Ambil data terbaru
$poin_saya = get_user_points($conn, $user_id);
$hadiah_list = get_all_rewards($conn); 
$db->close();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Hadiah Poin</title>
    <!-- Memuat Tailwind CSS melalui CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'primary-blue': '#1e40af', // Biru tua
                        'secondary-yellow': '#fcd34d', // Kuning/emas aksen
                        'light-bg': '#f3f4f6', // Latar belakang
                        'text-dark': '#1f2937', // Teks gelap
                    }
                }
            }
        }
    </script>
    <!-- Pastikan font Inter dimuat -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light-bg">

    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center space-x-2">
                    <i class="fas fa-print text-purple-600 text-2xl"></i>
                    <span class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                        Copy&ATK Premium
                    </span>
                </a>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-purple-600 transition">Beranda</a>
                    <a href="produk.php" class="text-gray-700 hover:text-purple-600 transition">Produk</a>
                    <a href="fotocopy.php" class="text-gray-700 hover:text-purple-600 transition">Layanan Fotocopy</a>
                    <a href="cart.php" class="relative text-gray-700 hover:text-purple-600 transition">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <?php if (getCartCount() > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            <?= getCartCount() ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-700 hover:text-purple-600">
                            <i class="fas fa-user-circle text-xl"></i>
                            <span><?= $_SESSION['nama'] ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                            <a href="pesanan-saya.php" class="block px-4 py-2 text-purple-600 hover:bg-purple-50 font-semibold">
                                <i class="fas fa-shopping-bag mr-2"></i>Pesanan Saya
                            </a>
                            <a href="hadiah.php" class="block px-4 py-2 text-gray-700 bg-purple-50">
                                <i class="fas fa-gift mr-2"></i>Tukar Hadiah
                            </a>
                            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="md:hidden text-gray-700">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t">
            <div class="px-4 py-3 space-y-3">
                <a href="index.php" class="block text-gray-700">Beranda</a>
                <a href="produk.php" class="block text-gray-700">Produk</a>
                <a href="cart.php" class="block text-gray-700">Keranjang (<?= getCartCount() ?>)</a>
                <a href="pesanan-saya.php" class="block text-purple-600 font-semibold">Pesanan Saya</a>
                <a href="hadiah.php" class="block text-gray-700">Tukar Hadiah</a>
                <a href="logout.php" class="block text-red-600">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Konten Utama -->
    <main class="p-6 max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold text-text-dark mb-6 border-b pb-2">Tukar Poin dengan Hadiah Menarik</h2>

        <!-- Notifikasi (Consistent with alert styling) -->
        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-6 shadow-md" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-md" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Grid Hadiah (Responsive Grid) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php if (count($hadiah_list) > 0): ?>
                <?php foreach ($hadiah_list as $hadiah): ?>
                    <?php
                        $is_available = $hadiah['stok'] > 0;
                        $is_redeemable = $poin_saya >= $hadiah['poin_dibutuhkan'] && $is_available;
                        // Menggunakan shadow yang lebih tebal pada hadiah yang bisa ditukar
                        $card_class = $is_redeemable ? 'border-primary-blue/50 shadow-lg hover:shadow-xl transform hover:scale-[1.01]' : 'border-gray-300 opacity-80';
                        $button_class = $is_redeemable ? 'bg-secondary-yellow text-text-dark hover:bg-yellow-400 shadow-md' : 'bg-gray-200 text-gray-500 cursor-not-allowed';
                    ?>
                    <!-- Card Hadiah (Menggunakan rounded-2xl untuk konsistensi) -->
                    <div class="bg-white rounded-2xl border-2 p-4 flex flex-col transition duration-300 <?php echo $card_class; ?>">
                        
                        <!-- Gambar Hadiah -->
                        <img src="<?php echo htmlspecialchars($hadiah['gambar_url'] ?: 'https://placehold.co/400x250/fcd34d/1f2937?text=REWARD'); ?>" 
                             alt="<?php echo htmlspecialchars($hadiah['nama_hadiah']); ?>" 
                             class="w-full h-40 object-cover rounded-xl mb-4 shadow-sm">
                        
                        <h3 class="text-lg font-bold text-text-dark mb-1 h-12 overflow-hidden"><?php echo htmlspecialchars($hadiah['nama_hadiah']); ?></h3>
                        <p class="text-sm text-gray-500 mb-3 line-clamp-2 h-10"><?php echo htmlspecialchars($hadiah['deskripsi'] ?: 'Hadiah menarik untuk Anda.'); ?></p>

                        <!-- Poin Dibutuhkan & Stok -->
                        <div class="flex items-center justify-between mt-auto mb-3 border-t pt-3">
                            <span class="text-xl font-extrabold text-primary-blue flex items-center">
                                <!-- Icon Emas untuk Poin -->
                                <i data-lucide="gem" class="w-5 h-5 mr-1 fill-secondary-yellow text-secondary-yellow"></i>
                                <?php echo number_format($hadiah['poin_dibutuhkan'], 0, ',', '.'); ?>
                            </span>
                            <span class="text-sm font-medium px-2 py-1 rounded-full <?php echo $is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                Stok: <?php echo $is_available ? number_format($hadiah['stok'], 0, ',', '.') : 'Habis'; ?>
                            </span>
                        </div>

                        <!-- Tombol Tukar (Menggunakan rounded-2xl untuk konsistensi) -->
                        <?php if ($is_redeemable): ?>
                            <button onclick="confirmRedeem(<?php echo $hadiah['id']; ?>, '<?php echo htmlspecialchars(addslashes($hadiah['nama_hadiah'])); ?>', <?php echo $hadiah['poin_dibutuhkan']; ?>)"
                                    class="w-full font-bold py-2 rounded-2xl transition duration-150 transform hover:scale-[1.02] <?php echo $button_class; ?>">
                                Tukar Sekarang
                            </button>
                        <?php else: ?>
                            <button disabled 
                                    class="w-full font-bold py-2 rounded-2xl <?php echo $button_class; ?>">
                                <?php echo $is_available ? 'Poin Tidak Cukup' : 'Stok Habis'; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Empty State (Menggunakan rounded-2xl untuk konsistensi) -->
                <div class="col-span-full text-center py-12 bg-white rounded-2xl shadow-xl border-2 border-gray-100">
                    <i data-lucide="gift" class="w-16 h-16 mx-auto text-gray-400 mb-4 stroke-1"></i>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Belum Ada Hadiah</h3>
                    <p class="text-gray-500">Nantikan hadiah-hadiah menarik yang akan segera hadir!</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; 2025 Copy&ATK Premium. All rights reserved.</p>
        </div>
    </footer>

    <!-- Modal Konfirmasi Penukaran (Menggunakan rounded-2xl untuk konsistensi) -->
    <div id="modal-redeem" class="fixed inset-0 bg-gray-900 bg-opacity-70 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center p-4">
        <div class="relative mx-auto border w-full max-w-sm shadow-2xl rounded-2xl bg-white transform transition-all duration-300 scale-100">
            <div class="p-6 text-center">
                <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-secondary-yellow/30 mb-4">
                    <i data-lucide="gem" class="w-8 h-8 text-primary-blue fill-secondary-yellow"></i>
                </div>
                <h3 class="text-xl leading-7 font-bold text-gray-900 mt-2">Konfirmasi Penukaran</h3>
                <div class="mt-4 pb-4 border-b">
                    <p class="text-base text-gray-600">Anda yakin ingin menukar hadiah:</p>
                    <p class="text-lg font-extrabold text-text-dark mt-1">"<span id="redeem-modal-name" class="text-primary-blue"></span>"</p>
                    <p class="text-sm text-gray-500 mt-2">dengan total</p>
                    <p class="text-2xl font-black text-primary-blue flex items-center justify-center mt-1">
                         <i data-lucide="gem" class="w-6 h-6 mr-1 fill-secondary-yellow text-secondary-yellow"></i>
                        <span id="redeem-modal-poin"></span> Poin?
                    </p>
                </div>
                <div class="mt-4 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('modal-redeem').classList.add('hidden')"
                            class="px-5 py-2 bg-gray-200 text-gray-700 text-base font-semibold rounded-2xl hover:bg-gray-300 transition duration-150">
                        Batal
                    </button>
                    <button type="button" onclick="redeemHadiah()"
                            class="px-5 py-2 bg-primary-blue text-white text-base font-semibold rounded-2xl hover:bg-blue-700 transition duration-150 shadow-md">
                        Ya, Tukar!
                    </button>
                </div>
            </div>
            <!-- Hidden Input untuk ID Hadiah -->
            <input type="hidden" id="redeem-modal-id">
        </div>
    </div>

    <!-- Hidden Form untuk Submit Penukaran -->
    <form id="redeem-form" method="POST" action="hadiah.php" style="display: none;">
        <input type="hidden" name="action" value="redeem">
        <input type="hidden" name="reward_id" id="redeem-form-id">
    </form>

    <script>
        // Inisialisasi Lucide Icons
        lucide.createIcons();

        // Fungsi untuk menampilkan modal konfirmasi penukaran
        function confirmRedeem(id, nama, poin) {
            document.getElementById('redeem-modal-id').value = id;
            document.getElementById('redeem-modal-name').textContent = nama;
            // Memformat angka poin dengan pemisah ribuan
            document.getElementById('redeem-modal-poin').textContent = poin.toLocaleString('id-ID'); 
            document.getElementById('modal-redeem').classList.remove('hidden');
        }
        
        // Logika untuk submit penukaran
        function redeemHadiah() {
            const id = document.getElementById('redeem-modal-id').value;
            document.getElementById('redeem-form-id').value = id;
            document.getElementById('redeem-form').submit();
        }
    </script>

</body>
</html>