# 📂 Struktur Lengkap File Website

## 🗂️ Struktur Folder Lengkap

```
toko-atk-fotocopy/
│
├── 📁 config/
│   ├── database.php          ✅ [SUDAH DIBUAT]
│   └── session.php            ✅ [SUDAH DIBUAT]
│
├── 📁 admin/
│   ├── dashboard.php          ✅ [SUDAH DIBUAT - dengan sidebar]
│   ├── produk.php             📝 [Perlu dibuat]
│   ├── produk-tambah.php      📝 [Perlu dibuat]
│   ├── produk-edit.php        📝 [Perlu dibuat]
│   ├── produk-delete.php      📝 [Perlu dibuat]
│   ├── kategori.php           📝 [Perlu dibuat]
│   ├── pesanan.php            📝 [Perlu dibuat]
│   ├── pesanan-detail.php     📝 [Perlu dibuat]
│   ├── pesanan-update.php     📝 [Perlu dibuat]
│   ├── fotocopy-orders.php    📝 [Perlu dibuat]
│   ├── keuangan.php           📝 [Perlu dibuat]
│   ├── laporan-pdf.php        📝 [Perlu dibuat]
│   └── settings.php           📝 [Perlu dibuat]
│
├── 📁 uploads/
│   └── .htaccess              📝 [Perlu dibuat - security]
│
├── 📁 assets/
│   └── 📁 css/
│       └── custom.css         📝 [Optional]
│
├── index.php                  ✅ [SUDAH DIBUAT]
├── login.php                  ✅ [SUDAH DIBUAT]
├── register.php               📝 [Perlu dibuat]
├── logout.php                 📝 [Perlu dibuat]
│
├── produk.php                 📝 [Perlu dibuat]
├── detail-produk.php          📝 [Perlu dibuat]
├── cart.php                   📝 [Perlu dibuat]
├── cart-action.php            ✅ [SUDAH DIBUAT]
├── checkout.php               📝 [Perlu dibuat]
├── checkout-process.php       📝 [Perlu dibuat]
│
├── fotocopy.php               ✅ [SUDAH DIBUAT]
├── fotocopy-process.php       📝 [Perlu dibuat]
│
├── invoice.php                📝 [Perlu dibuat]
├── pesanan-saya.php           📝 [Perlu dibuat]
│
├── database_schema.sql        ✅ [SUDAH DIBUAT]
└── README.md                  ✅ [SUDAH DIBUAT]
```

---

## 📝 File yang Perlu Dibuat

### 1️⃣ **register.php** - Halaman Registrasi Customer
**Fitur:**
- Form registrasi: nama, email, password, telepon, alamat
- Validasi email unique
- Password hashing
- Auto login setelah register

**Kode Utama:**
```php
// Proses register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = escape($_POST['nama']);
    $email = escape($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Insert ke database
    $query = "INSERT INTO users (nama, email, password, role) 
              VALUES ('$nama', '$email', '$password', 'customer')";
}
```

---

### 2️⃣ **logout.php** - Logout Handler
**Fitur:**
- Destroy session
- Redirect ke homepage

**Kode:**
```php
require_once 'config/session.php';
logoutUser();
header("Location: index.php");
```

---

### 3️⃣ **produk.php** - Halaman List Produk
**Fitur:**
- Tampilkan semua produk dengan pagination
- Filter berdasarkan kategori
- Search produk
- Sort by harga/nama

**Query Utama:**
```php
$query = "SELECT p.*, k.nama_kategori 
          FROM produk p 
          JOIN kategori k ON p.kategori_id = k.id 
          WHERE p.is_active = 1";
          
// Add search filter
if (!empty($_GET['search'])) {
    $search = escape($_GET['search']);
    $query .= " AND p.nama_produk LIKE '%$search%'";
}

// Add kategori filter
if (!empty($_GET['kategori'])) {
    $kategori_id = (int)$_GET['kategori'];
    $query .= " AND p.kategori_id = $kategori_id";
}

// Add sorting
$sort = $_GET['sort'] ?? 'terbaru';
switch($sort) {
    case 'termurah': $query .= " ORDER BY p.harga ASC"; break;
    case 'termahal': $query .= " ORDER BY p.harga DESC"; break;
    default: $query .= " ORDER BY p.created_at DESC";
}
```

---

### 4️⃣ **detail-produk.php** - Detail Produk
**Fitur:**
- Tampilkan detail lengkap produk
- Foto produk
- Pilih jumlah beli
- Tombol add to cart
- Produk terkait (dari kategori sama)

