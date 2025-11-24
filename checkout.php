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
    <title>Checkout - Copy&ATK Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center space-x-2">
                    <i class="fas fa-print text-purple-600 text-2xl"></i>
                    <span class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                        Copy&ATK Premium
                    </span>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Flash Message -->
    <?php if ($flash): ?>
    <div class="max-w-7xl mx-auto px-4 mt-4">
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg">
            <?= $flash['message'] ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Checkout Steps -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center">
                <div class="flex items-center">
                    <div class="bg-purple-600 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <span class="ml-2 font-semibold text-purple-600">Keranjang</span>
                </div>
                <div class="w-16 h-1 bg-purple-600 mx-4"></div>
                <div class="flex items-center">
                    <div class="bg-purple-600 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold">
                        2
                    </div>
                    <span class="ml-2 font-semibold text-purple-600">Checkout</span>
                </div>
                <div class="w-16 h-1 bg-gray-300 mx-4"></div>
                <div class="flex items-center">
                    <div class="bg-gray-300 text-gray-600 w-10 h-10 rounded-full flex items-center justify-center font-bold">
                        3
                    </div>
                    <span class="ml-2 text-gray-600">Selesai</span>
                </div>
            </div>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">
            <i class="fas fa-clipboard-check text-purple-600 mr-2"></i>
            Checkout Pesanan
        </h1>
        
        <form method="POST" action="checkout-process.php">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form Data -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Data Customer -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-user text-purple-600 mr-2"></i>
                            Data Pemesan
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Nama Lengkap *</label>
                                <input 
                                    type="text" 
                                    name="nama" 
                                    required 
                                    value="<?= htmlspecialchars($user_data['nama']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                                    placeholder="John Doe"
                                >
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Email</label>
                                <input 
                                    type="email" 
                                    name="email"
                                    value="<?= htmlspecialchars($user_data['email']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                                    placeholder="email@example.com"
                                >
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">No. Telepon *</label>
                                <input 
                                    type="tel" 
                                    name="telepon" 
                                    required
                                    value="<?= htmlspecialchars($user_data['telepon']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                                    placeholder="08123456789"
                                >
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Alamat Pengiriman *</label>
                                <textarea 
                                    name="alamat" 
                                    required 
                                    rows="3"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                                    placeholder="Jl. Contoh No. 123, Kelurahan, Kecamatan, Kota, Provinsi"
                                ><?= htmlspecialchars($user_data['alamat']) ?></textarea>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Catatan (Optional)</label>
                                <textarea 
                                    name="catatan" 
                                    rows="2"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                                    placeholder="Catatan untuk penjual..."
                                ></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Metode Pembayaran -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-credit-card text-purple-600 mr-2"></i>
                            Metode Pembayaran
                        </h2>
                        
                        <div class="space-y-3">
                            <label class="flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-purple-600 transition has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50">
                                <input 
                                    type="radio" 
                                    name="metode_pembayaran" 
                                    value="COD" 
                                    required 
                                    class="w-5 h-5 text-purple-600"
                                    checked
                                >
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-gray-800">Bayar di Tempat (COD)</p>
                                            <p class="text-sm text-gray-600">Bayar saat pengambilan barang</p>
                                        </div>
                                        <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-purple-600 transition has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50">
                                <input 
                                    type="radio" 
                                    name="metode_pembayaran" 
                                    value="Transfer" 
                                    class="w-5 h-5 text-purple-600"
                                >
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-gray-800">Transfer Bank</p>
                                            <p class="text-sm text-gray-600">BCA/Mandiri/BNI</p>
                                        </div>
                                        <i class="fas fa-university text-blue-600 text-2xl"></i>
                                    </div>
                                </div>
                            </label>
                        </div>
                        
                        <div id="transferInfo" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg hidden">
                            <p class="font-semibold text-blue-800 mb-2">Informasi Transfer:</p>
                            <div class="space-y-1 text-sm">
                                <p class="text-blue-700">Bank BCA: 1234567890 (a.n. Toko ATK)</p>
                                <p class="text-blue-700">Bank Mandiri: 0987654321 (a.n. Toko ATK)</p>
                                <p class="text-blue-700">Bank BNI: 5678901234 (a.n. Toko ATK)</p>
                            </div>
                            <p class="text-xs text-blue-600 mt-2">*Konfirmasi pembayaran via WhatsApp setelah transfer</p>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-4">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Ringkasan Pesanan</h2>
                        
                        <!-- Items -->
                        <div class="space-y-3 mb-4 max-h-64 overflow-y-auto">
                            <?php foreach ($cart as $item): ?>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0">
                                    <?php if ($item['foto']): ?>
                                    <img src="uploads/<?= $item['foto'] ?>" alt="<?= $item['nama'] ?>" class="w-12 h-12 object-cover rounded">
                                    <?php else: ?>
                                    <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-sm truncate"><?= $item['nama'] ?></p>
                                    <p class="text-xs text-gray-600"><?= $item['jumlah'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></p>
                                </div>
                                <p class="font-semibold text-sm text-purple-600">
                                    Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Kupon Promo -->
                        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-ticket-alt text-yellow-600 mr-2"></i>
                                Punya Kode Kupon?
                            </label>
                            <div class="flex gap-2">
                                <input 
                                    type="text" 
                                    id="kode_kupon_input"
                                    name="kode_kupon"
                                    placeholder="Masukkan kode kupon"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 uppercase"
                                >
                                <button 
                                    type="button"
                                    onclick="applyKupon()"
                                    class="bg-yellow-600 text-white px-6 py-2 rounded-lg hover:bg-yellow-700 transition font-semibold">
                                    Pakai
                                </button>
                            </div>
                            <div id="kupon_message" class="mt-2 text-sm hidden"></div>
                        </div>
                        
                        <!-- Totals -->
                        <div class="border-t pt-4 space-y-2">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal (<?= getCartCount() ?> item)</span>
                                <span class="font-semibold">Rp <?= number_format($cart_total, 0, ',', '.') ?></span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Ongkos Kirim</span>
                                <span class="font-semibold text-green-600">GRATIS</span>
                            </div>
                            <div id="diskon_row" class="flex justify-between text-gray-600 hidden">
                                <span class="flex items-center">
                                    <i class="fas fa-tag text-green-600 mr-2"></i>
                                    Diskon Kupon <span id="kupon_code_display" class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full"></span>
                                </span>
                                <span class="font-semibold text-green-600" id="diskon_amount">- Rp 0</span>
                            </div>
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-bold text-gray-800">Total Pembayaran</span>
                                    <span class="text-2xl font-bold text-purple-600" id="total_pembayaran">
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
                            class="w-full mt-6 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-2xl transition"
                        >
                            <i class="fas fa-check-circle mr-2"></i>
                            Konfirmasi Pesanan
                        </button>
                        
                        <a 
                            href="cart.php"
                            class="block w-full mt-3 bg-gray-200 text-gray-700 py-3 rounded-xl font-semibold text-center hover:bg-gray-300 transition"
                        >
                            <i class="fas fa-arrow-left mr-2"></i>
                            Kembali ke Keranjang
                        </a>
                        
                        <!-- Security Badge -->
                        <div class="mt-6 p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center text-green-700">
                                <i class="fas fa-shield-alt text-2xl mr-3"></i>
                                <div>
                                    <p class="font-semibold text-sm">Transaksi Aman</p>
                                    <p class="text-xs">Data Anda dilindungi dengan SSL</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; 2025 Copy&ATK Premium. All rights reserved.</p>
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
            const kodeKupon = document.getElementById('kode_kupon_input').value.trim().toUpperCase();
            const messageDiv = document.getElementById('kupon_message');
            
            if (!kodeKupon) {
                showKuponMessage('Masukkan kode kupon terlebih dahulu!', 'error');
                return;
            }
            
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
                
                const result = await response.json();
                
                if (result.success) {
                    appliedKupon = result.data;
                    showKuponMessage(result.message, 'success');
                    updateTotalWithDiskon();
                    
                    // Set hidden fields
                    document.getElementById('kupon_id_hidden').value = appliedKupon.kupon_id;
                    document.getElementById('nilai_diskon_hidden').value = appliedKupon.nilai_diskon;
                    
                    // Disable input setelah berhasil
                    document.getElementById('kode_kupon_input').disabled = true;
                } else {
                    showKuponMessage(result.message, 'error');
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
            
            appliedKupon = null;
        }
        
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
</body>
</html>