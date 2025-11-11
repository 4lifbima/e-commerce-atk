<?php
/**
 * Admin - Detail Pesanan
 */

require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

$db = getDB();
$pesanan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get pesanan
$query = "SELECT * FROM pesanan WHERE id = $pesanan_id";
$result = $db->query($query);

if ($result->num_rows === 0) {
    setFlash('error', 'Pesanan tidak ditemukan!');
    header("Location: pesanan.php");
    exit();
}

$pesanan = $result->fetch_assoc();

// Get detail produk
$query_detail = "SELECT dp.*, p.foto FROM detail_pesanan dp 
                 LEFT JOIN produk p ON dp.produk_id = p.id
                 WHERE dp.pesanan_id = $pesanan_id";
$detail_produk = $db->query($query_detail);

// Get pesanan fotocopy jika ada
$query_fotocopy = "SELECT * FROM pesanan_fotocopy WHERE pesanan_id = $pesanan_id";
$result_fotocopy = $db->query($query_fotocopy);
$fotocopy = $result_fotocopy->num_rows > 0 ? $result_fotocopy->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    <a href="pesanan.php" class="text-gray-600 hover:text-purple-600">
                        <i class="fas fa-arrow-left text-2xl"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Detail Pesanan</h1>
                        <p class="text-gray-600">Kode: <?= $pesanan['kode_pesanan'] ?></p>
                    </div>
                </div>
                <button onclick="window.print()" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-print mr-2"></i>Cetak Struk
                </button>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Info Pesanan -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Status & Info -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-xl font-bold mb-2">Informasi Pesanan</h2>
                            <p class="text-sm text-gray-600">Tanggal: <?= date('d F Y, H:i', strtotime($pesanan['created_at'])) ?></p>
                        </div>
                        <span class="px-4 py-2 rounded-full text-sm font-semibold
                            <?php 
                            if ($pesanan['status'] === 'Pending') echo 'bg-yellow-100 text-yellow-700';
                            elseif ($pesanan['status'] === 'Diproses') echo 'bg-blue-100 text-blue-700';
                            elseif ($pesanan['status'] === 'Selesai') echo 'bg-green-100 text-green-700';
                            else echo 'bg-red-100 text-red-700';
                            ?>">
                            <?= $pesanan['status'] ?>
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Metode Pembayaran</p>
                            <p class="font-semibold"><?= $pesanan['metode_pembayaran'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Harga</p>
                            <p class="font-bold text-xl text-purple-600">Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></p>
                        </div>
                    </div>
                    
                    <?php if ($pesanan['catatan']): ?>
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-600 mb-1">Catatan</p>
                        <p><?= $pesanan['catatan'] ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Detail Produk -->
                <?php if ($detail_produk->num_rows > 0): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Detail Produk</h2>
                    <div class="space-y-4">
                        <?php while ($item = $detail_produk->fetch_assoc()): ?>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                            <?php if ($item['foto']): ?>
                            <img src="../uploads/<?= $item['foto'] ?>" alt="<?= $item['nama_produk'] ?>" class="w-16 h-16 object-cover rounded-lg">
                            <?php else: ?>
                            <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                <i class="fas fa-image text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <h3 class="font-semibold"><?= $item['nama_produk'] ?></h3>
                                <p class="text-sm text-gray-600">Rp <?= number_format($item['harga'], 0, ',', '.') ?> x <?= $item['jumlah'] ?></p>
                            </div>
                            <p class="font-bold text-purple-600">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></p>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Detail Fotocopy -->
                <?php if ($fotocopy): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Detail Fotocopy</h2>
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
                            <p class="text-sm text-gray-600">Total Biaya</p>
                            <p class="font-bold text-purple-600">Rp <?= number_format($fotocopy['subtotal'], 0, ',', '.') ?></p>
                        </div>
                    </div>
                    <?php if ($fotocopy['keterangan_tambahan']): ?>
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-600 mb-1">Keterangan Tambahan</p>
                        <p><?= $fotocopy['keterangan_tambahan'] ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Info Customer -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Info Customer</h2>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600">Nama</p>
                            <p class="font-semibold"><?= $pesanan['nama_customer'] ?></p>
                        </div>
                        <?php if ($pesanan['email_customer']): ?>
                        <div>
                            <p class="text-sm text-gray-600">Email</p>
                            <p class="font-semibold"><?= $pesanan['email_customer'] ?></p>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p class="text-sm text-gray-600">Telepon</p>
                            <p class="font-semibold"><?= $pesanan['telepon_customer'] ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Alamat Pengiriman</p>
                            <p class="font-semibold"><?= $pesanan['alamat_pengiriman'] ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <?php if ($pesanan['status'] !== 'Selesai' && $pesanan['status'] !== 'Dibatalkan'): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Update Status</h2>
                    <form method="POST" action="pesanan-update.php">
                        <input type="hidden" name="id" value="<?= $pesanan['id'] ?>">
                        <select name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600 mb-4">
                            <option value="Pending" <?= $pesanan['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Diproses" <?= $pesanan['status'] === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                            <option value="Selesai" <?= $pesanan['status'] === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="Dibatalkan" <?= $pesanan['status'] === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                        <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-semibold hover:shadow-xl transition">
                            Update Status
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Print Styles -->
    <style media="print">
        body * {
            visibility: hidden;
        }
        .ml-64 {
            margin-left: 0 !important;
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
    </style>
</body>
</html>