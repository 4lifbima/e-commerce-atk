<?php
/**
 * Admin - Point of Sale (POS) System
 * Sistem pelayanan offline untuk pelanggan yang datang langsung ke toko
 */

$activePage = 'pos';
require_once '../config/database.php';
require_once '../config/session.php';
requireAdmin();

$db = getDB();

// Get produk aktif
$query_produk = "SELECT p.*, k.nama_kategori 
                 FROM produk p 
                 JOIN kategori k ON p.kategori_id = k.id 
                 WHERE p.is_active = 1 AND p.stok > 0
                 ORDER BY k.nama_kategori, p.nama_produk";
$produk_result = $db->query($query_produk);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale (POS) - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include 'includes/admin-styles.php'; ?>
</head>
<body class="bg-gray-100">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content p-4 md:p-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Point of Sale (POS)</h1>
            <p class="text-gray-600">Sistem pelayanan offline untuk pelanggan yang datang langsung ke toko</p>
        </div>
        
        <!-- Flash Message -->
        <?php if ($flash): ?>
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg mb-6">
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Product List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-box mr-2"></i>Daftar Produk
                    </h2>
                    
                    <!-- Search -->
                    <div class="mb-4">
                        <input 
                            type="text" 
                            id="searchProduk" 
                            placeholder="Cari produk..." 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        >
                    </div>
                    
                    <!-- Product Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 max-h-[600px] overflow-y-auto" id="produkGrid">
                        <?php while ($produk = $produk_result->fetch_assoc()): ?>
                        <div class="produk-item border border-gray-200 rounded-lg p-4 hover:shadow-lg transition cursor-pointer"
                             data-id="<?= $produk['id'] ?>"
                             data-nama="<?= htmlspecialchars($produk['nama_produk']) ?>"
                             data-harga="<?= $produk['harga'] ?>"
                             data-stok="<?= $produk['stok'] ?>"
                             data-kategori="<?= htmlspecialchars($produk['nama_kategori']) ?>"
                             onclick="addToCart(<?= $produk['id'] ?>, '<?= htmlspecialchars(addslashes($produk['nama_produk'])) ?>', <?= $produk['harga'] ?>, <?= $produk['stok'] ?>)">
                            <div class="text-center">
                                <div class="bg-purple-100 rounded-lg p-3 mb-2">
                                    <i class="fas fa-box text-3xl text-purple-600"></i>
                                </div>
                                <h3 class="font-semibold text-sm mb-1"><?= htmlspecialchars($produk['nama_produk']) ?></h3>
                                <p class="text-xs text-gray-500 mb-2"><?= htmlspecialchars($produk['nama_kategori']) ?></p>
                                <p class="font-bold text-purple-600">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></p>
                                <p class="text-xs text-gray-400 mt-1">Stok: <?= $produk['stok'] ?></p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right: Cart & Checkout -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-shopping-cart mr-2"></i>Keranjang
                    </h2>
                    
                    <!-- Cart Items -->
                    <div id="cartItems" class="mb-4 max-h-[400px] overflow-y-auto">
                        <p class="text-gray-500 text-center py-8">Keranjang kosong</p>
                    </div>
                    
                    <!-- Cart Summary -->
                    <div class="border-t pt-4 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-semibold" id="subtotal">Rp 0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Diskon:</span>
                            <span class="font-semibold text-green-600" id="diskon">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t pt-2">
                            <span>Total:</span>
                            <span class="text-purple-600" id="total">Rp 0</span>
                        </div>
                    </div>
                    
                    <!-- Customer Info Form -->
                    <div class="mt-6 border-t pt-4">
                        <h3 class="font-semibold mb-3">Informasi Pelanggan</h3>
                        <form id="checkoutForm" method="POST" action="pos-process.php">
                            <div class="mb-3">
                                <label class="block text-sm text-gray-700 mb-1">Nama Pelanggan *</label>
                                <input type="text" name="nama_customer" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm text-gray-700 mb-1">Telepon</label>
                                <input type="text" name="telepon_customer"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm text-gray-700 mb-1">Metode Pembayaran *</label>
                                <select name="metode_pembayaran" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                                    <option value="Tunai">Tunai</option>
                                    <option value="Transfer">Transfer</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="block text-sm text-gray-700 mb-1">Kode Kupon (Opsional)</label>
                                <input type="text" name="kode_kupon" id="kodeKupon"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                                <button type="button" onclick="validateKupon()" class="mt-1 text-sm text-purple-600 hover:underline" id="btnValidateKupon">
                                    Validasi Kupon
                                </button>
                            </div>
                            <input type="hidden" name="cart_data" id="cartData">
                            <input type="hidden" name="total_harga" id="totalHarga">
                            <input type="hidden" name="nilai_diskon" id="nilaiDiskon" value="0">
                            
                            <button type="submit" id="checkoutBtn" 
                                    class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-semibold hover:shadow-xl transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled>
                                <i class="fas fa-cash-register mr-2"></i>Proses Transaksi
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let cart = [];
        let kuponData = null;
        
        // Search produk
        document.getElementById('searchProduk').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.produk-item');
            items.forEach(item => {
                const nama = item.dataset.nama.toLowerCase();
                const kategori = item.dataset.kategori.toLowerCase();
                if (nama.includes(search) || kategori.includes(search)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Add to cart
        function addToCart(id, nama, harga, stok) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                if (existing.jumlah < stok) {
                    existing.jumlah++;
                } else {
                    alert('Stok tidak mencukupi!');
                    return;
                }
            } else {
                cart.push({
                    id: id,
                    nama: nama,
                    harga: harga,
                    jumlah: 1,
                    stok: stok
                });
            }
            updateCart();
        }
        
        // Remove from cart
        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            updateCart();
        }
        
        // Update quantity
        function updateQuantity(id, delta) {
            const item = cart.find(i => i.id === id);
            if (item) {
                item.jumlah += delta;
                if (item.jumlah <= 0) {
                    removeFromCart(id);
                } else if (item.jumlah > item.stok) {
                    item.jumlah = item.stok;
                    alert('Stok tidak mencukupi!');
                }
            }
            updateCart();
        }
        
        // Update cart display
        function updateCart() {
            const cartItems = document.getElementById('cartItems');
            if (cart.length === 0) {
                cartItems.innerHTML = '<p class="text-gray-500 text-center py-8">Keranjang kosong</p>';
                document.getElementById('checkoutBtn').disabled = true;
            } else {
                let html = '';
                cart.forEach(item => {
                    const subtotal = item.harga * item.jumlah;
                    html += `
                        <div class="border-b pb-3 mb-3">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-sm">${item.nama}</h4>
                                    <p class="text-xs text-gray-500">Rp ${formatNumber(item.harga)} x ${item.jumlah}</p>
                                </div>
                                <button onclick="removeFromCart(${item.id})" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="updateQuantity(${item.id}, -1)" class="bg-gray-200 px-2 py-1 rounded text-sm">-</button>
                                <span class="font-semibold">${item.jumlah}</span>
                                <button onclick="updateQuantity(${item.id}, 1)" class="bg-gray-200 px-2 py-1 rounded text-sm">+</button>
                                <span class="ml-auto font-bold text-purple-600">Rp ${formatNumber(subtotal)}</span>
                            </div>
                        </div>
                    `;
                });
                cartItems.innerHTML = html;
                document.getElementById('checkoutBtn').disabled = false;
            }
            
            // Calculate totals
            const subtotal = cart.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
            const diskon = kuponData ? kuponData.nilai_diskon : 0;
            const total = subtotal - diskon;
            
            document.getElementById('subtotal').textContent = 'Rp ' + formatNumber(subtotal);
            document.getElementById('diskon').textContent = 'Rp ' + formatNumber(diskon);
            document.getElementById('total').textContent = 'Rp ' + formatNumber(total);
            
            // Update hidden inputs
            document.getElementById('cartData').value = JSON.stringify(cart);
            document.getElementById('totalHarga').value = total;
            document.getElementById('nilaiDiskon').value = diskon;
        }
        
        // Validate kupon
        function validateKupon() {
            const kode = document.getElementById('kodeKupon').value.trim().toUpperCase();
            if (!kode) {
                alert('Masukkan kode kupon!');
                return;
            }
            
            // Update input field dengan uppercase
            document.getElementById('kodeKupon').value = kode;
            
            if (cart.length === 0) {
                alert('Keranjang masih kosong! Silakan tambahkan produk terlebih dahulu.');
                return;
            }
            
            const subtotal = cart.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
            
            if (subtotal <= 0) {
                alert('Total belanja tidak valid!');
                return;
            }
            
            // Show loading
            const btn = document.getElementById('btnValidateKupon');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Memvalidasi...';
            
            fetch('../validate-kupon.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `kode_kupon=${encodeURIComponent(kode)}&total_belanja=${subtotal}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    kuponData = {
                        kupon_id: data.kupon_id,
                        kode_kupon: data.kode_kupon,
                        nilai_diskon: data.nilai_diskon
                    };
                    alert('Kupon berhasil diterapkan! Diskon: Rp ' + formatNumber(data.nilai_diskon));
                    updateCart();
                } else {
                    alert(data.message || 'Kupon tidak valid!');
                    kuponData = null;
                    updateCart();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat validasi kupon! Silakan coba lagi.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        }
        
        // Format number
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        // Form submit
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            if (cart.length === 0) {
                e.preventDefault();
                alert('Keranjang kosong!');
                return;
            }
            
            if (!confirm('Yakin ingin memproses transaksi ini?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>

