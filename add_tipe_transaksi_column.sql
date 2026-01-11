-- Tambahkan kolom tipe_transaksi ke tabel pesanan untuk membedakan transaksi online/offline
ALTER TABLE `pesanan` 
ADD COLUMN `tipe_transaksi` ENUM('Online', 'Offline') DEFAULT 'Online' AFTER `status`;

