<?php
// Di dalam laporan-template.php — dipanggil via include, semua variabel $db, $jenis_laporan, $start_date, dst sudah tersedia
if ($jenis_laporan === 'penjualan') {
    $query = "SELECT 
                p.*,
                COALESCE((SELECT SUM(jumlah) FROM detail_pesanan WHERE pesanan_id = p.id), 0) as total_item
              FROM pesanan p
              WHERE DATE(p.created_at) BETWEEN '$start_date' AND '$end_date'
              AND p.status != 'Dibatalkan'
              ORDER BY p.created_at DESC";
    $result = $db->query($query);
    
    $query_summary = "SELECT 
                        COUNT(*) as total_transaksi,
                        SUM(total_harga) as total_penjualan,
                        SUM(nilai_diskon) as total_diskon,
                        AVG(total_harga) as rata_rata
                      FROM pesanan
                      WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'
                      AND status != 'Dibatalkan'";
    $summary = $db->query($query_summary)->fetch_assoc();
} elseif ($jenis_laporan === 'produk_terlaris') {
    $query = "SELECT 
                p.nama_produk,
                k.nama_kategori,
                SUM(dp.jumlah) as total_terjual,
                SUM(dp.subtotal) as total_pendapatan,
                AVG(dp.harga) as harga_rata2
              FROM detail_pesanan dp
              JOIN produk p ON dp.produk_id = p.id
              JOIN kategori k ON p.kategori_id = k.id
              JOIN pesanan pe ON dp.pesanan_id = pe.id
              WHERE DATE(pe.created_at) BETWEEN '$start_date' AND '$end_date'
              AND pe.status != 'Dibatalkan'
              GROUP BY p.id
              ORDER BY total_terjual DESC
              LIMIT 20";
    $result = $db->query($query);
} elseif ($jenis_laporan === 'keuangan') {
    $query = "SELECT * FROM keuangan 
              WHERE tanggal BETWEEN '$start_date' AND '$end_date'
              ORDER BY tanggal DESC, created_at DESC";
    $result = $db->query($query);
    
    $query_summary = "SELECT 
                        SUM(CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE 0 END) as total_pemasukan,
                        SUM(CASE WHEN jenis = 'Pengeluaran' THEN jumlah ELSE 0 END) as total_pengeluaran
                      FROM keuangan
                      WHERE tanggal BETWEEN '$start_date' AND '$end_date'";
    $summary = $db->query($query_summary)->fetch_assoc();
    $summary['saldo'] = $summary['total_pemasukan'] - $summary['total_pengeluaran'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan <?= ucwords(str_replace('_', ' ', $jenis_laporan)) ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 20px; }
        h1 { color: #5b21b6; }
        .summary { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
        .card { background: #f3f4f6; padding: 12px; border-radius: 8px; min-width: 180px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #8b5cf6; color: white; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
    </style>
</head>
<body>
    <h1>LAPORAN <?= strtoupper(str_replace('_', ' ', $jenis_laporan)) ?></h1>
    <p>Periode: <?= $start_date ?> s/d <?= $end_date ?></p>

    <?php if (isset($summary)): ?>
    <div class="summary">
        <?php if ($jenis_laporan === 'penjualan'): ?>
        <div class="card"><strong>Total Transaksi:</strong> <?= number_format($summary['total_transaksi']) ?></div>
        <div class="card"><strong>Total Penjualan:</strong> Rp <?= number_format($summary['total_penjualan'], 0, ',', '.') ?></div>
        <div class="card"><strong>Total Diskon:</strong> Rp <?= number_format($summary['total_diskon'], 0, ',', '.') ?></div>
        <div class="card"><strong>Rata-rata:</strong> Rp <?= number_format($summary['rata_rata'], 0, ',', '.') ?></div>
        <?php elseif ($jenis_laporan === 'keuangan'): ?>
        <div class="card"><strong>Pemasukan:</strong> Rp <?= number_format($summary['total_pemasukan'], 0, ',', '.') ?></div>
        <div class="card"><strong>Pengeluaran:</strong> Rp <?= number_format($summary['total_pengeluaran'], 0, ',', '.') ?></div>
        <div class="card"><strong>Saldo:</strong> Rp <?= number_format($summary['saldo'], 0, ',', '.') ?> (<?= $summary['saldo'] >= 0 ? 'Surplus' : 'Defisit' ?>)</div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <table>
        <?php if ($jenis_laporan === 'penjualan'): ?>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kode Pesanan</th>
                <th>Customer</th>
                <th class="text-center">Item</th>
                <th class="text-right">Total</th>
                <th class="text-right">Diskon</th>
                <th class="text-center">Metode Bayar</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($r = $result->fetch_assoc()): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                <td><?= $r['kode_pesanan'] ?></td>
                <td><?= $r['nama_customer'] ?></td>
                <td class="text-center"><?= $r['total_item'] ?></td>
                <td class="text-right">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                <td class="text-right"><?= $r['nilai_diskon'] > 0 ? '- Rp ' . number_format($r['nilai_diskon'], 0, ',', '.') : '-' ?></td>
                <td class="text-center"><?= $r['metode_pembayaran'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>

        <?php elseif ($jenis_laporan === 'produk_terlaris'): ?>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th class="text-center">Total Terjual</th>
                <th class="text-right">Pendapatan</th>
                <th class="text-right">Harga Rata-rata</th>
            </tr>
        </thead>
        <tbody>
        <?php $rank = 1; while ($r = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $rank++ ?></td>
                <td><?= $r['nama_produk'] ?></td>
                <td><?= $r['nama_kategori'] ?></td>
                <td class="text-center"><?= $r['total_terjual'] ?></td>
                <td class="text-right">Rp <?= number_format($r['total_pendapatan'], 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($r['harga_rata2'], 0, ',', '.') ?></td>
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
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($r = $result->fetch_assoc()): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                <td><?= $r['jenis'] ?></td>
                <td><?= $r['kategori'] ?></td>
                <td><?= $r['deskripsi'] ?></td>
                <td class="text-right"><?= $r['jenis'] === 'Pemasukan' ? '+' : '-' ?> Rp <?= number_format($r['jumlah'], 0, ',', '.') ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
        <?php endif; ?>
    </table>
</body>
</html>