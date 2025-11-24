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
$jenis_laporan = in_array($_GET['jenis'] ?? 'penjualan', ['penjualan','produk_terlaris','keuangan'])
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