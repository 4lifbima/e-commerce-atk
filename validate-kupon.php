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

// Call stored procedure
$query = "CALL validate_kupon('$kode_kupon', $total_belanja, @valid, @message, @kupon_id, @nilai_diskon)";
$db->query($query);

// Get output parameters
$result = $db->query("SELECT @valid as valid, @message as message, @kupon_id as kupon_id, @nilai_diskon as nilai_diskon");
$data = $result->fetch_assoc();

// Close statement untuk clear result
$db->next_result();

if ($data['valid']) {
    echo json_encode([
        'success' => true,
        'message' => $data['message'],
        'data' => [
            'kupon_id' => $data['kupon_id'],
            'kode_kupon' => $kode_kupon,
            'nilai_diskon' => (float)$data['nilai_diskon']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $data['message']
    ]);
}
?>