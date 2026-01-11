<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireAdmin();

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../vendor/autoload.php';

$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-d');
$jenis_laporan = $_GET['jenis'] ?? 'penjualan';

$db = getDB();

// Konfigurasi informasi perusahaan
$company_name = "PT. ANGIN RIBUT TERBANG GORONTALO";
$address = "Jl. Jeruk Nipis, No. 28, Kota Gorontalo, 96111";
$phone = "08xxxxxxxxxx";
$outlet = "Cabang Utama";

// Path logo lokal (pastikan file ada)
$logoPath = __DIR__ . '/../assets/icon.png'; // Ubah sesuai lokasi file Anda

// Encode gambar ke base64 (agar bisa diakses oleh DomPDF)
$logoData = '';
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
}

// Buat HTML dengan format kop surat profesional
ob_start();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 20px;
            line-height: 1.4;
        }
        .kop-container {
            display: flex;
            position: relative;
            align-items: center; /* Rata atas */
            margin-bottom: 20px;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
        }
        .logo {
            width: 80px;
            height: 80px;
            margin-right: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo img {
            margin-top: 85px;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .company-info {
            text-align: center;
            flex-grow: 1;
        }
        .company-info h2 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        .company-info h5 {
            font-size: 15px;
            font-weight: bold;
            margin: 0;
        }
        .company-info p {
            margin: 2px 0;
            font-size: 12px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
        }
        .info-row {
            margin: 5px 0;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            font-size: 12px;
        }
        .signature {
            text-align: right;
            margin-top: 5px;
        }
        .signature p {
            font-size: 12px;
        }
        .signature b {
            margin-bottom: 50px;
            margin-right: 20px;
            display: inline-block;
            font-size: 12px;
        }
    </style>
</head>
<body>

    <!-- Kop Surat -->
    <div class="kop-container">
        <div class="logo">
            <?php if ($logoData): ?>
                <img src="data:image/png;base64,<?= $logoData ?>" alt="Logo Perusahaan">
            <?php else: ?>
                <div style="width: 100%; height: 100%; background: #3b82f6; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    LOGO
                </div>
            <?php endif; ?>
        </div>
        <div class="company-info">
            <h2><?= htmlspecialchars($company_name) ?></h2>
            <h5>DINAS CETAK & ALAT TULIS KANTOR</h5>
            <p><?= htmlspecialchars($address) ?></p>
            <p>Telp: <?= htmlspecialchars($phone) ?> Website: www.ecommerse.kesug.com</p>
        </div>
    </div>

    <!-- Judul Laporan -->
    <div class="title">
        <?php 
        $judul_laporan = [
            'penjualan' => 'LAPORAN PENJUALAN',
            'produk_terlaris' => 'LAPORAN PRODUK TERLARIS',
            'keuangan' => 'LAPORAN KEUANGAN',
            'pelanggan' => 'LAPORAN PELANGGAN',
            'kategori' => 'LAPORAN KATEGORI',
            'stok' => 'LAPORAN STOK PRODUK',
            'kupon' => 'LAPORAN KUPON',
            'fotocopy' => 'LAPORAN FOTOCOPY',
            'keuntungan' => 'LAPORAN KEUNTUNGAN',
            'harian' => 'LAPORAN HARIAN'
        ];
        echo $judul_laporan[$jenis_laporan] ?? 'LAPORAN';
        ?>
    </div>

    <!-- Informasi Periode & Outlet -->
    <div class="info-row">
        <strong>Tanggal:</strong> <?= htmlspecialchars($start_date) ?> s/d <?= htmlspecialchars($end_date) ?>
    </div>
    <div class="info-row">
        <strong>Outlet / Cabang:</strong> <?= htmlspecialchars($outlet) ?>
    </div>

    <!-- Data Table -->
    <table>
        <?php if ($jenis_laporan === 'penjualan'): ?>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kode Pesanan</th>
                    <th>Customer</th>
                    <th>Item</th>
                    <th>Total</th>
                    <th>Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->prepare("SELECT 
                                        p.*,
                                        COALESCE((SELECT SUM(jumlah) FROM detail_pesanan WHERE pesanan_id = p.id), 0) as total_item
                                      FROM pesanan p
                                      WHERE DATE(p.created_at) BETWEEN ? AND ?
                                      AND p.status != 'Dibatalkan'
                                      ORDER BY p.created_at DESC");
                $stmt->bind_param('ss', $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                    <td><?= htmlspecialchars($row['kode_pesanan']) ?></td>
                    <td><?= htmlspecialchars($row['nama_customer']) ?></td>
                    <td><?= $row['total_item'] ?></td>
                    <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        <?php elseif ($jenis_laporan === 'produk_terlaris'): ?>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Total Terjual</th>
                    <th>Total Pendapatan</th>
                    <th>Harga Rata-rata</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->prepare("SELECT 
                                        p.nama_produk,
                                        k.nama_kategori,
                                        SUM(dp.jumlah) as total_terjual,
                                        SUM(dp.subtotal) as total_pendapatan,
                                        AVG(dp.harga) as harga_rata2
                                      FROM detail_pesanan dp
                                      JOIN produk p ON dp.produk_id = p.id
                                      JOIN kategori k ON p.kategori_id = k.id
                                      JOIN pesanan pe ON dp.pesanan_id = pe.id
                                      WHERE DATE(pe.created_at) BETWEEN ? AND ?
                                      AND pe.status != 'Dibatalkan'
                                      GROUP BY p.id
                                      ORDER BY total_terjual DESC
                                      LIMIT 20");
                $stmt->bind_param('ss', $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                $rank = 1;
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                    <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                    <td><?= $row['total_terjual'] ?></td>
                    <td>Rp <?= number_format($row['total_pendapatan'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($row['harga_rata2'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        <?php elseif ($jenis_laporan === 'keuangan'): ?>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->prepare("SELECT * FROM keuangan 
                                      WHERE tanggal BETWEEN ? AND ?
                                      ORDER BY tanggal DESC, created_at DESC");
                $stmt->bind_param('ss', $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($row['jenis']) ?></td>
                    <td><?= htmlspecialchars($row['kategori']) ?></td>
                    <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                    <td><?= $row['jenis'] === 'Pemasukan' ? '+' : '-' ?> Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        <?php elseif ($jenis_laporan === 'pelanggan'): ?>
            <thead>
                <tr>
                    <th>Nama Pelanggan</th>
                    <th>Email</th>
                    <th>Telepon</th>
                    <th>Total Pesanan</th>
                    <th>Total Belanja</th>
                    <th>Total Poin</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->prepare("SELECT u.id, u.nama, u.email, u.telepon, COUNT(DISTINCT p.id) as total_pesanan, COALESCE(SUM(p.total_harga), 0) as total_belanja, COALESCE(SUM(p.poin_didapat), 0) as total_poin FROM users u LEFT JOIN pesanan p ON u.id = p.user_id AND DATE(p.created_at) BETWEEN ? AND ? AND p.status != 'Dibatalkan' WHERE u.role = 'customer' GROUP BY u.id HAVING total_pesanan > 0 ORDER BY total_belanja DESC");
                $stmt->bind_param('ss', $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['telepon']) ?></td>
                    <td><?= $row['total_pesanan'] ?></td>
                    <td>Rp <?= number_format($row['total_belanja'], 0, ',', '.') ?></td>
                    <td><?= $row['total_poin'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        <?php elseif ($jenis_laporan === 'kategori'): ?>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Total Produk</th>
                    <th>Total Terjual</th>
                    <th>Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->prepare("SELECT k.id, k.nama_kategori, COUNT(DISTINCT p.id) as total_produk, COALESCE(SUM(dp.jumlah), 0) as total_terjual, COALESCE(SUM(dp.subtotal), 0) as total_pendapatan FROM kategori k LEFT JOIN produk p ON k.id = p.kategori_id LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id LEFT JOIN pesanan pe ON dp.pesanan_id = pe.id AND DATE(pe.created_at) BETWEEN ? AND ? AND pe.status != 'Dibatalkan' GROUP BY k.id ORDER BY total_pendapatan DESC");
                $stmt->bind_param('ss', $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                    <td><?= $row['total_produk'] ?></td>
                    <td><?= $row['total_terjual'] ?></td>
                    <td>Rp <?= number_format($row['total_pendapatan'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        <?php elseif ($jenis_laporan === 'stok'): ?>
            <thead>
                <tr>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Harga</th>
                    <th>Terjual (Periode)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->prepare("SELECT p.id, p.nama_produk, k.nama_kategori, p.stok, p.harga, (SELECT SUM(jumlah) FROM detail_pesanan dp JOIN pesanan pe ON dp.pesanan_id = pe.id WHERE dp.produk_id = p.id AND DATE(pe.created_at) BETWEEN ? AND ? AND pe.status != 'Dibatalkan') as terjual_periode FROM produk p JOIN kategori k ON p.kategori_id = k.id WHERE p.is_active = 1 ORDER BY p.stok ASC");
                $stmt->bind_param('ss', $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                    <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                    <td><?= $row['stok'] ?></td>
                    <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                    <td><?= $row['terjual_periode'] ?? 0 ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        <?php elseif ($jenis_laporan === 'kupon'): ?>
            <thead>
                <tr>
                    <th>Kode Kupon</th>
                    <th>Nama Kupon</th>
                    <th>Jenis Diskon</th>
                    <th>Nilai Diskon</th>
                    <th>Total Penggunaan</th>
                    <th>Total Diskon Diberikan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->prepare("SELECT k.*, COUNT(hk.id) as total_penggunaan, COALESCE(SUM(hk.nilai_diskon_didapat), 0) as total_diskon_diberikan FROM kupon k LEFT JOIN history_kupon hk ON k.id = hk.kupon_id AND DATE(hk.tgl_pakai) BETWEEN ? AND ? GROUP BY k.id ORDER BY k.created_at DESC");
                $stmt->bind_param('ss', $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['kode_kupon']) ?></td>
                    <td><?= htmlspecialchars($row['nama_kupon']) ?></td>
                    <td><?= htmlspecialchars($row['jenis_diskon']) ?></td>
                    <td><?= $row['jenis_diskon'] === 'Persen' ? $row['nilai_diskon'] . '%' : 'Rp ' . number_format($row['nilai_diskon'], 0, ',', '.') ?></td>
                    <td><?= $row['total_penggunaan'] ?></td>
                    <td>Rp <?= number_format($row['total_diskon_diberikan'], 0, ',', '.') ?></td>
                    <td><?= $row['status_aktif'] ? 'Aktif' : 'Tidak Aktif' ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        <?php elseif ($jenis_laporan === 'fotocopy'): ?>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kode Pesanan</th>
                    <th>Customer</th>
                    <th>Jumlah Lembar</th>
                    <th>Jenis Kertas</th>
                    <th>Warna</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->prepare("SELECT pf.*, p.kode_pesanan, p.nama_customer, p.created_at as tanggal_pesanan FROM pesanan_fotocopy pf JOIN pesanan p ON pf.pesanan_id = p.id WHERE DATE(p.created_at) BETWEEN ? AND ? AND p.status != 'Dibatalkan' ORDER BY p.created_at DESC");
                $stmt->bind_param('ss', $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($row['tanggal_pesanan'])) ?></td>
                    <td><?= htmlspecialchars($row['kode_pesanan']) ?></td>
                    <td><?= htmlspecialchars($row['nama_customer']) ?></td>
                    <td><?= $row['jumlah_lembar'] ?></td>
                    <td><?= htmlspecialchars($row['jenis_kertas']) ?></td>
                    <td><?= htmlspecialchars($row['warna']) ?></td>
                    <td>Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        <?php elseif ($jenis_laporan === 'keuntungan'): ?>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Jumlah</th>
                    <th>Harga Jual</th>
                    <th>Harga Beli</th>
                    <th>Keuntungan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->prepare("SELECT dp.id, p.nama_produk, k.nama_kategori, dp.jumlah, dp.harga as harga_jual, pr.harga_beli as harga_beli, ((dp.harga - pr.harga_beli) * dp.jumlah) as total_keuntungan, pe.created_at as tanggal FROM detail_pesanan dp JOIN produk pr ON dp.produk_id = pr.id JOIN kategori k ON pr.kategori_id = k.id JOIN pesanan pe ON dp.pesanan_id = pe.id WHERE DATE(pe.created_at) BETWEEN ? AND ? AND pe.status = 'Selesai' ORDER BY pe.created_at DESC");
                $stmt->bind_param('ss', $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                    <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                    <td><?= $row['jumlah'] ?></td>
                    <td>Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($row['total_keuntungan'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        <?php elseif ($jenis_laporan === 'harian'): ?>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Total Transaksi</th>
                    <th>Total Customer</th>
                    <th>Total Penjualan</th>
                    <th>Rata-rata per Transaksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->prepare("SELECT DATE(created_at) as tanggal, COUNT(*) as total_transaksi, SUM(total_harga) as total_penjualan, COUNT(DISTINCT user_id) as total_customer, AVG(total_harga) as rata_rata FROM pesanan WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'Dibatalkan' GROUP BY DATE(created_at) ORDER BY tanggal DESC");
                $stmt->bind_param('ss', $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                    <td><?= $row['total_transaksi'] ?></td>
                    <td><?= $row['total_customer'] ?></td>
                    <td>Rp <?= number_format($row['total_penjualan'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($row['rata_rata'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        <?php endif; ?>
    </table>

    <!-- Footer -->
    <div class="footer">
        <div class="info-row">
            <strong>Tanggal Cetak Report:</strong> <?= date('d/m/Y H:i') ?>
        </div>
        <div class="signature">
            <b>Kepala Outlet</b>
            <br><br>
            <p>Lionel Nando Alfredo</p>
        </div>
    </div>

</body>
</html>

<?php
$html = ob_get_clean();

// Setup DomPDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF
$dompdf->stream("Laporan_{$jenis_laporan}_{$start_date}_{$end_date}.pdf", ['Attachment' => false]);
exit;