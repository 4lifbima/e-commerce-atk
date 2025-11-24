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
        <?php if ($jenis_laporan === 'penjualan'): ?>
            LAPORAN PENJUALAN
        <?php elseif ($jenis_laporan === 'produk_terlaris'): ?>
            LAPORAN PRODUK TERLARIS
        <?php elseif ($jenis_laporan === 'keuangan'): ?>
            LAPORAN KEUANGAN
        <?php endif; ?>
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