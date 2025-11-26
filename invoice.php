<?php
/**
 * Invoice Pesanan
 * Menampilkan detail invoice untuk customer
 */

require_once 'config/database.php';
require_once 'config/session.php';

$db = getDB();
$kode_pesanan = isset($_GET['kode']) ? escape($_GET['kode']) : '';

if (empty($kode_pesanan)) {
    header("Location: index.php");
    exit();
}

// Get pesanan
$query = "SELECT * FROM pesanan WHERE kode_pesanan = '$kode_pesanan'";
$result = $db->query($query);

if ($result->num_rows === 0) {
    setFlash('error', 'Pesanan tidak ditemukan!');
    header("Location: index.php");
    exit();
}

$pesanan = $result->fetch_assoc();

// Get detail produk
$query_detail = "SELECT dp.*, p.foto 
                 FROM detail_pesanan dp 
                 LEFT JOIN produk p ON dp.produk_id = p.id
                 WHERE dp.pesanan_id = {$pesanan['id']}";
$detail_produk = $db->query($query_detail);

// Get pesanan fotocopy jika ada
$query_fotocopy = "SELECT * FROM pesanan_fotocopy WHERE pesanan_id = {$pesanan['id']}";
$result_fotocopy = $db->query($query_fotocopy);
$fotocopy = $result_fotocopy->num_rows > 0 ? $result_fotocopy->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= $kode_pesanan ?> - Copy&ATK Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printArea, #printArea * {
                visibility: visible;
            }
            #printArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-lg no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center space-x-2">
                    <i class="fas fa-print text-purple-600 text-2xl"></i>
                    <span class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                        Copy&ATK Premium
                    </span>
                </a>
                <div class="flex items-center gap-4">
                    <button onclick="window.print()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                        <i class="fas fa-print mr-2"></i>Cetak
                    </button>
                    <a href="index.php" class="text-gray-700 hover:text-purple-600">
                        <i class="fas fa-home mr-1"></i>Beranda
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Success Message -->
    <div class="bg-green-50 border-l-4 border-green-500 p-6 no-print">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 text-4xl mr-4"></i>
                <div>
                    <h2 class="text-2xl font-bold text-green-800">Pesanan Berhasil Dibuat!</h2>
                    <p class="text-green-700">Terima kasih atas pesanan Anda. Invoice telah dikirim ke nomor whatsapp Anda.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Invoice -->
    <div class="max-w-4xl mx-auto px-4 py-8" id="printArea">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <!-- Header -->
            <div class="flex justify-between items-start mb-8 pb-6 border-b-2">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-print text-purple-600 text-3xl"></i>
                        <h1 class="text-3xl font-bold text-gray-800">Copy&ATK Premium</h1>
                    </div>
                    <p class="text-gray-600">Manado, Sulawesi Utara</p>
                    <p class="text-gray-600">Telp: 0812-3456-7890</p>
                    <p class="text-gray-600">Email: info@copyatk.com</p>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold text-purple-600 mb-2">INVOICE</h2>
                    <p class="text-gray-600">No: <span class="font-bold"><?= $kode_pesanan ?></span></p>
                    <p class="text-gray-600">Tanggal: <?= date('d/m/Y', strtotime($pesanan['created_at'])) ?></p>
                    <span class="inline-block mt-2 px-4 py-2 rounded-full text-sm font-bold
                        <?php 
                        if ($pesanan['status'] === 'Pending') echo 'bg-yellow-100 text-yellow-700';
                        elseif ($pesanan['status'] === 'Diproses') echo 'bg-blue-100 text-blue-700';
                        elseif ($pesanan['status'] === 'Selesai') echo 'bg-green-100 text-green-700';
                        else echo 'bg-red-100 text-red-700';
                        ?>">
                        <?= $pesanan['status'] ?>
                    </span>
                </div>
            </div>
            
            <!-- Customer Info -->
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <h3 class="font-bold text-gray-800 mb-3 text-lg">Informasi Pelanggan:</h3>
                    <div class="space-y-1">
                        <p><span class="text-gray-600">Nama:</span> <span class="font-semibold"><?= $pesanan['nama_customer'] ?></span></p>
                        <?php if ($pesanan['email_customer']): ?>
                        <p><span class="text-gray-600">Email:</span> <span class="font-semibold"><?= $pesanan['email_customer'] ?></span></p>
                        <?php endif; ?>
                        <p><span class="text-gray-600">Telepon:</span> <span class="font-semibold"><?= $pesanan['telepon_customer'] ?></span></p>
                    </div>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 mb-3 text-lg">Alamat Pengiriman:</h3>
                    <p class="text-gray-700"><?= nl2br($pesanan['alamat_pengiriman']) ?></p>
                </div>
            </div>
            
            <!-- Detail Produk -->
            <?php if ($detail_produk->num_rows > 0): ?>
            <div class="mb-8">
                <h3 class="font-bold text-gray-800 mb-4 text-lg">Detail Produk:</h3>
                <table class="w-full">
                    <thead class="bg-purple-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Produk</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Harga</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Jumlah</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $detail_produk->fetch_assoc()): ?>
                        <tr class="border-b">
                            <td class="px-4 py-3"><?= $item['nama_produk'] ?></td>
                            <td class="px-4 py-3 text-center">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                            <td class="px-4 py-3 text-center"><?= $item['jumlah'] ?></td>
                            <td class="px-4 py-3 text-right font-semibold">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Detail Fotocopy -->
            <?php if ($fotocopy): ?>
            <div class="mb-8">
                <h3 class="font-bold text-gray-800 mb-4 text-lg">Detail Fotocopy:</h3>
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Jumlah Lembar</p>
                            <p class="font-semibold"><?= $fotocopy['jumlah_lembar'] ?> lembar</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Jenis Kertas</p>
                            <p class="font-semibold"><?= $fotocopy['jenis_kertas'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Warna</p>
                            <p class="font-semibold"><?= $fotocopy['warna'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Bolak-Balik</p>
                            <p class="font-semibold"><?= $fotocopy['bolak_balik'] ? 'Ya' : 'Tidak' ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Jilid</p>
                            <p class="font-semibold"><?= $fotocopy['jilid'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Biaya Fotocopy</p>
                            <p class="font-bold text-blue-600">Rp <?= number_format($fotocopy['subtotal'], 0, ',', '.') ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Total -->
            <div class="border-t-2 pt-6">
                <div class="space-y-2 mb-6">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span class="font-semibold">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Ongkos Kirim</span>
                        <span class="font-semibold text-green-600">GRATIS</span>
                    </div>
                    <div class="flex justify-between text-xl font-bold text-gray-800 pt-4 border-t">
                        <span>TOTAL PEMBAYARAN</span>
                        <span class="text-purple-600">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="font-semibold text-gray-800 mb-2">Metode Pembayaran:</p>
                    <p class="text-lg font-bold text-purple-600"><?= $pesanan['metode_pembayaran'] ?></p>
                    
                    <?php if ($pesanan['metode_pembayaran'] === 'Transfer'): ?>
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
                        <p class="font-semibold text-blue-800 mb-2">Informasi Transfer:</p>
                        <div class="space-y-1 text-sm text-blue-700">
                            <p>Bank BCA: 1234567890 (a.n. Toko ATK)</p>
                            <p>Bank Mandiri: 0987654321 (a.n. Toko ATK)</p>
                            <p>Bank BNI: 5678901234 (a.n. Toko ATK)</p>
                        </div>
                        <p class="text-xs text-blue-600 mt-2">*Konfirmasi pembayaran via WhatsApp: 0812-3456-7890</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($pesanan['catatan']): ?>
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="font-semibold text-yellow-800 mb-1">Catatan:</p>
                <p class="text-yellow-700"><?= nl2br($pesanan['catatan']) ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <div class="mt-8 pt-6 border-t text-center text-gray-600 text-sm">
                <p class="font-semibold mb-2">Terima kasih atas pesanan Anda!</p>
                <p>Jika ada pertanyaan, hubungi kami di 0812-3456-7890 atau info@copyatk.com</p>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="max-w-4xl mx-auto px-4 pb-8 no-print">
        <div class="flex gap-4 justify-center">
            <button onclick="window.print()" class="bg-purple-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-purple-700 transition">
                <i class="fas fa-print mr-2"></i>Cetak Invoice
            </button>
            <a href="index.php" class="bg-gray-200 text-gray-700 px-8 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                <i class="fas fa-home mr-2"></i>Kembali ke Beranda
            </a>
            <?php if (isLoggedIn()): ?>
            <a href="pesanan-saya.php" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                <i class="fas fa-history mr-2"></i>Riwayat Pesanan
            </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>