---

### 5️⃣ **cart.php** - Keranjang Belanja
**Fitur:**
- List item di cart
- Update jumlah item
- Hapus item
- Tampilkan subtotal & total
- Tombol checkout

**Design:**
```html
<table>
  <tr>
    <td>Foto Produk</td>
    <td>Nama Produk</td>
    <td>Harga</td>
    <td>
      <input type="number" name="jumlah" min="1">
      <button>Update</button>
    </td>
    <td>Subtotal</td>
    <td><button>Hapus</button></td>
  </tr>
</table>
```

---

### 6️⃣ **checkout.php** - Halaman Checkout
**Fitur:**
- Form data pengiriman
- Pilih metode pembayaran
- Ringkasan pesanan
- Tombol konfirmasi pesanan

---

### 7️⃣ **checkout-process.php** - Proses Checkout
**Fitur:**
- Insert data pesanan ke tabel `pesanan`
- Insert detail produk ke `detail_pesanan`
- Kurangi stok produk
- Generate kode pesanan unique
- Insert ke tabel `keuangan`
- Clear cart
- Redirect ke invoice

**Kode:**
```php
// Generate kode pesanan
$kode = 'ORD' . date('Ymd') . rand(1000, 9999);

// Insert pesanan
$query = "INSERT INTO pesanan (kode_pesanan, nama_customer, ...) 
          VALUES ('$kode', '$nama', ...)";

// Insert detail
foreach ($cart as $item) {
    $query_detail = "INSERT INTO detail_pesanan (...) VALUES (...)";
    
    // Update stok
    $query_stok = "UPDATE produk SET stok = stok - {$item['jumlah']} 
                   WHERE id = {$item['id']}";
}

// Insert keuangan
$query_keuangan = "INSERT INTO keuangan (jenis, kategori, jumlah, pesanan_id) 
                   VALUES ('Pemasukan', 'Penjualan ATK', $total, $pesanan_id)";
```

---

### 8️⃣ **fotocopy-process.php** - Proses Order Fotocopy
**Fitur:**
- Insert pesanan fotocopy
- Kalkulasi harga berdasarkan spesifikasi
- Upload file dokumen (optional)
- Generate kode pesanan
- Redirect ke invoice

---

### 9️⃣ **invoice.php** - Invoice Customer
**Fitur:**
- Tampilkan detail pesanan
- Info customer
- List produk yang dibeli
- Detail fotocopy (jika ada)
- Total pembayaran
- Status pesanan
- Tombol cetak

**URL:** `invoice.php?kode=ORD20250108XXXX`

---

### 🔟 **pesanan-saya.php** - Riwayat Pesanan Customer
**Fitur:**
- List semua pesanan customer (login required)
- Filter berdasarkan status
- Link ke invoice masing-masing

---

## 🔐 Admin Pages

### 1️⃣ **admin/produk.php** - Manajemen Produk
**Fitur:**
- Tabel list produk dengan pagination
- Tombol tambah, edit, delete
- Search & filter
- Alert stok menipis

---

### 2️⃣ **admin/produk-tambah.php** - Form Tambah Produk
**Fitur:**
- Form input: nama, kategori, harga, stok, deskripsi, foto
- Upload foto produk
- Validasi input

**Upload Handler:**
```php
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['foto']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $new_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], '../uploads/' . $new_name);
    }
}
```

---

### 3️⃣ **admin/produk-edit.php** - Edit Produk
**Fitur:**
- Pre-fill form dengan data existing
- Update data produk
- Replace foto (optional)

---

### 4️⃣ **admin/produk-delete.php** - Hapus Produk
**Fitur:**
- Soft delete (set is_active = 0)
- Atau hard delete dari database

---

### 5️⃣ **admin/kategori.php** - Manajemen Kategori
**Fitur:**
- CRUD kategori
- Inline edit
- List produk per kategori

---

### 6️⃣ **admin/pesanan.php** - Manajemen Pesanan
**Fitur:**
- List semua pesanan
- Filter by status, tanggal
- Update status pesanan
- Link ke detail pesanan

---

### 7️⃣ **admin/pesanan-detail.php** - Detail Pesanan
**Fitur:**
- Info lengkap pesanan
- List produk yang dibeli
- Info fotocopy (jika ada)
- Tombol update status
- Tombol cetak struk

---

