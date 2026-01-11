<?php
/**
 * API: Validate Kupon
 * Validasi kode kupon dan return nilai diskon
 */

require_once 'config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$kode_kupon = isset($_POST['kode_kupon']) ? strtoupper(trim($_POST['kode_kupon'])) : '';
$total_belanja = isset($_POST['total_belanja']) ? (float)$_POST['total_belanja'] : 0;

if (empty($kode_kupon)) {
    echo json_encode(['success' => false, 'message' => 'Kode kupon tidak boleh kosong']);
    exit();
}

if ($total_belanja <= 0) {
    echo json_encode(['success' => false, 'message' => 'Total belanja tidak valid']);
    exit();
}

$db = getDB();
$kode_kupon = escape($kode_kupon);

try {
    // 1. Panggil Stored Procedure
    $query = "CALL validate_kupon('$kode_kupon', $total_belanja, @valid, @message, @kupon_id, @nilai_diskon)";
    
    // Eksekusi CALL
    if (!$db->query($query)) {
        throw new Exception("Database Error: " . $db->error);
    }
    
    // PENTING: Bersihkan result set dari CALL procedure agar bisa jalanin query SELECT berikutnya
    // Tanpa ini, akan muncul error "Commands out of sync"
    while($db->more_results()) {
        $db->next_result();
    }
    
    // 2. Ambil nilai output variabel
    $result = $db->query("SELECT @valid as valid, @message as message, @kupon_id as kupon_id, @nilai_diskon as nilai_diskon");
    
    if (!$result) {
        throw new Exception("Gagal mengambil hasil validasi");
    }

    $data = $result->fetch_assoc();
    
    // Konversi valid ke boolean yang benar (karena dari DB biasanya string '1' atau '0')
    $isValid = ($data['valid'] == 1 || $data['valid'] === 'TRUE');

    if ($isValid) {
        echo json_encode([
            'success' => true,
            'message' => $data['message'],
            'kupon_id' => (int)$data['kupon_id'],
            'kode_kupon' => $kode_kupon,
            'nilai_diskon' => (float)$data['nilai_diskon']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $data['message']
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
?>