<?php
/**
 * Admin Fonnte WhatsApp Integration
 * Khusus untuk notifikasi dari admin ke customer
 */

require_once '../config/fonnte.php';

/**
 * Kirim notifikasi update status pesanan ke customer
 * @param string $kode_pesanan - Kode pesanan
 * @param string $status_baru - Status baru pesanan
 * @return array - Result pengiriman
 */
function kirimNotifikasiUpdateStatus($kode_pesanan, $status_baru) {
    require_once '../config/database.php';
    $db = getDB();
    
    // Ambil data pesanan
    $kode_pesanan = escape($kode_pesanan);
    $query = "SELECT * FROM pesanan WHERE kode_pesanan = '$kode_pesanan'";
    $result = $db->query($query);
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Pesanan tidak ditemukan'
        ];
    }
    
    $pesanan = $result->fetch_assoc();
    
    // Ambil detail produk
    $pesanan_id = $pesanan['id'];
    $query_detail = "SELECT * FROM detail_pesanan WHERE pesanan_id = $pesanan_id";
    $result_detail = $db->query($query_detail);
    
    $items = [];
    while ($item = $result_detail->fetch_assoc()) {
        $items[] = [
            'nama' => $item['nama_produk'],
            'jumlah' => $item['jumlah'],
            'harga' => $item['harga']
        ];
    }
    
    // Generate pesan berdasarkan status
    $message = generatePesanUpdateStatus(
        $pesanan['nama_customer'],
        $kode_pesanan,
        $status_baru,
        $items,
        $pesanan['total_harga'],
        $pesanan['metode_pembayaran']
    );
    
    // Format nomor WhatsApp
    $whatsappNumber = formatWhatsAppNumber($pesanan['telepon_customer']);
    
    // Kirim pesan
    $result = kirimWhatsApp($whatsappNumber, $message);
    
    // Log pengiriman
    if ($result['success']) {
        $log_status = 'sent';
        $log_response = isset($result['response']) ? json_encode($result['response']) : 'Success';
    } else {
        $log_status = 'failed';
        $log_response = isset($result['error']) ? $result['error'] : 'Unknown error';
    }
    
    // Simpan log ke database
    $query_log = "INSERT INTO notifikasi_log (
        pesanan_id, 
        tipe, 
        nomor_tujuan, 
        status, 
        response, 
        tanggal_kirim
    ) VALUES (
        $pesanan_id,
        'whatsapp_status_update',
        '$whatsappNumber',
        '$log_status',
        '" . escape($log_response) . "',
        NOW()
    )";
    
    $db->query($query_log);
    
    return [
        'success' => $result['success'],
        'message' => $result['success'] ? 'Notifikasi WhatsApp berhasil dikirim' : 'Gagal mengirim notifikasi WhatsApp',
        'wa_result' => $result
    ];
}

/**
 * Generate pesan update status
 */
