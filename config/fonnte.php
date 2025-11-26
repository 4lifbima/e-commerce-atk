<?php
/**
 * Konfigurasi Fonnte WhatsApp API
 */

// Token Fonnte - ganti dengan token Anda dari https://fonnte.com
define('FONNTE_TOKEN', '7Bqc9vbcyMPmj7Y8LLEo'); // GANTI dengan token Anda

/**
 * Fungsi untuk mengirim pesan WhatsApp via Fonnte
 * @param string $target - Nomor WhatsApp tujuan (format: 628xxx)
 * @param string $message - Pesan yang akan dikirim
 * @param string $delay - Delay pengiriman (default: 1-3 detik)
 * @return array - Response dari API Fonnte
 */
function kirimWhatsApp($target, $message, $delay = '1-3') {
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'target' => $target,
            'message' => $message,
            'countryCode' => '62',
            'delay' => $delay
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . FONNTE_TOKEN
        ),
    ));
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if (curl_errno($curl)) {
        $error = curl_error($curl);
        curl_close($curl);
        return [
            'success' => false,
            'error' => $error,
            'http_code' => 0
        ];
    }
    
    curl_close($curl);
    
    $result = json_decode($response, true);
    
    return [
        'success' => $httpCode == 200 && isset($result['status']) && $result['status'] == true,
        'response' => $result,
        'http_code' => $httpCode,
        'raw_response' => $response
    ];
}

/**
 * Fungsi untuk format nomor WhatsApp
 * Dari 08xxx atau +628xxx menjadi 628xxx
 * @param string $phone - Nomor telepon
 * @return string - Nomor WhatsApp terformat
 */
function formatWhatsAppNumber($phone) {
    // Hapus semua karakter non-digit
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Jika kosong, return kosong
    if (empty($phone)) {
        return '';
    }
    
    // Jika diawali 0, ganti dengan 62
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    // Jika diawali +62, hapus +
    if (substr($phone, 0, 3) === '+62') {
        $phone = substr($phone, 1);
    }
    
    // Jika tidak diawali 62, tambahkan 62
    if (substr($phone, 0, 2) !== '62') {
        $phone = '62' . $phone;
    }
    
    return $phone;
}

/**
 * Fungsi untuk generate pesan notifikasi pesanan
 * @param array $orderData - Data pesanan
 * @return string - Pesan yang akan dikirim
 */
