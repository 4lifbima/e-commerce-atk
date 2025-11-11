<?php
$activePage = 'fotocopy';    
require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

$db = getDB();

// Get semua order fotocopy dengan info pesanan
$query = "SELECT pf.*, p.kode_pesanan, p.nama_customer, p.telepon_customer, p.status, p.created_at as tanggal_order
          FROM pesanan_fotocopy pf
          JOIN pesanan p ON pf.pesanan_id = p.id
          ORDER BY p.created_at DESC";
$result = $db->query($query);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Fotocopy - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Order Fotocopy</h1>
            <p class="text-gray-600">Kelola pesanan fotocopy</p>
        </div>
        
        <?php if ($flash): ?>
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg mb-6">
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>
        
        <!-- Orders Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($order = $result->fetch_assoc()): ?>
                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition">
                    <!-- Header -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Order #<?= $order['kode_pesanan'] ?></p>
                            <h3 class="text-xl font-bold text-gray-800"><?= $order['nama_customer'] ?></h3>
                            <p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i><?= $order['telepon_customer'] ?></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold
                            <?php 
                            if ($order['status'] === 'Pending') echo 'bg-yellow-100 text-yellow-700';
                            elseif ($order['status'] === 'Diproses') echo 'bg-blue-100 text-blue-700';
                            elseif ($order['status'] === 'Selesai') echo 'bg-green-100 text-green-700';
                            else echo 'bg-red-100 text-red-700';
                            ?>">
                            <?= $order['status'] ?>
                        </span>
                    </div>
                    
                    <!-- Specs -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-purple-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-600">Jumlah Lembar</p>
                            <p class="font-bold text-purple-600"><?= $order['jumlah_lembar'] ?> lembar</p>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-600">Jenis Kertas</p>
                            <p class="font-bold text-blue-600"><?= $order['jenis_kertas'] ?></p>
                        </div>
                        <div class="bg-green-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-600">Warna</p>
                            <p class="font-bold text-green-600"><?= $order['warna'] ?></p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-600">Jilid</p>
                            <p class="font-bold text-orange-600"><?= $order['jilid'] ?></p>
                        </div>
                    </div>
                    
                    <!-- Details -->
                    <div class="border-t pt-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Bolak-Balik:</span>
                            <span class="font-semibold"><?= $order['bolak_balik'] ? 'Ya' : 'Tidak' ?></span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Harga/Lembar:</span>
                            <span class="font-semibold">Rp <?= number_format($order['harga_per_lembar'], 0, ',', '.') ?></span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Biaya Jilid:</span>
                            <span class="font-semibold">Rp <?= number_format($order['biaya_jilid'], 0, ',', '.') ?></span>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t">
                            <span class="font-bold text-gray-800">Total:</span>
                            <span class="font-bold text-xl text-purple-600">Rp <?= number_format($order['subtotal'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                    
                    <?php if ($order['keterangan_tambahan']): ?>
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-600 mb-1">Keterangan:</p>
                        <p class="text-sm"><?= $order['keterangan_tambahan'] ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Footer -->
                    <div class="mt-4 pt-4 border-t flex justify-between items-center">
                        <span class="text-xs text-gray-500">
                            <i class="fas fa-calendar mr-1"></i>
                            <?= date('d/m/Y H:i', strtotime($order['tanggal_order'])) ?>
                        </span>
                        <a href="pesanan-detail.php?id=<?= $order['pesanan_id'] ?>" class="text-purple-600 hover:text-purple-700 font-semibold text-sm">
                            Lihat Detail <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-2 bg-white rounded-2xl shadow-lg p-12 text-center">
                    <i class="fas fa-print text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">Belum ada order fotocopy</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>