<?php
require_once '../config/database.php';
require_once '../config/session.php';
requireAdmin();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once __DIR__ . '/../vendor/autoload.php';

// 🔐 Input sanitization
$start_date = preg_replace('/[^\d\-]/', '', $_GET['start'] ?? date('Y-m-01'));
$end_date   = preg_replace('/[^\d\-]/', '', $_GET['end']   ?? date('Y-m-d'));
$jenis_laporan = in_array($_GET['jenis'] ?? 'penjualan', ['penjualan','produk_terlaris','keuangan','pelanggan','kategori','stok','kupon','fotocopy','keuntungan','harian'])
    ? $_GET['jenis']
    : 'penjualan';

$db = getDB();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle(ucwords(str_replace('_', ' ', $jenis_laporan)));

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '6B21A8']],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
];

$row = 1;
$sheet->setCellValue('A' . $row++, "LAPORAN " . strtoupper(str_replace('_', ' ', $jenis_laporan)));
$sheet->setCellValue('A' . $row++, "Periode: {$start_date} s/d {$end_date}");
$prevRow = $row - 1;
$sheet->mergeCells("A{$prevRow}:Z{$prevRow}");
$sheet->getStyle("A{$prevRow}")->getFont()->setBold(true)->setSize(14);
$row += 1;

if ($jenis_laporan === 'penjualan') {
    $headers = ['Tanggal', 'Kode Pesanan', 'Customer', 'Item', 'Total', 'Diskon', 'Metode Bayar'];
    $sheet->fromArray([$headers], null, 'A' . $row);
    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($headerStyle);
    $row++;

    $stmt = $db->prepare("SELECT p.*, COALESCE((SELECT SUM(jumlah) FROM detail_pesanan WHERE pesanan_id = p.id), 0) as total_item FROM pesanan p WHERE DATE(p.created_at) BETWEEN ? AND ? AND p.status != 'Dibatalkan' ORDER BY p.created_at DESC");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $startDataRow = $row;
    while ($r = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, date('d/m/Y H:i', strtotime($r['created_at'])));
        $sheet->setCellValue('B' . $row, $r['kode_pesanan']);
        $sheet->setCellValue('C' . $row, $r['nama_customer']);
        $sheet->setCellValue('D' . $row, $r['total_item']);
        $sheet->setCellValue('E' . $row, (float)$r['total_harga']);
        $sheet->setCellValue('F' . $row, (float)$r['nilai_diskon']);
        $sheet->setCellValue('G' . $row, $r['metode_pembayaran']);
        $row++;
    }

if ($row > $startDataRow) {
    $endRow = $row - 1;
    $sheet->getStyle("E{$startDataRow}:G{$endRow}")->getNumberFormat()->setFormatCode('#,##0');
}

} elseif ($jenis_laporan === 'produk_terlaris') {
    $headers = ['Rank', 'Nama Produk', 'Kategori', 'Total Terjual', 'Pendapatan', 'Harga Rata-rata'];
    $sheet->fromArray([$headers], null, 'A' . $row);
    $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($headerStyle);
    $row++;

    $stmt = $db->prepare("SELECT p.nama_produk, k.nama_kategori, SUM(dp.jumlah) as total_terjual, SUM(dp.subtotal) as total_pendapatan, AVG(dp.harga) as harga_rata2 FROM detail_pesanan dp JOIN produk p ON dp.produk_id = p.id JOIN kategori k ON p.kategori_id = k.id JOIN pesanan pe ON dp.pesanan_id = pe.id WHERE DATE(pe.created_at) BETWEEN ? AND ? AND pe.status != 'Dibatalkan' GROUP BY p.id ORDER BY total_terjual DESC LIMIT 20");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $startDataRow = $row;
    $rank = 1;
    while ($r = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $rank++);
        $sheet->setCellValue('B' . $row, $r['nama_produk']);
        $sheet->setCellValue('C' . $row, $r['nama_kategori']);
        $sheet->setCellValue('D' . $row, (float)$r['total_terjual']);
        $sheet->setCellValue('E' . $row, (float)$r['total_pendapatan']);
        $sheet->setCellValue('F' . $row, (float)$r['harga_rata2']);
        $row++;
    }