function generatePesanNotifikasiPesanan($orderData) {
    $message = "*🛍️ KONFIRMASI PESANAN*\n";
    $message .= "*Copy&ATK Premium*\n\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $message .= "Halo *{$orderData['nama']}*,\n";
    $message .= "Terima kasih telah berbelanja! 🙏\n\n";
    
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "*📋 DETAIL PESANAN*\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "🔖 No. Pesanan: *{$orderData['no_pesanan']}*\n";
    $message .= "📅 Tanggal: {$orderData['tanggal']}\n";
    $message .= "📊 Status: *{$orderData['status']}*\n\n";
    
    $message .= "*🛒 PRODUK YANG DIPESAN:*\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($orderData['items'] as $index => $item) {
        $no = $index + 1;
        $message .= "{$no}. *{$item['nama']}*\n";
        $message .= "   ├ Jumlah: {$item['jumlah']} pcs\n";
        $message .= "   ├ Harga: Rp " . number_format($item['harga'], 0, ',', '.') . "\n";
        $message .= "   └ Subtotal: Rp " . number_format($item['subtotal'], 0, ',', '.') . "\n";
        
        if ($index < count($orderData['items']) - 1) {
            $message .= "\n";
        }
    }
    
    $message .= "\n━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "*💰 RINCIAN PEMBAYARAN*\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Subtotal: Rp " . number_format($orderData['subtotal'], 0, ',', '.') . "\n";
    $message .= "Ongkir: *GRATIS* 🎉\n";
    
    if ($orderData['diskon'] > 0) {
        $message .= "Diskon: -Rp " . number_format($orderData['diskon'], 0, ',', '.') . " 🏷️\n";
    }
    
    $message .= "─────────────────────\n";
    $message .= "*TOTAL: Rp " . number_format($orderData['total'], 0, ',', '.') . "*\n\n";
    
    $message .= "*💳 Metode Pembayaran:*\n";
    $message .= "└ {$orderData['metode_pembayaran']}\n\n";
    
    if ($orderData['metode_pembayaran'] === 'Transfer') {
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "*🏦 INFORMASI TRANSFER*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📱 Bank BCA\n";
        $message .= "   1234567890\n";
        $message .= "   a.n. Toko ATK\n\n";
        $message .= "📱 Bank Mandiri\n";
        $message .= "   0987654321\n";
        $message .= "   a.n. Toko ATK\n\n";
        $message .= "📱 Bank BNI\n";
        $message .= "   5678901234\n";
        $message .= "   a.n. Toko ATK\n\n";
        $message .= "⚠️ *Nominal Transfer:*\n";
        $message .= "*Rp " . number_format($orderData['total'], 0, ',', '.') . "*\n\n";
        $message .= "📸 Setelah transfer, mohon kirim\n";
        $message .= "   bukti pembayaran ke nomor ini.\n\n";
    } else {
        $message .= "💵 Pembayaran dilakukan saat\n";
        $message .= "   pengambilan barang (COD)\n\n";
    }
    
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "*📍 ALAMAT PENGIRIMAN*\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "{$orderData['alamat']}\n";
    $message .= "📞 {$orderData['telepon']}\n\n";
    
    if (!empty($orderData['catatan'])) {
        $message .= "*📝 Catatan Pesanan:*\n";
        $message .= "\"{$orderData['catatan']}\"\n\n";
    }
    
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
    $message .= "Pesanan Anda di pending\n";
    $message .= "⏰ Kami akan segera menghubungi Anda\n";
    $message .= "📦 Estimasi proses: 1-2 hari kerja\n\n";
    
    $message .= "Jika ada pertanyaan, silakan balas\n";
    $message .= "pesan ini. Kami siap membantu! 😊\n\n";
    
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Terima kasih telah berbelanja! 🎉\n";
    $message .= "*Copy&ATK Premium*\n";
    $message .= "🏪 Solusi ATK & Fotokopi Terpercaya";
    
    return $message;
}

/**
 * Fungsi untuk kirim notifikasi status pesanan berubah
 * @param string $nomorWA - Nomor WhatsApp
 * @param string $namaPelanggan - Nama pelanggan
 * @param string $noPesanan - Nomor pesanan
 * @param string $statusBaru - Status baru pesanan
 * @return array - Response pengiriman
 */
function kirimNotifikasiStatusPesanan($nomorWA, $namaPelanggan, $noPesanan, $statusBaru) {
    $statusEmoji = [
        'Pending' => '⏳',
        'Diproses' => '🔄',
        'Selesai' => '✅',
        'Dibatalkan' => '❌'
    ];
    
    $emoji = $statusEmoji[$statusBaru] ?? '📢';
    
    $message = "*{$emoji} UPDATE STATUS PESANAN*\n";
    $message .= "*Copy&ATK Premium*\n\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
    $message .= "Halo *{$namaPelanggan}*,\n\n";
    $message .= "Status pesanan Anda telah diupdate:\n\n";
    $message .= "🔖 No. Pesanan: *{$noPesanan}*\n";
    $message .= "📊 Status: *{$statusBaru}*\n\n";
    
    if ($statusBaru === 'Diproses') {
        $message .= "Pesanan Anda sedang dikerjakan.\n";
        $message .= "Kami akan segera menghubungi Anda! 📞\n\n";
    } elseif ($statusBaru === 'Selesai') {
        $message .= "Pesanan Anda sudah selesai! 🎉\n";
        $message .= "Silakan ambil di toko kami.\n\n";
    } elseif ($statusBaru === 'Dibatalkan') {
        $message .= "Pesanan Anda telah dibatalkan.\n";
        $message .= "Hubungi kami jika ada pertanyaan.\n\n";
    }
    
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Terima kasih! 🙏\n";
    $message .= "*Copy&ATK Premium*";
    
    $target = formatWhatsAppNumber($nomorWA);
    return kirimWhatsApp($target, $message);
}
?>