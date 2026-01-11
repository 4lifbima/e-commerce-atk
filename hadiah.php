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
    <link rel="apple-touch-icon" sizes="180x180" href="assets/icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icon.png">
    <link rel="icon" href="assets/icon.png" type="image/x-icon">
    <title>Tukar Hadiah - Copy&ATK Premium</title>
    <meta name="description" content="Tukar poin belanja Anda dengan hadiah menarik di Copy&ATK Premium.">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Custom animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* Card hover effect */
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        /* Reward card */
        .reward-card {
            transition: all 0.3s ease;
        }

        .reward-card:hover {
            transform: translateY(-4px);
        }

        .reward-card.redeemable:hover {
            box-shadow: 0 12px 30px rgba(64, 13, 217, 0.2);
        }
    </style>
</head>
<body class="bg-white">
    
    <!-- Navigation -->
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
                    
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-700 hover:text-[#400dd9]">
                            <i class="fas fa-user-circle text-xl"></i>
                            <span><?= $_SESSION['nama'] ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                            <a href="pesanan-saya.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-shopping-bag mr-2"></i>Pesanan Saya
                            </a>
                            <a href="hadiah.php" class="block px-4 py-2 text-[#400dd9] bg-purple-50 font-semibold">
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
                <a href="index.php" class="block text-gray-700 hover:text-[#400dd9]">Beranda</a>
                <a href="produk.php" class="block text-gray-700 hover:text-[#400dd9]">Produk</a>
                <a href="fotocopy.php" class="block text-gray-700 hover:text-[#400dd9]">Layanan Fotocopy</a>
                <a href="cart.php" class="block text-gray-700 hover:text-[#400dd9]">Keranjang (<?= getCartCount() ?>)</a>
                <a href="pesanan-saya.php" class="block text-gray-700 hover:text-[#400dd9]">Pesanan Saya</a>
                <a href="hadiah.php" class="block text-[#400dd9] font-semibold">Tukar Hadiah</a>
                <a href="logout.php" class="block text-red-600">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Poin Info -->
    <section class="relative overflow-hidden py-16 bg-gradient-to-br from-[#3600c9] via-[#400dd9] to-[#6d3bff] text-white">
        <!-- Background Orbs -->
        <div class="absolute -top-40 -left-32 w-[420px] h-[420px] bg-[#8bb5ff] blur-3xl opacity-30 rounded-full"></div>
        <div class="absolute top-10 right-0 w-[480px] h-[480px] bg-[#d4a8ff] blur-3xl opacity-30 rounded-full"></div>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[380px] h-[380px] bg-[#ffd3e6] blur-[120px] opacity-20 rounded-full"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="text-center md:text-left">
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">
                        <i class="fas fa-gift mr-3"></i>Tukar Poin dengan Hadiah
                    </h1>
                    <p class="text-purple-100 text-lg">Dapatkan hadiah menarik dengan poin belanja Anda!</p>
                </div>
                
                <!-- Poin Card -->
                <div class="bg-white/20 backdrop-blur-xl rounded-2xl p-6 min-w-[280px] hover:bg-white/30 transition duration-300 border border-white/30">
                    <div class="flex items-center gap-4">
                        <div class="bg-yellow-400 w-14 h-14 rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-medal text-white text-2xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-purple-100 mb-1">Poin Anda Saat Ini</p>
                            <p class="text-3xl font-bold"><?= number_format($poin_saya, 0, ',', '.') ?></p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-white/20">
                        <a href="pesanan-saya.php" class="text-sm text-white hover:text-yellow-300 transition flex items-center gap-2">
                            <i class="fas fa-history"></i>
                            Lihat Riwayat Pesanan
                            <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Flash Messages -->
    <?php if ($success_message): ?>
    <div class="max-w-7xl mx-auto px-4 mt-6 fade-in-up">
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex items-center gap-3" role="alert">
            <i class="fas fa-check-circle text-xl"></i>
            <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
    <div class="max-w-7xl mx-auto px-4 mt-6 fade-in-up">
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg flex items-center gap-3" role="alert">
            <i class="fas fa-exclamation-circle text-xl"></i>
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <section class="py-12 bg-gradient-to-b from-[#f7f9fc] to-[#e9eef5] relative overflow-hidden">
        <!-- Background Blur Orbs -->
        <div class="absolute -top-32 -left-20 w-72 h-72 bg-[#a3c6ff] blur-3xl opacity-30 rounded-full"></div>
        <div class="absolute top-10 right-0 w-80 h-80 bg-[#c2a3ff] blur-3xl opacity-30 rounded-full"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Katalog Hadiah</h2>
                <p class="text-gray-600">Pilih hadiah favorit Anda dan tukarkan dengan poin</p>
            </div>
            
            <!-- Rewards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php if (count($hadiah_list) > 0): ?>
                    <?php foreach ($hadiah_list as $hadiah): ?>
                        <?php
                            $is_available = $hadiah['stok'] > 0;
                            $is_redeemable = $poin_saya >= $hadiah['poin_dibutuhkan'] && $is_available;
                        ?>
                        
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden reward-card <?= $is_redeemable ? 'redeemable border-2 border-[#400dd9]/20' : 'opacity-80' ?>">
                            <!-- Image -->
                            <div class="h-48 bg-gray-100 flex items-center justify-center overflow-hidden">
                                <?php if ($hadiah['gambar_url']): ?>
                                <img src="<?php echo htmlspecialchars($hadiah['gambar_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($hadiah['nama_hadiah']); ?>" 
                                     class="w-full h-full object-cover">
                                <?php else: ?>
                                <div class="text-center">
                                    <i class="fas fa-gift text-gray-300 text-6xl"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-5">
                                <!-- Stock Badge -->
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs bg-blue-100 text-[#400dd9] px-3 py-1 rounded-full font-medium">
                                        <i class="fas fa-gift mr-1"></i>Hadiah
                                    </span>
                                    <span class="text-xs px-3 py-1 rounded-full font-medium <?= $is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                        <?= $is_available ? 'Stok: ' . number_format($hadiah['stok'], 0, ',', '.') : 'Stok Habis' ?>
                                    </span>
                                </div>
                                
                                <!-- Title -->
                                <h3 class="font-bold text-lg mb-2 text-gray-800 line-clamp-2">
                                    <?php echo htmlspecialchars($hadiah['nama_hadiah']); ?>
                                </h3>
                                
                                <!-- Description -->
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                    <?php echo htmlspecialchars($hadiah['deskripsi'] ?: 'Hadiah menarik untuk Anda.'); ?>
                                </p>
                                
                                <!-- Points Required -->
                                <div class="flex items-center justify-between border-t pt-4 mb-4">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-medal text-yellow-500 text-xl"></i>
                                        <span class="text-2xl font-bold text-[#400dd9]">
                                            <?php echo number_format($hadiah['poin_dibutuhkan'], 0, ',', '.'); ?>
                                        </span>
                                        <span class="text-gray-500 text-sm">Poin</span>
                                    </div>
                                </div>
                                
                                <!-- Action Button -->
                                <?php if ($is_redeemable): ?>
                                    <button onclick="confirmRedeem(<?php echo $hadiah['id']; ?>, '<?php echo htmlspecialchars(addslashes($hadiah['nama_hadiah'])); ?>', <?php echo $hadiah['poin_dibutuhkan']; ?>)"
                                            class="w-full bg-[#400dd9] text-white font-bold py-3 rounded-xl hover:bg-[#350ba1] transition shadow-lg hover:shadow-xl transform hover:scale-[1.02]">
                                        <i class="fas fa-exchange-alt mr-2"></i>Tukar Sekarang
                                    </button>
                                <?php else: ?>
                                    <button disabled 
                                            class="w-full bg-gray-200 text-gray-500 font-bold py-3 rounded-xl cursor-not-allowed">
                                        <?php if (!$is_available): ?>
                                            <i class="fas fa-times-circle mr-2"></i>Stok Habis
                                        <?php else: ?>
                                            <i class="fas fa-lock mr-2"></i>Poin Tidak Cukup
                                        <?php endif; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="col-span-full text-center py-16 bg-white rounded-2xl shadow-lg">
                        <i class="fas fa-gift text-gray-300 text-8xl mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Belum Ada Hadiah</h3>
                        <p class="text-gray-600 mb-6">Nantikan hadiah-hadiah menarik yang akan segera hadir!</p>
                        <a href="produk.php" class="inline-block bg-[#400dd9] text-white px-8 py-3 rounded-full font-semibold hover:bg-[#350ba1] hover:shadow-xl transition">
                            <i class="fas fa-shopping-bag mr-2"></i>Belanja Sekarang
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Cara Mendapatkan Poin</h2>
                <p class="text-gray-600">Kumpulkan poin dengan berbelanja di Copy&ATK Premium</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-6 rounded-2xl bg-gray-50 card-hover">
                    <div class="bg-[#400dd9] w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shopping-cart text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">1. Belanja</h3>
                    <p class="text-gray-600">Belanja produk ATK favorit Anda di toko kami</p>
                </div>
                
                <div class="text-center p-6 rounded-2xl bg-gray-50 card-hover">
                    <div class="bg-[#400dd9] w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-medal text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">2. Kumpulkan Poin</h3>
                    <p class="text-gray-600">Dapatkan poin dari setiap transaksi yang berhasil</p>
                </div>
                
                <div class="text-center p-6 rounded-2xl bg-gray-50 card-hover">
                    <div class="bg-[#400dd9] w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-gift text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">3. Tukar Hadiah</h3>
                    <p class="text-gray-600">Tukarkan poin Anda dengan hadiah menarik</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <i class="fas fa-print text-white text-2xl"></i>
                        <span class="text-xl font-bold text-white">
                            Copy&ATK Premium
                        </span>
                    <p class="text-gray-400">Solusi terpercaya untuk kebutuhan ATK dan fotocopy Anda</p>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold mb-4">Menu</h3>
                    <ul class="space-y-2">
                        <li><a href="produk.php" class="text-gray-400 hover:text-white">Produk</a></li>
                        <li><a href="fotocopy.php" class="text-gray-400 hover:text-white">Layanan Fotocopy</a></li>
                        <li><a href="cart.php" class="text-gray-400 hover:text-white">Keranjang</a></li>
                        <li><a href="hadiah.php" class="text-gray-400 hover:text-white">Tukar Hadiah</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold mb-4">Kontak</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><i class="fas fa-phone mr-2"></i> 0822-9138-3797</li>
                        <li><i class="fas fa-envelope mr-2"></i> info@copyatk.com</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i> Tilongkabila, Bonebolango</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold mb-4">Ikuti Kami</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="bg-white text-[#400dd9] w-10 h-10 rounded-full flex items-center justify-center hover:shadow-lg transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="bg-white text-[#400dd9] w-10 h-10 rounded-full flex items-center justify-center hover:shadow-lg transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="bg-white text-[#400dd9] w-10 h-10 rounded-full flex items-center justify-center hover:shadow-lg transition">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 Copy&ATK Premium. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Modal Konfirmasi Penukaran -->
    <div id="modal-redeem" class="fixed inset-0 bg-gray-900 bg-opacity-70 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center p-4">
        <div class="relative mx-auto border w-full max-w-sm shadow-2xl rounded-2xl bg-white transform transition-all duration-300 scale-100">
            <div class="p-6 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-[#400dd9]/10 mb-4">
                    <i class="fas fa-exchange-alt text-[#400dd9] text-2xl"></i>
                </div>
                <h3 class="text-xl leading-7 font-bold text-gray-900 mt-2">Konfirmasi Penukaran</h3>
                <div class="mt-4 pb-4 border-b">
                    <p class="text-base text-gray-600">Anda yakin ingin menukar hadiah:</p>
                    <p class="text-lg font-bold text-gray-800 mt-2">"<span id="redeem-modal-name" class="text-[#400dd9]"></span>"</p>
                    <div class="mt-4 flex items-center justify-center gap-2">
                        <i class="fas fa-medal text-yellow-500 text-xl"></i>
                        <span class="text-2xl font-bold text-[#400dd9]" id="redeem-modal-poin"></span>
                        <span class="text-gray-500">Poin</span>
                    </div>
                </div>
                <div class="mt-4 flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('modal-redeem').classList.add('hidden')"
                            class="px-5 py-2 bg-gray-200 text-gray-700 text-base font-semibold rounded-xl hover:bg-gray-300 transition duration-150">
                        Batal
                    </button>
                    <button type="button" onclick="redeemHadiah()"
                            class="px-5 py-2 bg-[#400dd9] text-white text-base font-semibold rounded-xl hover:bg-[#350ba1] transition duration-150 shadow-md">
                        <i class="fas fa-check mr-2"></i>Ya, Tukar!
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
        // Mobile Menu Toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

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

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('modal-redeem').classList.add('hidden');
            }
        });

        // Close modal on backdrop click
        document.getElementById('modal-redeem').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    </script>
    <?php include 'components/chatbot-widget.php'; ?>
</body>
</html>