if ($row > $startDataRow) {
    $endRow = $row - 1;
    $sheet->getStyle("D{$startDataRow}:F{$endRow}")->getNumberFormat()->setFormatCode('#,##0');
}

} elseif ($jenis_laporan === 'keuangan') {
    $headers = ['Tanggal', 'Jenis', 'Kategori', 'Deskripsi', 'Jumlah'];
    $sheet->fromArray([$headers], null, 'A' . $row);
    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($headerStyle);
    $row++;

    $stmt = $db->prepare("SELECT * FROM keuangan WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal DESC");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $startDataRow = $row;
    while ($r = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($r['tanggal'])));
        $sheet->setCellValue('B' . $row, $r['jenis']);
        $sheet->setCellValue('C' . $row, $r['kategori']);
        $sheet->setCellValue('D' . $row, $r['deskripsi']);
        $sheet->setCellValue('E' . $row, (float)$r['jumlah']);
        $row++;
    }

if ($row > $startDataRow) {
    $endRow = $row - 1;
    $sheet->getStyle("E{$startDataRow}:E{$endRow}")->getNumberFormat()->setFormatCode('#,##0');
}

} elseif ($jenis_laporan === 'pelanggan') {
    $headers = ['Nama Pelanggan', 'Email', 'Telepon', 'Total Pesanan', 'Total Belanja', 'Total Poin'];
    $sheet->fromArray([$headers], null, 'A' . $row);
    $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($headerStyle);
    $row++;

    $stmt = $db->prepare("SELECT u.id, u.nama, u.email, u.telepon, COUNT(DISTINCT p.id) as total_pesanan, COALESCE(SUM(p.total_harga), 0) as total_belanja, COALESCE(SUM(p.poin_didapat), 0) as total_poin FROM users u LEFT JOIN pesanan p ON u.id = p.user_id AND DATE(p.created_at) BETWEEN ? AND ? AND p.status != 'Dibatalkan' WHERE u.role = 'customer' GROUP BY u.id HAVING total_pesanan > 0 ORDER BY total_belanja DESC");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $startDataRow = $row;
    while ($r = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $r['nama']);
        $sheet->setCellValue('B' . $row, $r['email']);
        $sheet->setCellValue('C' . $row, $r['telepon']);
        $sheet->setCellValue('D' . $row, (float)$r['total_pesanan']);
        $sheet->setCellValue('E' . $row, (float)$r['total_belanja']);
        $sheet->setCellValue('F' . $row, (float)$r['total_poin']);
        $row++;
    }
    if ($row > $startDataRow) {
        $endRow = $row - 1;
        $sheet->getStyle("E{$startDataRow}:E{$endRow}")->getNumberFormat()->setFormatCode('#,##0');
    }

} elseif ($jenis_laporan === 'kategori') {
    $headers = ['Kategori', 'Total Produk', 'Total Terjual', 'Total Pendapatan'];
    $sheet->fromArray([$headers], null, 'A' . $row);
    $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($headerStyle);
    $row++;

    $stmt = $db->prepare("SELECT k.id, k.nama_kategori, COUNT(DISTINCT p.id) as total_produk, COALESCE(SUM(dp.jumlah), 0) as total_terjual, COALESCE(SUM(dp.subtotal), 0) as total_pendapatan FROM kategori k LEFT JOIN produk p ON k.id = p.kategori_id LEFT JOIN detail_pesanan dp ON p.id = dp.produk_id LEFT JOIN pesanan pe ON dp.pesanan_id = pe.id AND DATE(pe.created_at) BETWEEN ? AND ? AND pe.status != 'Dibatalkan' GROUP BY k.id ORDER BY total_pendapatan DESC");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $startDataRow = $row;
    while ($r = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $r['nama_kategori']);
        $sheet->setCellValue('B' . $row, (float)$r['total_produk']);
        $sheet->setCellValue('C' . $row, (float)$r['total_terjual']);
        $sheet->setCellValue('D' . $row, (float)$r['total_pendapatan']);
        $row++;
    }
    if ($row > $startDataRow) {
        $endRow = $row - 1;
        $sheet->getStyle("D{$startDataRow}:D{$endRow}")->getNumberFormat()->setFormatCode('#,##0');
    }

} elseif ($jenis_laporan === 'stok') {
    $headers = ['Nama Produk', 'Kategori', 'Stok', 'Harga', 'Terjual (Periode)'];
    $sheet->fromArray([$headers], null, 'A' . $row);
    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($headerStyle);
    $row++;

    $stmt = $db->prepare("SELECT p.id, p.nama_produk, k.nama_kategori, p.stok, p.harga, (SELECT SUM(jumlah) FROM detail_pesanan dp JOIN pesanan pe ON dp.pesanan_id = pe.id WHERE dp.produk_id = p.id AND DATE(pe.created_at) BETWEEN ? AND ? AND pe.status != 'Dibatalkan') as terjual_periode FROM produk p JOIN kategori k ON p.kategori_id = k.id WHERE p.is_active = 1 ORDER BY p.stok ASC");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $startDataRow = $row;
    while ($r = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $r['nama_produk']);
        $sheet->setCellValue('B' . $row, $r['nama_kategori']);
        $sheet->setCellValue('C' . $row, (float)$r['stok']);
        $sheet->setCellValue('D' . $row, (float)$r['harga']);
        $sheet->setCellValue('E' . $row, (float)($r['terjual_periode'] ?? 0));
        $row++;
    }
    if ($row > $startDataRow) {
        $endRow = $row - 1;
        $sheet->getStyle("D{$startDataRow}:D{$endRow}")->getNumberFormat()->setFormatCode('#,##0');
    }

} elseif ($jenis_laporan === 'kupon') {
    $headers = ['Kode Kupon', 'Nama Kupon', 'Jenis Diskon', 'Nilai Diskon', 'Total Penggunaan', 'Total Diskon Diberikan', 'Status'];
    $sheet->fromArray([$headers], null, 'A' . $row);
    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($headerStyle);
    $row++;

    $stmt = $db->prepare("SELECT k.*, COUNT(hk.id) as total_penggunaan, COALESCE(SUM(hk.nilai_diskon_didapat), 0) as total_diskon_diberikan FROM kupon k LEFT JOIN history_kupon hk ON k.id = hk.kupon_id AND DATE(hk.tgl_pakai) BETWEEN ? AND ? GROUP BY k.id ORDER BY k.created_at DESC");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $startDataRow = $row;
    while ($r = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $r['kode_kupon']);
        $sheet->setCellValue('B' . $row, $r['nama_kupon']);
        $sheet->setCellValue('C' . $row, $r['jenis_diskon']);
        $sheet->setCellValue('D' . $row, (float)$r['nilai_diskon']);
        $sheet->setCellValue('E' . $row, (float)$r['total_penggunaan']);
        $sheet->setCellValue('F' . $row, (float)$r['total_diskon_diberikan']);
        $sheet->setCellValue('G' . $row, $r['status_aktif'] ? 'Aktif' : 'Tidak Aktif');
        $row++;
    }
    if ($row > $startDataRow) {
        $endRow = $row - 1;
        $sheet->getStyle("D{$startDataRow}:F{$endRow}")->getNumberFormat()->setFormatCode('#,##0');
    }

} elseif ($jenis_laporan === 'fotocopy') {
    $headers = ['Tanggal', 'Kode Pesanan', 'Customer', 'Jumlah Lembar', 'Jenis Kertas', 'Warna', 'Subtotal'];
    $sheet->fromArray([$headers], null, 'A' . $row);
    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($headerStyle);
    $row++;

    $stmt = $db->prepare("SELECT pf.*, p.kode_pesanan, p.nama_customer, p.created_at as tanggal_pesanan FROM pesanan_fotocopy pf JOIN pesanan p ON pf.pesanan_id = p.id WHERE DATE(p.created_at) BETWEEN ? AND ? AND p.status != 'Dibatalkan' ORDER BY p.created_at DESC");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $startDataRow = $row;
    while ($r = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, date('d/m/Y H:i', strtotime($r['tanggal_pesanan'])));
        $sheet->setCellValue('B' . $row, $r['kode_pesanan']);
        $sheet->setCellValue('C' . $row, $r['nama_customer']);
        $sheet->setCellValue('D' . $row, (float)$r['jumlah_lembar']);
        $sheet->setCellValue('E' . $row, $r['jenis_kertas']);
        $sheet->setCellValue('F' . $row, $r['warna']);
        $sheet->setCellValue('G' . $row, (float)$r['subtotal']);
        $row++;
    }
    if ($row > $startDataRow) {
        $endRow = $row - 1;
        $sheet->getStyle("G{$startDataRow}:G{$endRow}")->getNumberFormat()->setFormatCode('#,##0');
    }

} elseif ($jenis_laporan === 'keuntungan') {
    $headers = ['Tanggal', 'Produk', 'Kategori', 'Jumlah', 'Harga Jual', 'Harga Beli', 'Keuntungan'];
    $sheet->fromArray([$headers], null, 'A' . $row);
    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($headerStyle);
    $row++;

    $stmt = $db->prepare("SELECT dp.id, p.nama_produk, k.nama_kategori, dp.jumlah, dp.harga as harga_jual, pr.harga_beli as harga_beli, ((dp.harga - pr.harga_beli) * dp.jumlah) as total_keuntungan, pe.created_at as tanggal FROM detail_pesanan dp JOIN produk pr ON dp.produk_id = pr.id JOIN kategori k ON pr.kategori_id = k.id JOIN pesanan pe ON dp.pesanan_id = pe.id WHERE DATE(pe.created_at) BETWEEN ? AND ? AND pe.status = 'Selesai' ORDER BY pe.created_at DESC");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $startDataRow = $row;
    while ($r = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($r['tanggal'])));
        $sheet->setCellValue('B' . $row, $r['nama_produk']);
        $sheet->setCellValue('C' . $row, $r['nama_kategori']);
        $sheet->setCellValue('D' . $row, (float)$r['jumlah']);
        $sheet->setCellValue('E' . $row, (float)$r['harga_jual']);
        $sheet->setCellValue('F' . $row, (float)$r['harga_beli']);
        $sheet->setCellValue('G' . $row, (float)$r['total_keuntungan']);
        $row++;
    }
    if ($row > $startDataRow) {
        $endRow = $row - 1;
        $sheet->getStyle("E{$startDataRow}:G{$endRow}")->getNumberFormat()->setFormatCode('#,##0');
    }

} elseif ($jenis_laporan === 'harian') {
    $headers = ['Tanggal', 'Total Transaksi', 'Total Customer', 'Total Penjualan', 'Rata-rata per Transaksi'];
    $sheet->fromArray([$headers], null, 'A' . $row);
    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($headerStyle);
    $row++;

    $stmt = $db->prepare("SELECT DATE(created_at) as tanggal, COUNT(*) as total_transaksi, SUM(total_harga) as total_penjualan, COUNT(DISTINCT user_id) as total_customer, AVG(total_harga) as rata_rata FROM pesanan WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'Dibatalkan' GROUP BY DATE(created_at) ORDER BY tanggal DESC");
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $startDataRow = $row;
    while ($r = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($r['tanggal'])));
        $sheet->setCellValue('B' . $row, (float)$r['total_transaksi']);
        $sheet->setCellValue('C' . $row, (float)$r['total_customer']);
        $sheet->setCellValue('D' . $row, (float)$r['total_penjualan']);
        $sheet->setCellValue('E' . $row, (float)$r['rata_rata']);
        $row++;
    }
    if ($row > $startDataRow) {
        $endRow = $row - 1;
        $sheet->getStyle("D{$startDataRow}:E{$endRow}")->getNumberFormat()->setFormatCode('#,##0');
    }
}

// Auto-size columns
foreach (range('A', $sheet->getHighestColumn()) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Laporan_' . $jenis_laporan . '_' . $start_date . '_' . $end_date . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;