function generatePesanUpdateStatus($nama, $kode_pesanan, $status, $items, $total, $metode_pembayaran) {
    $message = "";
    
    // Header
    $message .= "*📦 UPDATE STATUS PESANAN*\n";
    $message .= "*Copy&ATK Premium*\n\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $message .= "Hai Kak *{$nama}*! 👋\n\n";
    
    // Status-specific message
    if ($status === 'Pending') {
        $message .= "⏳ *PESANAN MENUNGGU KONFIRMASI*\n\n";
        $message .= "Pesanan Kakak sedang menunggu konfirmasi dari kami.\n\n";
        $message .= "🔖 No. Pesanan: *{$kode_pesanan}*\n";
        $message .= "📊 Status: *Menunggu Konfirmasi*\n\n";
        $message .= "Tim kami akan segera memproses pesanan Kakak. Mohon ditunggu ya! 😊\n\n";
        
    } elseif ($status === 'Diproses') {
        $message .= "🔄 *PESANAN SEDANG DIPROSES*\n\n";
        $message .= "Kabar baik! Pesanan Kakak sudah kami terima dan sedang kami proses dengan penuh perhatian! 📦✨\n\n";
        $message .= "🔖 No. Pesanan: *{$kode_pesanan}*\n";
        $message .= "📊 Status: *Sedang Dikemas*\n\n";
        
        // Detail produk singkat
        $message .= "*🛍️ Produk yang Diproses:*\n";
        $jumlah_item = count($items);
        if ($jumlah_item <= 3) {
            foreach ($items as $idx => $item) {
                $message .= "  " . ($idx + 1) . ". {$item['nama']} ({$item['jumlah']}x)\n";
            }
        } else {
            foreach (array_slice($items, 0, 2) as $idx => $item) {
                $message .= "  " . ($idx + 1) . ". {$item['nama']} ({$item['jumlah']}x)\n";
            }
            $message .= "  ... dan " . ($jumlah_item - 2) . " produk lainnya\n";
        }
        $message .= "\n";
        
        $message .= "⏰ *Estimasi Selesai:* 1-2 hari kerja\n\n";
        $message .= "Tim kami sedang:\n";
        $message .= "✅ Memeriksa kualitas produk\n";
        $message .= "✅ Mengemas dengan rapi\n";
        $message .= "✅ Memastikan pesanan sesuai\n\n";
        $message .= "Kami akan kabari lagi begitu pesanan siap diambil! 🎉\n\n";
        
    } elseif ($status === 'Selesai') {
        $message .= "✅ *PESANAN SIAP DIAMBIL!*\n\n";
        $message .= "Yeay! Pesanan Kakak sudah selesai kami kemas dan siap untuk diambil! 🎊🎉\n\n";
        $message .= "🔖 No. Pesanan: *{$kode_pesanan}*\n";
        $message .= "📊 Status: *Siap Diambil*\n";
        $message .= "💰 Total: *Rp " . number_format($total, 0, ',', '.') . "*\n\n";
        
        // Info pembayaran
        if ($metode_pembayaran === 'COD') {
            $message .= "💵 *Metode Pembayaran:* COD (Bayar di Tempat)\n\n";
            $message .= "📍 *Silakan ambil pesanan di:*\n";
            $message .= "🏪 Copy&ATK Premium\n";
            $message .= "📍 *Jl. Jenderal Sudirman No. 123, RT.01 RW.01*\n";
            $message .= "🕐 Senin - Sabtu: 08.00 - 17.00 WIB\n";
            $message .= "🕐 Minggu: Tutup\n\n";
            $message .= "Jangan lupa bawa uang pas ya Kak! 💵\n\n";
        } else {
            $message .= "💳 *Metode Pembayaran:* Transfer Bank\n\n";
            $message .= "📍 *Silakan ambil pesanan di:*\n";
            $message .= "🏪 Copy&ATK Premium\n";
            $message .= "📍 *Jl. Jenderal Sudirman No. 123, RT.01 RW.01*\n";
            $message .= "🕐 Senin - Sabtu: 08.00 - 17.00 WIB\n";
            $message .= "🕐 Minggu: Tutup\n\n";
        }
        
        $message .= "⚠️ *Catatan Penting:*\n";
        $message .= "• Bawa kode pesanan saat pengambilan\n";
        $message .= "• Cek pesanan sebelum meninggalkan toko\n";
        $message .= "• Pesanan dijamin berkualitas! ✨\n\n";
        $message .= "Terima kasih sudah berbelanja! 🙏\n";
        $message .= "Ditunggu orderan berikutnya ya Kak! 😊\n\n";
        
    } elseif ($status === 'Dibatalkan') {
        $message .= "❌ *PESANAN DIBATALKAN*\n\n";
        $message .= "Mohon maaf, pesanan Kakak telah dibatalkan.\n\n";
        $message .= "🔖 No. Pesanan: *{$kode_pesanan}*\n";
        $message .= "📊 Status: *Dibatalkan*\n\n";
        $message .= "Jika ada pertanyaan atau ingin melakukan pemesanan ulang, silakan hubungi kami ya Kak! 📞\n\n";
        $message .= "Kami siap membantu! 😊\n\n";
    }
    
    // Footer
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    if ($status !== 'Dibatalkan') {
        $message .= "💬 *Ada Pertanyaan?*\n";
        $message .= "Balas pesan ini atau hubungi:\n";
        $message .= "📱 WhatsApp: 6281254773240\n";
        $message .= "📞 Telepon: 6281254773240\n\n";
    }
    
    $message .= "Terima kasih telah mempercayai kami! 🙏\n\n";
    $message .= "*Copy&ATK Premium*\n";
    $message .= "🏪 Solusi ATK & Fotokopi Terpercaya";
    
    return $message;
}

