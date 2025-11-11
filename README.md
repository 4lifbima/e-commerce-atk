# 🖨️ Copy&ATK Premium - E-Commerce Web App

Website E-Commerce lengkap untuk Toko Fotocopy & ATK dengan desain modern dan fitur premium.

## 📋 Daftar Isi
- [Fitur](#fitur)
- [Teknologi](#teknologi)
- [Struktur Folder](#struktur-folder)
- [Instalasi](#instalasi)
- [Akun Demo](#akun-demo)
- [Screenshot](#screenshot)

## ✨ Fitur

### 🔐 Autentikasi
- Login untuk Admin dan Customer
- Register Customer dengan validasi
- Middleware untuk proteksi halaman admin
- Session management yang aman

### 📦 Manajemen Produk ATK
- CRUD lengkap untuk produk (Create, Read, Update, Delete)
- Kategori produk dinamis
- Upload foto produk
- Manajemen stok real-time
- Filter & pencarian produk
- Halaman detail produk

### 🖨️ Layanan Fotocopy
- Form pemesanan fotocopy interaktif
- Pilihan kertas: A4, F4, A3
- Warna: Hitam Putih / Berwarna
- Bolak-balik (Duplex)
- Jilid: Spiral, Lakban, Hardcover
- Kalkulasi otomatis biaya total
- Simpan pesanan ke database

### 🛒 Keranjang & Checkout
- Shopping cart berbasis session
- Update jumlah item di cart
- Hapus item dari cart
- Halaman checkout lengkap
- Metode pembayaran: COD & Transfer Bank
- Input data pengiriman
- Order tracking dengan kode unik

### 📄 Invoice & Struk
- Generate invoice untuk customer
- Cetak struk/nota untuk admin
- PDF export untuk laporan
- Riwayat pesanan lengkap

### 📊 Dashboard Admin
- **Sidebar navigasi modern**
- Statistik real-time:
  - Total penjualan harian
  - Total penjualan bulanan
  - Total pesanan & status
  - Total produk aktif
- **Grafik penjualan 7 hari** (Chart.js)
- Produk terlaris bulan ini
- Alert stok menipis
- Pesanan terbaru
- Status pesanan (Pending, Diproses, Selesai, Dibatalkan)

### 💰 Manajemen Keuangan
- Catatan pemasukan & pengeluaran
- Rekap penjualan per hari/bulan
- Laporan keuangan lengkap
- Export ke PDF
- Filter berdasarkan tanggal

### 🎨 Desain Premium
- **Mobile-first responsive design**
- TailwindCSS untuk styling modern
- Gradient backgrounds
- Smooth animations
- Card hover effects
- Shadow & rounded corners premium
- Sticky navigation bar
- Font Awesome icons

## 🛠️ Teknologi

- **Backend:** PHP Native (Tanpa Framework)
- **Database:** MySQL/MariaDB
- **Frontend:** TailwindCSS (via CDN)
- **Icons:** Font Awesome 6
- **Charts:** Chart.js
- **Session Management:** PHP Sessions
- **Security:** Password hashing, SQL injection prevention

## 📁 Struktur Folder

```
toko-atk-fotocopy/
│
├── config/
│   ├── database.php          # Koneksi database
│   └── session.php            # Session & auth management
│
├── admin/
│   ├── dashboard.php          # Dashboard dengan sidebar
│   ├── produk.php             # CRUD Produk
│   ├── kategori.php           # CRUD Kategori
│   ├── pesanan.php            # Manajemen pesanan
│   ├── fotocopy-orders.php    # Order fotocopy
│   ├── keuangan.php           # Laporan keuangan
│   ├── settings.php           # Pengaturan harga
│   └── includes/
│       ├── header.php         # Header admin
│       └── sidebar.php        # Sidebar admin
│
├── uploads/                   # Folder upload foto produk
│
├── assets/
│   └── css/
│       └── custom.css         # Custom CSS jika diperlukan
│
├── index.php                  # Homepage
├── login.php                  # Halaman login
├── register.php               # Halaman register
├── logout.php                 # Logout handler
│
├── produk.php                 # List semua produk
├── detail-produk.php          # Detail produk
├── cart.php                   # Keranjang belanja
├── cart-action.php            # Handler aksi cart
├── checkout.php               # Halaman checkout
├── checkout-process.php       # Proses checkout
│
├── fotocopy.php               # Form order fotocopy
├── fotocopy-process.php       # Proses order fotocopy
│
├── invoice.php                # Invoice customer
├── pesanan-saya.php           # Riwayat pesanan customer
│
└── README.md                  # Dokumentasi
```

## 🚀 Instalasi

### Persyaratan Sistem
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau MariaDB 10.2+
- Web Server (Apache/Nginx)
- Browser modern

### Langkah Instalasi

1. **Clone atau Download Project**
   ```bash
   git clone https://github.com/username/toko-atk-fotocopy.git
   cd toko-atk-fotocopy
   ```

2. **Buat Database**
   - Buka phpMyAdmin atau MySQL client
   - Buat database baru: `toko_atk_fotocopy`
   - Import file SQL yang sudah disediakan
   - Atau jalankan query dari `database_schema.sql`

3. **Konfigurasi Database**
   
   Edit file `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');          // Sesuaikan username
   define('DB_PASS', '');              // Sesuaikan password
   define('DB_NAME', 'toko_atk_fotocopy');
   ```
 
4. **Buat Folder Upload**
   ```bash
   mkdir uploads
   chmod 755 uploads
   ```

5. **Jalankan Server**
   
   Menggunakan XAMPP/WAMP:
   - Copy folder project ke `htdocs/` atau `www/`
   - Akses: `http://localhost/toko-atk-fotocopy`
   
   Atau menggunakan PHP Built-in Server:
   ```bash
   php -S localhost:8000
   ```

6. **Login Admin**
   - URL: `http://localhost/toko-atk-fotocopy/login.php`
   - Email: `admin@tokofotocopy.com`
   - Password: `admin123`

## 🔑 Akun Demo

### Admin
- Email: `admin@tokofotocopy.com`
- Password: `admin123`

### Customer (Setelah Register)
- Buat akun baru melalui halaman register
- Atau gunakan SQL:
  ```sql
  INSERT INTO users (nama, email, password, role) 
  VALUES ('Customer Demo', 'customer@demo.com', 
          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
          'customer');
  ```
  Password: `demo123`

## 📊 Relasi Database

### Tabel Utama:
1. **users** - Data pengguna (admin & customer)
2. **kategori** - Kategori produk
3. **produk** - Data produk ATK
4. **pesanan** - Data pesanan
5. **detail_pesanan** - Detail item pesanan
6. **pesanan_fotocopy** - Pesanan fotocopy
7. **keuangan** - Catatan keuangan
8. **harga_fotocopy** - Setting harga fotocopy
9. **harga_jilid** - Setting harga jilid

### Relasi:
- `produk.kategori_id` → `kategori.id`
- `pesanan.user_id` → `users.id`
- `detail_pesanan.pesanan_id` → `pesanan.id`
- `detail_pesanan.produk_id` → `produk.id`
- `pesanan_fotocopy.pesanan_id` → `pesanan.id`
- `keuangan.pesanan_id` → `pesanan.id`

## 🎯 Fitur Tambahan (Bonus)

1. **Export Laporan ke PDF**
   - Menggunakan library TCPDF atau DomPDF
   - Laporan penjualan harian/bulanan
   - Invoice professional

2. **Notifikasi Real-time**
   - Alert pesanan baru untuk admin
   - Badge counter di menu

3. **Search & Filter Advanced**
   - Filter berdasarkan kategori
   - Pencarian produk
   - Sort by harga/nama

4. **Rating & Review** (Optional)
   - Customer bisa review produk
   - Rating bintang

5. **Wishlist** (Optional)
   - Simpan produk favorit
   - Berbasis session/database

## 🔒 Keamanan

✅ Password hashing menggunakan `password_hash()`
✅ SQL Injection prevention dengan prepared statements
✅ Session security
✅ XSS prevention
✅ CSRF token untuk form critical
✅ Input validation & sanitization
✅ Middleware authentication

## 📱 Responsive Design

- ✅ Mobile (320px - 767px)
- ✅ Tablet (768px - 1023px)
- ✅ Desktop (1024px+)
- ✅ Sidebar collapse untuk mobile

## 🎨 Color Palette

- Primary: Purple `#9333ea` → `#764ba2`
- Secondary: Pink `#ec4899`
- Success: Green `#10b981`
- Warning: Yellow `#f59e0b`
- Danger: Red `#ef4444`
- Info: Blue `#3b82f6`

## 📞 Support

Jika ada pertanyaan atau issue:
- Email: alif67916@gmail.com
- WhatsApp: 0822-9138-3797

## 📝 License

MIT License - Free to use untuk project komersial maupun personal

## 👨‍💻 Developer

Developed with ❤️ by Alif Bima Pradana

---

**Happy Coding! 🚀**