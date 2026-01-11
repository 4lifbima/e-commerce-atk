-- Script untuk update harga beli produk yang sudah ada
-- Gunakan margin ~40-50% untuk alat tulis

-- Stapler Kenko HD-50: Harga Jual Rp 25.000, Modal Rp 15.000 (Margin 40%)
UPDATE produk SET harga_beli = 15000.00 WHERE id = 4;

-- Pulpen Standard: Harga Jual Rp 2.000, Modal Rp 1.000 (Margin 50%)
UPDATE produk SET harga_beli = 1000.00 WHERE id = 1;

-- Pensil 2B Faber Castell: Harga Jual Rp 3.000, Modal Rp 1.500 (Margin 50%)
UPDATE produk SET harga_beli = 1500.00 WHERE id = 2;

-- Kertas HVS A4 70gr: Harga Jual Rp 35.000, Modal Rp 25.000 (Margin ~29%)
UPDATE produk SET harga_beli = 25000.00 WHERE id = 3;