/**
 * Kirim reminder pembayaran untuk pesanan pending transfer
 */
function kirimReminderPembayaran($kode_pesanan) {
    require_once '../config/database.php';
    $db = getDB();
    
    // Ambil data pesanan
    $kode_pesanan = escape($kode_pesanan);
    $query = "SELECT * FROM pesanan WHERE kode_pesanan = '$kode_pesanan' AND metode_pembayaran = 'Transfer' AND status = 'Pending'";
    $result = $db->query($query);
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Pesanan tidak ditemukan atau bukan pesanan transfer pending'
        ];
    }
    
    $pesanan = $result->fetch_assoc();
    
    // Generate pesan reminder
    $message = "*⏰ REMINDER PEMBAYARAN*\n";
    $message .= "*Copy&ATK Premium*\n\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
    $message .= "Hai Kak *{$pesanan['nama_customer']}*! 👋\n\n";
    $message .= "Kami mengingatkan bahwa pesanan Kakak masih menunggu pembayaran:\n\n";
    $message .= "🔖 No. Pesanan: *{$kode_pesanan}*\n";
    $message .= "💰 Total: *Rp " . number_format($pesanan['total_harga'], 0, ',', '.') . "*\n\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "*🏦 INFORMASI TRANSFER*\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "📱 Bank BCA: 1234567890\n";
    $message .= "📱 Bank Mandiri: 0987654321\n";
    $message .= "📱 Bank BNI: 5678901234\n";
    $message .= "a.n. *Toko ATK*\n\n";
    $message .= "⚠️ Mohon transfer sesuai nominal:\n";
    $message .= "*Rp " . number_format($pesanan['total_harga'], 0, ',', '.') . "*\n\n";
    $message .= "📸 Setelah transfer, kirim bukti\n";
    $message .= "   pembayaran ke nomor ini ya Kak!\n\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
    $message .= "Terima kasih! 🙏\n";
    $message .= "*Copy&ATK Premium*";
    
    // Format nomor WhatsApp
    $whatsappNumber = formatWhatsAppNumber($pesanan['telepon_customer']);
    
    // Kirim pesan
    $result = kirimWhatsApp($whatsappNumber, $message);
    
    return [
        'success' => $result['success'],
        'message' => $result['success'] ? 'Reminder pembayaran berhasil dikirim' : 'Gagal mengirim reminder pembayaran'
    ];
}

/**
 * Kirim pesan custom ke customer
 */
function kirimPesanCustom($kode_pesanan, $pesan_custom) {
    require_once '../config/database.php';
    $db = getDB();
    
    // Ambil data pesanan
    $kode_pesanan = escape($kode_pesanan);
    $query = "SELECT * FROM pesanan WHERE kode_pesanan = '$kode_pesanan'";
    $result = $db->query($query);
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Pesanan tidak ditemukan'
        ];
    }
    
    $pesanan = $result->fetch_assoc();
    
    // Generate pesan
    $message = "*💬 PESAN DARI TOKO*\n";
    $message .= "*Copy&ATK Premium*\n\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
    $message .= "Hai Kak *{$pesanan['nama_customer']}*! 👋\n\n";
    $message .= "Terkait pesanan: *{$kode_pesanan}*\n\n";
    $message .= $pesan_custom . "\n\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
    $message .= "Terima kasih! 🙏\n";
    $message .= "*Copy&ATK Premium*";
    
    // Format nomor WhatsApp
    $whatsappNumber = formatWhatsAppNumber($pesanan['telepon_customer']);
    
    // Kirim pesan
    $result = kirimWhatsApp($whatsappNumber, $message);
    
    return [
        'success' => $result['success'],
        'message' => $result['success'] ? 'Pesan berhasil dikirim' : 'Gagal mengirim pesan'
    ];
}
?>