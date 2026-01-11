-- Menambahkan kolom poin_didapat ke tabel pesanan
-- Kolom ini menyimpan jumlah poin yang didapat customer dari pesanan ini

ALTER TABLE pesanan 
ADD COLUMN poin_didapat INT DEFAULT 0 COMMENT 'Jumlah poin yang didapat dari pesanan ini';
