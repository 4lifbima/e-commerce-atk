-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 12, 2025 at 11:25 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `toko_atk_fotocopy`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `validate_kupon` (IN `p_kode_kupon` VARCHAR(50), IN `p_total_belanja` DECIMAL(12,2), OUT `p_valid` BOOLEAN, OUT `p_message` VARCHAR(255), OUT `p_kupon_id` INT, OUT `p_nilai_diskon` DECIMAL(12,2))   BEGIN
    DECLARE v_jenis_diskon VARCHAR(20);
    DECLARE v_nilai_diskon_persen DECIMAL(10,2);
    DECLARE v_min_pembelian DECIMAL(12,2);
    DECLARE v_max_diskon DECIMAL(12,2);
    DECLARE v_kuota INT;
    DECLARE v_kuota_terpakai INT;
    DECLARE v_tgl_mulai DATE;
    DECLARE v_tgl_selesai DATE;
    DECLARE v_status_aktif TINYINT;
    
    -- Default values
    SET p_valid = FALSE;
    SET p_message = '';
    SET p_kupon_id = NULL;
    SET p_nilai_diskon = 0;
    
    -- Get kupon data
    SELECT id, jenis_diskon, nilai_diskon, min_pembelian, max_diskon, 
           kuota, kuota_terpakai, tgl_mulai, tgl_selesai, status_aktif
    INTO p_kupon_id, v_jenis_diskon, v_nilai_diskon_persen, v_min_pembelian, 
         v_max_diskon, v_kuota, v_kuota_terpakai, v_tgl_mulai, v_tgl_selesai, v_status_aktif
    FROM kupon
    WHERE kode_kupon = p_kode_kupon
    LIMIT 1;
    
    -- Check if kupon exists
    IF p_kupon_id IS NULL THEN
        SET p_message = 'Kode kupon tidak valid!';
        
    -- Check if kupon is active
    ELSEIF v_status_aktif = 0 THEN
        SET p_message = 'Kupon sudah tidak aktif!';
        
    -- Check tanggal berlaku
    ELSEIF CURRENT_DATE() < v_tgl_mulai THEN
        SET p_message = 'Kupon belum berlaku!';
    ELSEIF CURRENT_DATE() > v_tgl_selesai THEN
        SET p_message = 'Kupon sudah expired!';
        
    -- Check kuota
    ELSEIF v_kuota IS NOT NULL AND v_kuota_terpakai >= v_kuota THEN
        SET p_message = 'Kuota kupon sudah habis!';
        
    -- Check minimal pembelian
    ELSEIF p_total_belanja < v_min_pembelian THEN
        SET p_message = CONCAT('Minimal pembelian Rp ', FORMAT(v_min_pembelian, 0), '!');
        
    -- Kupon valid, hitung diskon
    ELSE
        SET p_valid = TRUE;
        SET p_message = 'Kupon berhasil diterapkan!';
        
        -- Hitung nilai diskon
        IF v_jenis_diskon = 'Persen' THEN
            SET p_nilai_diskon = (p_total_belanja * v_nilai_diskon_persen / 100);
            
            -- Apply max diskon if exists
            IF v_max_diskon IS NOT NULL AND p_nilai_diskon > v_max_diskon THEN
                SET p_nilai_diskon = v_max_diskon;
            END IF;
        ELSE
            -- Nominal
            SET p_nilai_diskon = v_nilai_diskon_persen;
            
            -- Diskon tidak boleh lebih dari total belanja
            IF p_nilai_diskon > p_total_belanja THEN
                SET p_nilai_diskon = p_total_belanja;
            END IF;
        END IF;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id` int NOT NULL,
  `pesanan_id` int NOT NULL,
  `produk_id` int NOT NULL,
  `nama_produk` varchar(200) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `jumlah` int NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id`, `pesanan_id`, `produk_id`, `nama_produk`, `harga`, `jumlah`, `subtotal`) VALUES
