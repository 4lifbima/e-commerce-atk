<?php
/**
 * Halaman Checkout
 * Form data pengiriman dan metode pembayaran
 */

require_once 'config/database.php';
require_once 'config/session.php';

$db = getDB();
$cart = getCart();
$cart_total = getCartTotal();

// Redirect jika cart kosong
if (empty($cart)) {
    setFlash('error', 'Keranjang belanja kosong!');
    header("Location: cart.php");
    exit();
}

// Pre-fill data jika user sudah login
$user_data = [
    'nama' => '',
    'email' => '',
    'telepon' => '',
    'alamat' => ''
];

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE id = $user_id";
    $result = $db->query($query);
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_data['nama'] = $user['nama'];
        $user_data['email'] = $user['email'];
        $user_data['telepon'] = $user['telepon'];
        $user_data['alamat'] = $user['alamat'];
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icon.png">
    <link rel="icon" href="assets/icon.png" type="image/x-icon">
    <title>Checkout - Copy&ATK Premium</title>
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
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center space-x-2">
                    <i class="fas fa-print text-[#400dd9] text-2xl"></i>
                    <span class="text-xl font-bold text-[#400dd9]">
                        Copy&ATK Premium
                    </span>
                </a>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="cart.php" class="text-gray-600 hover:text-[#400dd9] transition flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        Kembali ke Keranjang
                    </a>
                </div>
                
                <!-- Mobile Back Button -->
                <a href="cart.php" class="md:hidden text-gray-600 hover:text-[#400dd9]">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Flash Message -->
    <?php if ($flash): ?>
    <div class="max-w-7xl mx-auto px-4 mt-4 fade-in-up">
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg flex items-center gap-3">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $flash['message'] ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Checkout Steps - Responsive -->
    <div class="max-w-7xl mx-auto px-4 py-6 sm:py-8">
        <!-- Mobile Steps (Simplified) -->
        <div class="flex sm:hidden items-center justify-center mb-6">
            <div class="flex items-center gap-3">
                <div class="flex items-center">
                    <div class="bg-[#400dd9] text-white w-8 h-8 rounded-full flex items-center justify-center text-sm">
                        <i class="fas fa-check text-xs"></i>
                    </div>
                </div>
                <div class="w-8 h-1 bg-[#400dd9]"></div>
                <div class="flex items-center">
                    <div class="bg-[#400dd9] text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold">
                        2
                    </div>
                </div>
                <div class="w-8 h-1 bg-gray-300"></div>
                <div class="flex items-center">
                    <div class="bg-gray-300 text-gray-600 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold">
                        3
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Desktop Steps -->
        <div class="hidden sm:flex items-center justify-center mb-8">
            <div class="flex items-center">
                <div class="flex items-center">
                    <div class="bg-[#400dd9] text-white w-10 h-10 rounded-full flex items-center justify-center font-bold">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <span class="ml-2 font-semibold text-[#400dd9]">Keranjang</span>
                </div>
                <div class="w-12 lg:w-16 h-1 bg-[#400dd9] mx-2 lg:mx-4"></div>
                <div class="flex items-center">
                    <div class="bg-[#400dd9] text-white w-10 h-10 rounded-full flex items-center justify-center font-bold">
                        2
                    </div>
                    <span class="ml-2 font-semibold text-[#400dd9]">Checkout</span>
                </div>
                <div class="w-12 lg:w-16 h-1 bg-gray-300 mx-2 lg:mx-4"></div>
                <div class="flex items-center">
                    <div class="bg-gray-300 text-gray-600 w-10 h-10 rounded-full flex items-center justify-center font-bold">
                        3
                    </div>
                    <span class="ml-2 text-gray-600">Selesai</span>
                </div>
            </div>
        </div>
        
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 sm:mb-8 text-center">
            <i class="fas fa-clipboard-check text-[#400dd9] mr-2"></i>
            Checkout Pesanan
        </h1>
        
        <form method="POST" action="checkout-process.php">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                <!-- Form Data -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Data Customer -->
                    <div class="bg-white rounded-2xl shadow-lg p-5 sm:p-6">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-user text-[#400dd9] mr-2"></i>
                            Data Pemesan
                        </h2>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2 text-sm sm:text-base">Nama Lengkap *</label>
                                <input 
                                    type="text" 
                                    name="nama" 
                                    required 
                                    value="<?= htmlspecialchars($user_data['nama']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#400dd9] focus:border-transparent text-base"
                                    placeholder="John Doe"
                                >
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 font-semibold mb-2 text-sm sm:text-base">Email</label>
                                    <input 
                                        type="email" 
                                        name="email"
                                        value="<?= htmlspecialchars($user_data['email']) ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#400dd9] focus:border-transparent text-base"
                                        placeholder="email@example.com"
                                    >
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-semibold mb-2 text-sm sm:text-base">No. Telepon *</label>
                                    <input 
                                        type="tel" 
                                        name="telepon" 
                                        required
                                        value="<?= htmlspecialchars($user_data['telepon']) ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#400dd9] focus:border-transparent text-base"
                                        placeholder="08123456789"
                                    >
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2 text-sm sm:text-base">Alamat Pengiriman *</label>
                                <textarea 
                                    name="alamat" 
                                    required 
                                    rows="3"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#400dd9] focus:border-transparent text-base resize-none"
                                    placeholder="Jl. Contoh No. 123, Kelurahan, Kecamatan, Kota, Provinsi"
                                ><?= htmlspecialchars($user_data['alamat']) ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2 text-sm sm:text-base">Catatan (Optional)</label>
                                <textarea 
                                    name="catatan" 
                                    rows="2"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#400dd9] focus:border-transparent text-base resize-none"
                                    placeholder="Catatan untuk penjual..."
                                ></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Metode Pembayaran -->
                    <div class="bg-white rounded-2xl shadow-lg p-5 sm:p-6">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-credit-card text-[#400dd9] mr-2"></i>
                            Metode Pembayaran
                        </h2>
                        
                        <div class="space-y-3">
                            <label class="flex items-start p-4 border-2 border-gray-300 rounded-xl cursor-pointer hover:border-[#400dd9] transition has-[:checked]:border-[#400dd9] has-[:checked]:bg-purple-50">
                                <input 
                                    type="radio" 
                                    name="metode_pembayaran" 
                                    value="COD" 
                                    required 
                                    class="w-5 h-5 text-[#400dd9] mt-0.5 flex-shrink-0"
                                    checked
                                >
                                <div class="ml-3 sm:ml-4 flex-1 min-w-0">
                                    <div class="flex items-start sm:items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-800 text-sm sm:text-base">Bayar di Tempat (COD)</p>
                                            <p class="text-xs sm:text-sm text-gray-600">Bayar saat pengambilan barang</p>
                                        </div>
                                        <i class="fas fa-money-bill-wave text-green-600 text-xl sm:text-2xl flex-shrink-0"></i>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="flex items-start p-4 border-2 border-gray-300 rounded-xl cursor-pointer hover:border-[#400dd9] transition has-[:checked]:border-[#400dd9] has-[:checked]:bg-purple-50">
                                <input 
                                    type="radio" 
                                    name="metode_pembayaran" 
                                    value="Transfer" 
                                    class="w-5 h-5 text-[#400dd9] mt-0.5 flex-shrink-0"
                                >
                                <div class="ml-3 sm:ml-4 flex-1 min-w-0">
                                    <div class="flex items-start sm:items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-800 text-sm sm:text-base">Transfer Bank</p>
                                            <p class="text-xs sm:text-sm text-gray-600">BCA/Mandiri/BNI</p>
                                        </div>
                                        <i class="fas fa-university text-blue-600 text-xl sm:text-2xl flex-shrink-0"></i>
                                    </div>
                                </div>
                            </label>
                        </div>
                        
                        <div id="transferInfo" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-xl hidden">
                            <p class="font-semibold text-blue-800 mb-2 text-sm sm:text-base">Informasi Transfer:</p>
                            <div class="space-y-1 text-xs sm:text-sm">
                                <p class="text-blue-700"><i class="fas fa-building mr-2"></i>Bank BCA: 1234567890 (a.n. Toko ATK)</p>
                                <p class="text-blue-700"><i class="fas fa-building mr-2"></i>Bank Mandiri: 0987654321 (a.n. Toko ATK)</p>
                                <p class="text-blue-700"><i class="fas fa-building mr-2"></i>Bank BNI: 5678901234 (a.n. Toko ATK)</p>
                            </div>
                            <p class="text-xs text-blue-600 mt-3 flex items-start gap-2">
                                <i class="fas fa-info-circle mt-0.5"></i>
                                Konfirmasi pembayaran via WhatsApp setelah transfer
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg p-5 sm:p-6 lg:sticky lg:top-20">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-800 mb-4">Ringkasan Pesanan</h2>
                        
                        <!-- Items -->
                        <div class="space-y-3 mb-4 max-h-48 sm:max-h-64 overflow-y-auto">
                            <?php foreach ($cart as $item): ?>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                <div class="flex-shrink-0">
                                    <?php if ($item['foto']): ?>
                                    <img src="uploads/<?= $item['foto'] ?>" alt="<?= $item['nama'] ?>" class="w-12 h-12 object-cover rounded-lg">
                                    <?php else: ?>
                                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-sm truncate"><?= $item['nama'] ?></p>
                                    <p class="text-xs text-gray-600"><?= $item['jumlah'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></p>
                                </div>
                                <p class="font-semibold text-sm text-[#400dd9] flex-shrink-0">
                                    Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Kupon Promo -->
                        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                            <label class="block text-gray-700 font-semibold mb-2 text-sm sm:text-base">
                                <i class="fas fa-ticket-alt text-yellow-600 mr-2"></i>
                                Punya Kode Kupon?
                            </label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input 
                                    type="text" 
                                    id="kode_kupon_input"
                                    name="kode_kupon"
                                    placeholder="Masukkan kode"
                                    class="flex-1 px-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-500 uppercase text-sm sm:text-base"
                                >
                                <button 
                                    type="button"
                                    onclick="applyKupon()"
                                    class="bg-yellow-600 text-white px-5 py-2.5 rounded-xl hover:bg-yellow-700 transition font-semibold text-sm sm:text-base whitespace-nowrap">
                                    <i class="fas fa-check mr-1 sm:hidden"></i>
                                    <span class="hidden sm:inline">Pakai</span>
                                    <span class="sm:hidden">Pakai</span>
                                </button>
                            </div>
                            <div id="kupon_message" class="mt-2 text-sm hidden"></div>
                        </div>
                        
                        <!-- Totals -->
                        <div class="border-t pt-4 space-y-2">
                            <div class="flex justify-between text-gray-600 text-sm sm:text-base">
                                <span>Subtotal (<?= getCartCount() ?> item)</span>
                                <span class="font-semibold">Rp <?= number_format($cart_total, 0, ',', '.') ?></span>
                            </div>
                            <div class="flex justify-between text-gray-600 text-sm sm:text-base">
                                <span>Ongkos Kirim</span>
                                <span class="font-semibold text-green-600">GRATIS</span>
                            </div>
                            <div id="diskon_row" class="flex justify-between text-gray-600 text-sm sm:text-base hidden">
                                <span class="flex items-center flex-wrap gap-1">
                                    <i class="fas fa-tag text-green-600"></i>
                                    <span>Diskon</span>
                                    <span id="kupon_code_display" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full"></span>
                                </span>
                                <span class="font-semibold text-green-600" id="diskon_amount">- Rp 0</span>
                            </div>
                            <div class="border-t pt-3 mt-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-base sm:text-lg font-bold text-gray-800">Total</span>
                                    <span class="text-xl sm:text-2xl font-bold text-[#400dd9]" id="total_pembayaran">
                                        Rp <?= number_format($cart_total, 0, ',', '.') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden fields for kupon -->
                        <input type="hidden" id="kupon_id_hidden" name="kupon_id" value="">
                        <input type="hidden" id="nilai_diskon_hidden" name="nilai_diskon" value="0">
                        
                        <!-- Submit Button -->
                        <button 
                            type="submit"
                            class="w-full mt-6 bg-[#400dd9] text-white py-3.5 sm:py-4 rounded-xl font-bold text-base sm:text-lg hover:bg-[#350ba1] hover:shadow-xl transition flex items-center justify-center gap-2"
                        >
                            <i class="fas fa-check-circle"></i>
                            Konfirmasi Pesanan
                        </button>
                        
                        <a 
                            href="cart.php"
                            class="block w-full mt-3 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold text-center hover:bg-gray-200 transition text-sm sm:text-base"
                        >
                            <i class="fas fa-arrow-left mr-2"></i>
                            Kembali ke Keranjang
                        </a>
                        
                        <!-- Security Badge -->
                        <div class="mt-6 p-3 bg-green-50 rounded-xl">
                            <div class="flex items-center text-green-700">
                                <i class="fas fa-shield-alt text-xl sm:text-2xl mr-3 flex-shrink-0"></i>
                                <div>
                                    <p class="font-semibold text-xs sm:text-sm">Transaksi Aman</p>
                                    <p class="text-xs">Data Anda dilindungi dengan enkripsi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 sm:py-12 mt-12 sm:mt-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <i class="fas fa-print text-white text-xl mb-2"></i>
            <p class="font-bold text-lg mb-1">Copy&ATK Premium</p>
            <p class="text-gray-400 text-sm">&copy; 2025 Copy&ATK Premium. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Show/hide transfer info
        const paymentMethods = document.querySelectorAll('input[name="metode_pembayaran"]');
        const transferInfo = document.getElementById('transferInfo');
        
        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                if (this.value === 'Transfer') {
                    transferInfo.classList.remove('hidden');
                } else {
                    transferInfo.classList.add('hidden');
                }
            });
        });
        
        // Kupon functionality
        const cartTotal = <?= $cart_total ?>;
        let appliedKupon = null;
        
        async function applyKupon() {
            const kodeKuponInput = document.getElementById('kode_kupon_input');
            const kodeKupon = kodeKuponInput.value.trim().toUpperCase();
            const messageDiv = document.getElementById('kupon_message');
            
            if (!kodeKupon) {
                showKuponMessage('Masukkan kode kupon terlebih dahulu!', 'error');
                return;
            }
            
            // Update input dengan uppercase
            kodeKuponInput.value = kodeKupon;
            
            // Show loading
            messageDiv.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Memvalidasi kupon...';
            messageDiv.className = 'mt-2 text-sm text-blue-600';
            messageDiv.classList.remove('hidden');
            
            try {
                // Call API to validate kupon
                const response = await fetch('validate-kupon.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `kode_kupon=${encodeURIComponent(kodeKupon)}&total_belanja=${cartTotal}`
                });
                
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    // Struktur response sudah flat (tidak nested)
                    appliedKupon = {
                        kupon_id: result.kupon_id,
                        kode_kupon: result.kode_kupon,
                        nilai_diskon: result.nilai_diskon
                    };
                    showKuponMessage(result.message, 'success');
                    updateTotalWithDiskon();
                    
                    // Set hidden fields
                    document.getElementById('kupon_id_hidden').value = appliedKupon.kupon_id;
                    document.getElementById('nilai_diskon_hidden').value = appliedKupon.nilai_diskon;
                    
                    // Disable input setelah berhasil
                    document.getElementById('kode_kupon_input').disabled = true;
                } else {
                    showKuponMessage(result.message || 'Kupon tidak valid!', 'error');
                    removeDiskon();
                }
            } catch (error) {
                console.error('Error:', error);
                showKuponMessage('Terjadi kesalahan saat validasi kupon', 'error');
                removeDiskon();
            }
        }
        
        function showKuponMessage(message, type) {
            const messageDiv = document.getElementById('kupon_message');
            messageDiv.textContent = message;
            messageDiv.classList.remove('hidden');
            
            if (type === 'success') {
                messageDiv.className = 'mt-2 text-sm text-green-600 font-semibold';
            } else {
                messageDiv.className = 'mt-2 text-sm text-red-600';
            }
        }
        
        function updateTotalWithDiskon() {
            const diskonRow = document.getElementById('diskon_row');
            const diskonAmount = document.getElementById('diskon_amount');
            const kuponCodeDisplay = document.getElementById('kupon_code_display');
            const totalPembayaran = document.getElementById('total_pembayaran');
            
            const nilaiDiskon = appliedKupon.nilai_diskon;
            const totalSetelahDiskon = cartTotal - nilaiDiskon;
            
            // Show diskon row
            diskonRow.classList.remove('hidden');
            diskonAmount.textContent = '- Rp ' + formatNumber(nilaiDiskon);
            kuponCodeDisplay.textContent = appliedKupon.kode_kupon;
            
            // Update total
            totalPembayaran.textContent = 'Rp ' + formatNumber(totalSetelahDiskon);
        }
        
        function removeDiskon() {
            const diskonRow = document.getElementById('diskon_row');
            const totalPembayaran = document.getElementById('total_pembayaran');
            
            diskonRow.classList.add('hidden');
            totalPembayaran.textContent = 'Rp ' + formatNumber(cartTotal);
            
            // Clear hidden fields
            document.getElementById('kupon_id_hidden').value = '';
            document.getElementById('nilai_diskon_hidden').value = '0';
            
            // Re-enable input jika kupon dihapus
            document.getElementById('kode_kupon_input').disabled = false;
            
            appliedKupon = null;
        }
        
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
    <?php include 'components/chatbot-widget.php'; ?>
</body>
</html>