### 8️⃣ **admin/pesanan-update.php** - Update Status Pesanan
**Fitur:**
- Form select status: Pending → Diproses → Selesai
- Redirect kembali ke detail

---

### 9️⃣ **admin/fotocopy-orders.php** - Order Fotocopy
**Fitur:**
- List semua order fotocopy
- Filter & search
- Update status

---

### 🔟 **admin/keuangan.php** - Laporan Keuangan
**Fitur:**
- Form input pemasukan/pengeluaran manual
- List transaksi keuangan
- Filter by tanggal
- Rekap: Total Pemasukan, Pengeluaran, Saldo
- Tombol export PDF

**Query Rekap:**
```php
$query_pemasukan = "SELECT SUM(jumlah) as total 
                    FROM keuangan 
                    WHERE jenis = 'Pemasukan' 
                    AND tanggal BETWEEN '$start' AND '$end'";

$query_pengeluaran = "SELECT SUM(jumlah) as total 
                      FROM keuangan 
                      WHERE jenis = 'Pengeluaran' 
                      AND tanggal BETWEEN '$start' AND '$end'";
```

---

### 1️⃣1️⃣ **admin/laporan-pdf.php** - Export Laporan ke PDF
**Fitur:**
- Generate PDF laporan keuangan
- Menggunakan library TCPDF atau FPDF
- Download/print langsung

**Library:** Download TCPDF dari https://tcpdf.org/

```php
require_once('tcpdf/tcpdf.php');

$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Laporan Keuangan', 0, 1, 'C');
// ... tambah konten
$pdf->Output('laporan.pdf', 'D');
```

---

### 1️⃣2️⃣ **admin/settings.php** - Pengaturan Harga
**Fitur:**
- Update harga fotocopy per jenis
- Update harga jilid
- Pengaturan umum aplikasi

---

## 🔒 Security Files

### **uploads/.htaccess**
```apache
# Prevent direct access to uploaded files
<FilesMatch "\.(php|php3|php4|php5|phtml)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Allow images only
<FilesMatch "\.(jpg|jpeg|png|gif|pdf)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>
```

---

## 📊 Tips Implementasi

### 1. **Pagination Helper**
```php
function pagination($total_data, $limit, $current_page) {
    $total_pages = ceil($total_data / $limit);
    $output = '';
    
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $output .= "<a href='?page=$i' class='$active'>$i</a>";
    }
    
    return $output;
}
```

### 2. **Format Tanggal Indonesia**
```php
function tanggal_indo($tanggal) {
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 
              'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    
    $split = explode('-', date('Y-n-j', strtotime($tanggal)));
    return $split[2] . ' ' . $bulan[$split[1]] . ' ' . $split[0];
}
```

### 3. **Generate Unique Order Code**
```php
function generateOrderCode() {
    $prefix = 'ORD';
    $date = date('Ymd');
    $random = rand(1000, 9999);
    
    return $prefix . $date . $random;
}
```

---

## 🎯 Prioritas Pembuatan

### ⭐ High Priority (Harus ada):
1. ✅ Database Schema
2. ✅ Config files (database.php, session.php)
3. ✅ Homepage (index.php)
4. ✅ Login (login.php)
5. ✅ Admin Dashboard (dashboard.php dengan sidebar)
6. ✅ Fotocopy Form (fotocopy.php)
7. 📝 Register (register.php)
8. 📝 Logout (logout.php)
9. 📝 Produk List (produk.php)
10. 📝 Detail Produk (detail-produk.php)
11. 📝 Cart (cart.php)
12. 📝 Checkout (checkout.php + process)
13. 📝 Invoice (invoice.php)
14. 📝 Admin Produk CRUD
15. 📝 Admin Pesanan

### ⭐ Medium Priority:
- Admin Keuangan
- Pesanan Saya (customer)
- Laporan PDF

### ⭐ Low Priority (Nice to have):
- Rating & Review
- Wishlist
- Dark Mode
- Multi-language

---

## 📚 Resources

- **TailwindCSS Docs:** https://tailwindcss.com/docs
- **Font Awesome Icons:** https://fontawesome.com/icons
- **Chart.js Docs:** https://www.chartjs.org/docs/
- **TCPDF Library:** https://tcpdf.org/
- **PHP Manual:** https://www.php.net/manual/

---

**Total Files: ~30 file**
**Estimasi Waktu Pengerjaan: 2-3 hari untuk developer berpengalaman**

✅ = Sudah dibuat
📝 = Perlu dibuat
🔥 = Priority tinggi