(2, 2, 4, 'Stapler Kenko HD-50', '25000.00', 2, '50000.00'),
(3, 3, 5, 'Lem', '50000.00', 1, '50000.00'),
(6, 6, 5, 'Lem', '50000.00', 1, '50000.00');

-- --------------------------------------------------------

--
-- Table structure for table `hadiah_poin`
--

CREATE TABLE `hadiah_poin` (
  `id` int NOT NULL,
  `nama_hadiah` varchar(255) NOT NULL,
  `deskripsi` text,
  `poin_dibutuhkan` int NOT NULL,
  `stok` int NOT NULL DEFAULT '0',
  `gambar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `hadiah_poin`
--

INSERT INTO `hadiah_poin` (`id`, `nama_hadiah`, `deskripsi`, `poin_dibutuhkan`, `stok`, `gambar_url`, `created_at`, `updated_at`) VALUES
(1, 'Diskon Rp 10.000', 'Voucher diskon senilai Rp 10.000 untuk pembelian berikutnya.', 100, 50, 'https://placehold.co/400x250/1e40af/ffffff?text=VOUCHER+10K', '2025-11-26 04:33:17', '2025-11-26 04:33:17'),
(2, 'Penghapus Joyko Baru', 'Penghapus super bersih merek Joyko.', 50, 100, 'https://placehold.co/400x250/fcd34d/1f2937?text=ERASER', '2025-11-26 04:33:17', '2025-11-26 04:33:17'),
(3, 'Tumbler Eksklusif', 'Tumbler premium dengan logo ATK-FoCo.', 500, 10, 'https://placehold.co/400x250/ef4444/ffffff?text=TUMBLER', '2025-11-26 04:33:17', '2025-11-26 04:33:17'),
(4, 'Gratis Fotokopi 10 Lembar', 'Voucher gratis fotokopi 10 lembar (hitam-putih).', 2, 200, 'uploads/reward_1764882041_3791.jpg', '2025-11-26 04:33:17', '2025-12-05 05:41:44');

-- --------------------------------------------------------

--
-- Table structure for table `harga_fotocopy`
--

CREATE TABLE `harga_fotocopy` (
  `id` int NOT NULL,
  `jenis_kertas` varchar(20) NOT NULL,
  `warna` varchar(20) NOT NULL,
  `bolak_balik` tinyint(1) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `harga_fotocopy`
--

INSERT INTO `harga_fotocopy` (`id`, `jenis_kertas`, `warna`, `bolak_balik`, `harga`, `updated_at`) VALUES
(1, 'A4', 'Hitam Putih', 0, '200.00', '2025-11-25 11:22:20'),
(2, 'A4', 'Hitam Putih', 1, '300.00', '2025-11-25 11:22:20'),
(3, 'A4', 'Berwarna', 0, '1000.00', '2025-11-25 11:22:20'),
(4, 'A4', 'Berwarna', 1, '1500.00', '2025-11-25 11:22:20'),
(5, 'F4', 'Hitam Putih', 0, '250.00', '2025-11-25 11:22:20'),
(6, 'F4', 'Hitam Putih', 1, '350.00', '2025-11-25 11:22:20'),
(7, 'A3', 'Hitam Putih', 0, '500.00', '2025-11-25 11:22:20'),
(8, 'A3', 'Berwarna', 0, '2000.00', '2025-11-25 11:22:20');

-- --------------------------------------------------------

--
-- Table structure for table `harga_jilid`
--

CREATE TABLE `harga_jilid` (
  `id` int NOT NULL,
  `jenis_jilid` varchar(50) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `harga_jilid`
--

INSERT INTO `harga_jilid` (`id`, `jenis_jilid`, `harga`, `updated_at`) VALUES
(1, 'Tidak', '0.00', '2025-11-25 11:22:20'),
(2, 'Spiral', '5000.00', '2025-11-25 11:22:20'),
(3, 'Lakban', '3000.00', '2025-11-25 11:22:20'),
(4, 'Hardcover', '15000.00', '2025-11-25 11:22:20');

-- --------------------------------------------------------

--
-- Table structure for table `history_kupon`
--

CREATE TABLE `history_kupon` (
  `id` int NOT NULL,
  `kupon_id` int NOT NULL,
  `pesanan_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `kode_kupon` varchar(50) NOT NULL,
  `nilai_diskon_didapat` decimal(12,2) NOT NULL,
  `tgl_pakai` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `history_kupon`
--

INSERT INTO `history_kupon` (`id`, `kupon_id`, `pesanan_id`, `user_id`, `kode_kupon`, `nilai_diskon_didapat`, `tgl_pakai`) VALUES
(2, 1, 2, 2, 'WELCOME10', '5000.00', '2025-11-25 20:06:45'),
(3, 1, 3, 2, 'WELCOME10', '5000.00', '2025-11-27 23:18:49'),
(6, 1, 6, 2, 'WELCOME10', '5000.00', '2025-11-28 02:00:16');

-- --------------------------------------------------------

--
-- Table structure for table `history_poin`
--

CREATE TABLE `history_poin` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `pesanan_id` int DEFAULT NULL,
  `jenis` enum('Masuk','Keluar') NOT NULL,
  `jumlah_poin` int NOT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `history_poin`
--

INSERT INTO `history_poin` (`id`, `user_id`, `pesanan_id`, `jenis`, `jumlah_poin`, `keterangan`, `created_at`) VALUES
(3, 2, NULL, 'Masuk', 4, 'Mendapat poin dari pembelian - Order ORD202511273071', '2025-11-27 18:00:16');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `deskripsi`, `created_at`) VALUES
(1, 'Alat Tulis', 'Pulpen, pensil, spidol, dll', '2025-11-25 11:22:20'),
(2, 'Kertas & Buku', 'Kertas HVS, buku tulis, binder, dll', '2025-11-25 11:22:20'),
(3, 'Perlengkapan Kantor', 'Stapler, gunting, lem, dll', '2025-11-25 11:22:20'),
(4, 'Perlengkapan Sekolah', 'Tas, tempat pensil, penggaris, dll', '2025-11-25 11:22:20');

-- --------------------------------------------------------

--
-- Table structure for table `keuangan`
--

CREATE TABLE `keuangan` (
  `id` int NOT NULL,
  `jenis` enum('Pemasukan','Pengeluaran') NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `deskripsi` text,
  `jumlah` decimal(12,2) NOT NULL,
  `tanggal` date NOT NULL,
  `pesanan_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `keuangan`
--

INSERT INTO `keuangan` (`id`, `jenis`, `kategori`, `deskripsi`, `jumlah`, `tanggal`, `pesanan_id`, `created_at`) VALUES
(1, 'Pemasukan', 'Penjualan ATK', 'Penjualan produk - Order ORD202511254881', '45000.00', '2025-11-25', 2, '2025-11-25 12:06:45'),
(2, 'Pemasukan', 'Penjualan ATK', 'Penjualan produk - Order ORD202511271694', '45000.00', '2025-11-27', 3, '2025-11-27 15:18:49'),
(5, 'Pemasukan', 'Penjualan ATK', 'Penjualan produk - Order ORD202511273071', '45000.00', '2025-11-28', 6, '2025-11-27 18:00:16');

-- --------------------------------------------------------

--
-- Table structure for table `kupon`
--

CREATE TABLE `kupon` (
  `id` int NOT NULL,
  `kode_kupon` varchar(50) NOT NULL,
  `nama_kupon` varchar(200) NOT NULL,
  `deskripsi` text,
  `jenis_diskon` enum('Persen','Nominal') NOT NULL,
  `nilai_diskon` decimal(10,2) NOT NULL,
  `min_pembelian` decimal(12,2) DEFAULT '0.00',
  `max_diskon` decimal(12,2) DEFAULT NULL,
  `kuota` int DEFAULT NULL,
  `kuota_terpakai` int DEFAULT '0',
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `status_aktif` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kupon`
--

INSERT INTO `kupon` (`id`, `kode_kupon`, `nama_kupon`, `deskripsi`, `jenis_diskon`, `nilai_diskon`, `min_pembelian`, `max_diskon`, `kuota`, `kuota_terpakai`, `tgl_mulai`, `tgl_selesai`, `status_aktif`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'Diskon 10% untuk Pelanggan Baru', 'Dapatkan diskon 10% untuk pembelian pertama', 'Persen', '10.00', '50000.00', '50000.00', 100, 6, '2025-01-01', '2025-12-31', 1, '2025-11-25 11:23:57', '2025-11-27 18:00:16'),
(2, 'HEMAT20', 'Hemat 20% Belanja Minimal 100rb', 'Diskon 20% untuk belanja minimal Rp 100.000', 'Persen', '20.00', '100000.00', '100000.00', 50, 0, '2025-01-01', '2025-06-30', 1, '2025-11-25 11:23:57', '2025-11-25 11:23:57'),
(3, 'GAJIAN25', 'Diskon 25% Akhir Bulan', 'Promo spesial akhir bulan diskon 25%', 'Persen', '25.00', '200000.00', '150000.00', NULL, 0, '2025-01-25', '2025-12-31', 1, '2025-11-25 11:23:57', '2025-11-25 11:23:57'),
(4, 'POTONG50K', 'Potongan Langsung 50rb', 'Dapatkan potongan Rp 50.000 untuk belanja minimal Rp 300.000', 'Nominal', '50000.00', '300000.00', NULL, 30, 0, '2025-01-01', '2025-03-31', 1, '2025-11-25 11:23:57', '2025-11-25 11:23:57'),
(5, 'GRATIS30K', 'Gratis Ongkir 30rb', 'Potongan langsung Rp 30.000 tanpa minimal pembelian', 'Nominal', '30000.00', '0.00', NULL, NULL, 0, '2025-01-01', '2025-12-31', 1, '2025-11-25 11:23:57', '2025-11-25 11:23:57');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi_log`
--

CREATE TABLE `notifikasi_log` (
  `id` int NOT NULL,
  `pesanan_id` int NOT NULL,
  `tipe` enum('whatsapp','email','sms') NOT NULL DEFAULT 'whatsapp',
  `nomor_tujuan` varchar(20) NOT NULL,
  `status` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `response` text,
  `tanggal_kirim` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifikasi_log`
--

INSERT INTO `notifikasi_log` (`id`, `pesanan_id`, `tipe`, `nomor_tujuan`, `status`, `response`, `tanggal_kirim`) VALUES
(1, 2, 'whatsapp', '6282291383797', 'sent', '{\"detail\":\"success! message in queue\",\"id\":[132392808],\"process\":\"pending\",\"quota\":{\"6281254773240\":{\"details\":\"deduced from total quota\",\"quota\":995,\"remaining\":994,\"used\":1}},\"requestid\":236940788,\"status\":true,\"target\":[\"6282291383797\"]}', '2025-11-25 20:06:47'),
(2, 2, '', '6282291383797', 'sent', '{\"detail\":\"success! message in queue\",\"id\":[132394365],\"process\":\"pending\",\"quota\":{\"6281254773240\":{\"details\":\"deduced from total quota\",\"quota\":994,\"remaining\":993,\"used\":1}},\"requestid\":236962986,\"status\":true,\"target\":[\"6282291383797\"]}', '2025-11-25 20:23:14'),
(3, 2, '', '6282291383797', 'sent', '{\"detail\":\"success! message in queue\",\"id\":[132394559],\"process\":\"pending\",\"quota\":{\"6281254773240\":{\"details\":\"deduced from total quota\",\"quota\":993,\"remaining\":992,\"used\":1}},\"requestid\":236964257,\"status\":true,\"target\":[\"6282291383797\"]}', '2025-11-25 20:25:03'),
(4, 3, 'whatsapp', '6282291383797', 'sent', '{\"detail\":\"success! message in queue\",\"id\":[132720082],\"process\":\"pending\",\"quota\":{\"6281254773240\":{\"details\":\"deduced from total quota\",\"quota\":990,\"remaining\":989,\"used\":1}},\"requestid\":241927286,\"status\":true,\"target\":[\"6282291383797\"]}', '2025-11-27 23:18:51'),
(5, 6, 'whatsapp', '6282291383797', 'sent', '{\"detail\":\"success! message in queue\",\"id\":[132727662],\"process\":\"pending\",\"quota\":{\"6281254773240\":{\"details\":\"deduced from total quota\",\"quota\":989,\"remaining\":988,\"used\":1}},\"requestid\":242140101,\"status\":true,\"target\":[\"6282291383797\"]}', '2025-11-28 02:00:18');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `kode_pesanan` varchar(50) NOT NULL,
  `nama_customer` varchar(100) NOT NULL,
  `email_customer` varchar(100) DEFAULT NULL,
  `telepon_customer` varchar(20) NOT NULL,
  `alamat_pengiriman` text NOT NULL,
  `total_harga` decimal(12,2) NOT NULL,
  `kupon_id` int DEFAULT NULL,
  `kode_kupon` varchar(50) DEFAULT NULL,
  `nilai_diskon` decimal(12,2) DEFAULT '0.00',
  `metode_pembayaran` enum('COD','Transfer') NOT NULL,
  `status` enum('Pending','Diproses','Selesai','Dibatalkan') DEFAULT 'Pending',
  `catatan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `poin_didapat` int DEFAULT '0' COMMENT 'Jumlah poin yang didapat dari pesanan ini'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`id`, `user_id`, `kode_pesanan`, `nama_customer`, `email_customer`, `telepon_customer`, `alamat_pengiriman`, `total_harga`, `kupon_id`, `kode_kupon`, `nilai_diskon`, `metode_pembayaran`, `status`, `catatan`, `created_at`, `updated_at`, `poin_didapat`) VALUES
(2, 2, 'ORD202511254881', 'Member', 'member@tokofotocopy.com', '082291383797', 'Jl. Nome Bungsu', '45000.00', 1, 'WELCOME10', '5000.00', 'COD', 'Selesai', '', '2025-11-25 12:06:45', '2025-11-25 12:25:02', 0),
(3, 2, 'ORD202511271694', 'Member', 'member@tokofotocopy.com', '082291383797', 'Jl. Nome Bungsu', '45000.00', 1, 'WELCOME10', '5000.00', 'Transfer', 'Pending', '', '2025-11-27 15:18:49', '2025-11-27 15:18:49', 0),
(6, 2, 'ORD202511273071', 'Member', 'member@tokofotocopy.com', '082291383797', 'Jl. Nome Bungsu', '45000.00', 1, 'WELCOME10', '5000.00', 'COD', 'Pending', '', '2025-11-27 18:00:16', '2025-11-27 18:00:16', 4);

--
-- Triggers `pesanan`
--
DELIMITER $$
CREATE TRIGGER `after_pesanan_with_kupon` AFTER INSERT ON `pesanan` FOR EACH ROW BEGIN
    IF NEW.kupon_id IS NOT NULL THEN
        -- Update kuota terpakai
        UPDATE kupon 
        SET kuota_terpakai = kuota_terpakai + 1 
        WHERE id = NEW.kupon_id;
        
        -- Insert history
        INSERT INTO history_kupon (kupon_id, pesanan_id, user_id, kode_kupon, nilai_diskon_didapat, tgl_pakai)
        VALUES (NEW.kupon_id, NEW.id, NEW.user_id, NEW.kode_kupon, NEW.nilai_diskon, NOW());
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `pesanan_fotocopy`
--

CREATE TABLE `pesanan_fotocopy` (
  `id` int NOT NULL,
  `pesanan_id` int NOT NULL,
  `jumlah_lembar` int NOT NULL,
  `jenis_kertas` enum('A4','F4','A3') DEFAULT 'A4',
  `warna` enum('Hitam Putih','Berwarna') DEFAULT 'Hitam Putih',
  `bolak_balik` tinyint(1) DEFAULT '0',
  `jilid` enum('Tidak','Spiral','Lakban','Hardcover') DEFAULT 'Tidak',
  `harga_per_lembar` decimal(10,2) NOT NULL,
  `biaya_jilid` decimal(10,2) DEFAULT '0.00',
  `biaya_tambahan` decimal(10,2) DEFAULT '0.00',
  `keterangan_tambahan` text,
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id` int NOT NULL,
  `kategori_id` int NOT NULL,
  `nama_produk` varchar(200) NOT NULL,
  `deskripsi` text,
  `harga_beli` decimal(10,2) NOT NULL DEFAULT '0.00',
  `harga` decimal(10,2) NOT NULL,
  `stok` int NOT NULL DEFAULT '0',
  `foto` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id`, `kategori_id`, `nama_produk`, `deskripsi`, `harga_beli`, `harga`, `stok`, `foto`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Pulpen Pilot G2', 'Pulpen gel premium dengan tinta halus', '1000.00', '5000.00', 100, 'pulpen-pilot.jpg', 1, '2025-11-25 11:22:20', '2025-11-27 18:15:20'),
(2, 1, 'Pensil 2B Faber Castell', 'Pensil grafit berkualitas tinggi', '1500.00', '3000.00', 150, 'pensil-2b.jpg', 1, '2025-11-25 11:22:20', '2025-11-27 18:15:20'),
(3, 2, 'Kertas HVS A4 70gr (1 Rim)', 'Kertas HVS putih 500 lembar', '25000.00', '35000.00', 50, 'hvs-a4.jpg', 1, '2025-11-25 11:22:20', '2025-11-27 18:15:20'),
(4, 3, 'Stapler Kenko HD-50', 'Stapler besar untuk kantor', '15000.00', '25000.00', 28, 'stapler.jpg', 1, '2025-11-25 11:22:20', '2025-11-27 18:15:20'),
(5, 3, 'Lem', 'Lem bening', '45000.00', '50000.00', 0, '1764135412_5596.png', 1, '2025-11-26 05:36:52', '2025-11-27 18:16:46');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_stok`
--

CREATE TABLE `riwayat_stok` (
  `id` int NOT NULL,
  `produk_id` int NOT NULL,
  `jenis_transaksi` enum('Masuk','Keluar') NOT NULL,
  `jumlah` int NOT NULL,
  `stok_awal` int NOT NULL,
  `stok_akhir` int NOT NULL,
  `keterangan` text,
  `tanggal` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting_poin`
--

CREATE TABLE `setting_poin` (
  `id` int NOT NULL,
  `min_belanja_dapat_poin` decimal(12,2) NOT NULL DEFAULT '10000.00',
  `jumlah_poin_didapat` int NOT NULL DEFAULT '1',
  `nilai_tukar_satu_poin` decimal(12,2) NOT NULL DEFAULT '100.00',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `setting_poin`
--

INSERT INTO `setting_poin` (`id`, `min_belanja_dapat_poin`, `jumlah_poin_didapat`, `nilai_tukar_satu_poin`, `updated_at`) VALUES
(1, '10000.00', 1, '100.00', '2025-11-25 11:28:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `telepon` varchar(20) DEFAULT NULL,
  `poin` int DEFAULT '0',
  `alamat` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `telepon`, `poin`, `alamat`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin@tokofotocopy.com', '$2y$10$5Re9ZMNI.sNN2E.qFM/FQerg8jNDCps27v4r5CDi9uBw7icLbgYw6', 'admin', '081234567890', 0, NULL, '2025-11-25 11:22:20', '2025-11-25 11:30:04'),
(2, 'Member', 'member@tokofotocopy.com', '$2y$10$NN/mm49KMmJL7W2Uy0OcVOuRjw1AHK9ig3.qPWszc29L5rI6ER4Yy', 'customer', '082291383797', 4, 'Jl. Nome Bungsu', '2025-11-25 11:33:35', '2025-11-27 18:00:16'),
(3, 'Tajuddin Abdillah', 'tajuddin@ung.ac.id', '$2y$10$1Ms1kjw5ZzSoOWs/beG9TuRE99JqkGC25BZOLUdp4tII2Tw/ALVRS', 'customer', '08124466687', 0, 'bulila, Kec. Telaga', '2025-11-26 05:49:14', '2025-11-26 05:49:14');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_grafik_kategori`
-- (See below for the actual view)
--
CREATE TABLE `view_grafik_kategori` (
`nama_kategori` varchar(100)
,`total_penjualan` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_keuntungan_produk`
-- (See below for the actual view)
--
CREATE TABLE `view_keuntungan_produk` (
`harga_jual_satuan` decimal(10,2)
,`id` int
,`jumlah` int
,`modal_satuan` decimal(10,2)
,`nama_produk` varchar(200)
,`nilai_keuntungan` decimal(21,2)
,`pesanan_id` int
,`tanggal_transaksi` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_laporan_kupon`
-- (See below for the actual view)
--
CREATE TABLE `view_laporan_kupon` (
`jenis_diskon` enum('Persen','Nominal')
,`kode_kupon` varchar(50)
,`kuota` int
,`kuota_terpakai` int
,`nama_kupon` varchar(200)
,`nilai_diskon` decimal(10,2)
,`status_aktif` tinyint(1)
,`tgl_mulai` date
,`tgl_selesai` date
,`total_diskon_diberikan` decimal(34,2)
,`total_penggunaan` bigint
);

-- --------------------------------------------------------

--
-- Structure for view `view_grafik_kategori`
--
DROP TABLE IF EXISTS `view_grafik_kategori`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_grafik_kategori`  AS SELECT `k`.`nama_kategori` AS `nama_kategori`, sum(`dp`.`subtotal`) AS `total_penjualan` FROM (((`detail_pesanan` `dp` join `produk` `p` on((`dp`.`produk_id` = `p`.`id`))) join `kategori` `k` on((`p`.`kategori_id` = `k`.`id`))) join `pesanan` `ps` on((`dp`.`pesanan_id` = `ps`.`id`))) WHERE (`ps`.`status` <> 'Dibatalkan') GROUP BY `k`.`id`, `k`.`nama_kategori``nama_kategori`  ;

-- --------------------------------------------------------

--
-- Structure for view `view_keuntungan_produk`
--
DROP TABLE IF EXISTS `view_keuntungan_produk`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_keuntungan_produk`  AS SELECT `dp`.`id` AS `id`, `dp`.`pesanan_id` AS `pesanan_id`, `ps`.`created_at` AS `tanggal_transaksi`, `p`.`nama_produk` AS `nama_produk`, `dp`.`jumlah` AS `jumlah`, `dp`.`harga` AS `harga_jual_satuan`, `p`.`harga_beli` AS `modal_satuan`, ((`dp`.`harga` - `p`.`harga_beli`) * `dp`.`jumlah`) AS `nilai_keuntungan` FROM ((`detail_pesanan` `dp` join `produk` `p` on((`dp`.`produk_id` = `p`.`id`))) join `pesanan` `ps` on((`dp`.`pesanan_id` = `ps`.`id`))) WHERE (`ps`.`status` = 'Selesai')  ;

-- --------------------------------------------------------

--
-- Structure for view `view_laporan_kupon`
--
DROP TABLE IF EXISTS `view_laporan_kupon`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_laporan_kupon`  AS SELECT `k`.`kode_kupon` AS `kode_kupon`, `k`.`nama_kupon` AS `nama_kupon`, `k`.`jenis_diskon` AS `jenis_diskon`, `k`.`nilai_diskon` AS `nilai_diskon`, `k`.`kuota` AS `kuota`, `k`.`kuota_terpakai` AS `kuota_terpakai`, count(`hk`.`id`) AS `total_penggunaan`, sum(`hk`.`nilai_diskon_didapat`) AS `total_diskon_diberikan`, `k`.`tgl_mulai` AS `tgl_mulai`, `k`.`tgl_selesai` AS `tgl_selesai`, `k`.`status_aktif` AS `status_aktif` FROM (`kupon` `k` left join `history_kupon` `hk` on((`k`.`id` = `hk`.`kupon_id`))) GROUP BY `k`.`id` ORDER BY `k`.`created_at` AS `DESCdesc` ASC  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indexes for table `hadiah_poin`
--
ALTER TABLE `hadiah_poin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `harga_fotocopy`
--
ALTER TABLE `harga_fotocopy`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `harga_jilid`
--
ALTER TABLE `harga_jilid`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `history_kupon`
--
ALTER TABLE `history_kupon`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kupon_id` (`kupon_id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_history_kupon_date` (`tgl_pakai`);

--
-- Indexes for table `history_poin`
--
ALTER TABLE `history_poin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `keuangan`
--
ALTER TABLE `keuangan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`);

--
-- Indexes for table `kupon`
--
ALTER TABLE `kupon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_kupon` (`kode_kupon`),
  ADD KEY `idx_kupon_kode` (`kode_kupon`),
  ADD KEY `idx_kupon_status` (`status_aktif`,`tgl_mulai`,`tgl_selesai`);

--
-- Indexes for table `notifikasi_log`
--
ALTER TABLE `notifikasi_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `status` (`status`),
  ADD KEY `tanggal_kirim` (`tanggal_kirim`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pesanan` (`kode_pesanan`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `kupon_id` (`kupon_id`);

--
-- Indexes for table `pesanan_fotocopy`
--
ALTER TABLE `pesanan_fotocopy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indexes for table `riwayat_stok`
--
ALTER TABLE `riwayat_stok`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indexes for table `setting_poin`
--
ALTER TABLE `setting_poin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `hadiah_poin`
--
ALTER TABLE `hadiah_poin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `harga_fotocopy`
--
ALTER TABLE `harga_fotocopy`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `harga_jilid`
--
ALTER TABLE `harga_jilid`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `history_kupon`
--
ALTER TABLE `history_kupon`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `history_poin`
--
ALTER TABLE `history_poin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `keuangan`
--
ALTER TABLE `keuangan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `kupon`
--
ALTER TABLE `kupon`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifikasi_log`
--
ALTER TABLE `notifikasi_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pesanan_fotocopy`
--
ALTER TABLE `pesanan_fotocopy`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `riwayat_stok`
--
ALTER TABLE `riwayat_stok`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `setting_poin`
--
ALTER TABLE `setting_poin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `history_kupon`
--
ALTER TABLE `history_kupon`
  ADD CONSTRAINT `history_kupon_ibfk_1` FOREIGN KEY (`kupon_id`) REFERENCES `kupon` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `history_kupon_ibfk_2` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `history_kupon_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `history_poin`
--
ALTER TABLE `history_poin`
  ADD CONSTRAINT `history_poin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `keuangan`
--
ALTER TABLE `keuangan`
  ADD CONSTRAINT `keuangan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifikasi_log`
--
ALTER TABLE `notifikasi_log`
  ADD CONSTRAINT `fk_notifikasi_pesanan` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pesanan_ibfk_2` FOREIGN KEY (`kupon_id`) REFERENCES `kupon` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pesanan_fotocopy`
--
ALTER TABLE `pesanan_fotocopy`
  ADD CONSTRAINT `pesanan_fotocopy_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `riwayat_stok`
--
ALTER TABLE `riwayat_stok`
  ADD CONSTRAINT `riwayat_stok_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
