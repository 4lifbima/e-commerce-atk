<?php
/**
 * Homepage E-Commerce
 * Menampilkan produk-produk ATK dan layanan fotocopy
 */

require_once 'config/database.php';
require_once 'config/session.php';

$db = getDB();

// Ambil produk terbaru (limit 8)
$query_produk = "SELECT p.*, k.nama_kategori 
                 FROM produk p 
                 JOIN kategori k ON p.kategori_id = k.id 
                 WHERE p.is_active = 1 
                 ORDER BY p.created_at DESC 
                 LIMIT 8";
$result_produk = $db->query($query_produk);

// Ambil kategori untuk filter
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori";
$result_kategori = $db->query($query_kategori);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- icon, android icon, apple icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icon.png">
    <link rel="icon" href="assets/icon.png" type="image/x-icon">
    <title>Toko Fotocopy & ATK - Premium Stationery Store</title>
    <!-- meta, og, twitter, cannonical -->
    <meta name="description" content="Copy&ATK Premium - Toko online terpercaya untuk kebutuhan alat tulis kantor (ATK) dan layanan fotocopy berkualitas. Produk original, harga bersaing, pengiriman cepat.">
    <meta property="og:title" content="Toko Fotocopy & ATK - Premium Stationery Store">
    <meta property="og:description" content="Copy&ATK Premium - Toko online terpercaya untuk kebutuhan alat tulis kantor (ATK) dan layanan fotocopy berkualitas. Produk original, harga bersaing, pengiriman cepat.">
    <meta property="og:image" content="https://ecommerse.kesug.com/assets/hero.png">
    <meta property="og:url" content="https://ecommerse.kesug.com/">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Toko Fotocopy & ATK - Premium Stationery Store">
    <meta name="twitter:description" content="Copy&ATK Premium - Toko online terpercaya untuk kebutuhan alat tulis kantor (ATK) dan layanan fotocopy berkualitas. Produk original, harga bersaing, pengiriman cepat.">
    <meta name="twitter:image" content="https://ecommerse.kesug.com/assets/hero.png">
    <meta rel="canonical" href="https://ecommerse.kesug.com/">

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Custom animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* Card hover effect */
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-white">
    
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-2">
                        <i class="fas fa-print text-[#400dd9] text-2xl"></i>
                        <span class="text-xl font-bold text-[#400dd9]">
                            Copy&ATK Premium
                        </span>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-[#400dd9] transition">Beranda</a>
                    <a href="produk.php" class="text-gray-700 hover:text-[#400dd9] transition">Produk</a>
                    <a href="fotocopy.php" class="text-gray-700 hover:text-[#400dd9] transition">Layanan Fotocopy</a>
                    
                    <?php if (isLoggedIn()): ?>
                    <a href="cart.php" class="relative text-gray-700 hover:text-[#400dd9] transition">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <?php if (getCartCount() > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            <?= getCartCount() ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (isLoggedIn()): ?>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-gray-700 hover:text-[#400dd9]">
                                <i class="fas fa-user-circle text-xl"></i>
                                <span><?= $_SESSION['nama'] ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                                <?php if (isAdmin()): ?>
                                <a href="admin/dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Dashboard Admin</a>
                                <?php else: ?>
                                <a href="pesanan-saya.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Pesanan Saya</a>
                                <?php endif; ?>
                                <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="bg-[#400dd9] text-white px-6 py-2 rounded-full hover:bg-[#350ba1] transition shadow-md">
                            Login
                        </a>
                    <?php endif; ?>
                </div>
                
                <button id="mobile-menu-btn" class="md:hidden text-gray-700">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t">
            <div class="px-4 py-3 space-y-3">
                <a href="index.php" class="block text-gray-700 hover:text-[#400dd9]">Beranda</a>
                <a href="produk.php" class="block text-gray-700 hover:text-[#400dd9]">Produk</a>
                <a href="fotocopy.php" class="block text-gray-700 hover:text-[#400dd9]">Layanan Fotocopy</a>
                <?php if (isLoggedIn()): ?>
                <a href="cart.php" class="block text-gray-700 hover:text-[#400dd9]">Keranjang (<?= getCartCount() ?>)</a>
                    <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php" class="block text-gray-700 hover:text-[#400dd9]">Dashboard Admin</a>
                    <?php else: ?>
                    <a href="pesanan-saya.php" class="block text-gray-700 hover:text-[#400dd9]">Pesanan Saya</a>
                    <?php endif; ?>
                    <a href="logout.php" class="block text-red-600">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="block text-[#400dd9] font-semibold">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <?php if ($flash): ?>
    <div class="max-w-7xl mx-auto px-4 mt-4 fade-in-up">
        <div class="bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded-lg" role="alert">
            <span class="block sm:inline"><?= $flash['message'] ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <section class="relative overflow-hidden py-24 bg-gradient-to-br from-[#3600c9] via-[#400dd9] to-[#6d3bff] text-white">
        <!-- Background Orbs -->
        <div class="absolute -top-40 -left-32 w-[420px] h-[420px] bg-[#8bb5ff] blur-3xl opacity-30 rounded-full"></div>
        <div class="absolute top-10 right-0 w-[480px] h-[480px] bg-[#d4a8ff] blur-3xl opacity-30 rounded-full"></div>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[380px] h-[380px] bg-[#ffd3e6] blur-[120px] opacity-20 rounded-full"></div>

        <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-16">

                <!-- LEFT: Text -->
                <div class="max-w-xl text-center lg:text-left">
                    <h1 class="text-4xl md:text-6xl font-extrabold leading-tight drop-shadow-sm">
                        Solusi Lengkap ATK & Fotocopy
                    </h1>
                    <p class="text-lg md:text-2xl text-gray-200 mt-4 font-light">
                        Produk berkualitas, layanan cepat, harga terjangkau
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 mt-10 justify-center lg:justify-start">
                        <a href="produk.php" class="bg-white/90 backdrop-blur-lg text-[#400dd9] px-8 py-3 rounded-full font-semibold shadow-lg hover:shadow-2xl transition hover:bg-white">
                            <i class="fas fa-shopping-bag mr-2"></i>
                            Belanja Sekarang
                        </a>
                        <?php if (isLoggedIn()): ?>
                        <a href="fotocopy.php" class="bg-white/10 backdrop-blur-xl border border-white/40 text-white px-8 py-3 rounded-full font-semibold hover:bg-white hover:text-[#400dd9] transition shadow-md">
                            <i class="fas fa-print mr-2"></i>
                            Pesan Fotocopy
                        </a>
                        <?php else: ?>
                        <a href="login.php" class="bg-white/10 backdrop-blur-xl border border-white/40 text-white px-8 py-3 rounded-full font-semibold hover:bg-white hover:text-[#400dd9] transition shadow-md">
                            <i class="fas fa-print mr-2"></i>
                          Pesan Fotocopy
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT: Image -->
                <div class="relative flex justify-center lg:justify-end w-full">
                    <div class="backdrop-blur-2xl bg-white/10 border border-white/20 shadow-2xl p-4 rounded-3xl 
                                hover:bg-white/20 hover:border-white/40 transition-all duration-300 
                                max-w-sm md:max-w-md lg:max-w-lg mx-auto">
                        <img src="./assets/hero.png" alt="ATK & Fotocopy" 
                            class="w-full aspect-square object-contain rounded-2xl shadow-xl">
                    </div>

                    <!-- Soft glow behind image -->
                    <div class="absolute inset-0 -z-10 blur-3xl opacity-40 bg-white/30"></div>
                </div>

            </div>
        </div>
    </section>


    
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <div class="text-center p-6 rounded-2xl bg-gray-50 card-hover">
                    <div class="bg-[#400dd9] w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-truck text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Pengiriman Cepat</h3>
                    <p class="text-gray-600">Pesanan sampai dalam hitungan jam</p>
                </div>
                
                <div class="text-center p-6 rounded-2xl bg-gray-50 card-hover">
                    <div class="bg-[#400dd9] w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Produk Berkualitas</h3>
                    <p class="text-gray-600">100% original dari brand terpercaya</p>
                </div>
                
                <div class="text-center p-6 rounded-2xl bg-gray-50 card-hover">
                    <div class="bg-[#400dd9] w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-headset text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Layanan 24/7</h3>
                    <p class="text-gray-600">Customer service siap membantu Anda</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="py-16 bg-gradient-to-b from-[#f7f9fc] to-[#e9eef5] relative overflow-hidden">
        <!-- Background Blur Orbs -->
    <div class="absolute -top-32 -left-20 w-72 h-72 bg-[#a3c6ff] blur-3xl opacity-30 rounded-full"></div>
    <div class="absolute top-10 right-0 w-80 h-80 bg-[#c2a3ff] blur-3xl opacity-30 rounded-full"></div>


        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Produk Terbaru</h2>
                <p class="text-gray-600">Pilihan terbaik untuk kebutuhan ATK Anda</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php while ($produk = $result_produk->fetch_assoc()): ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden card-hover">
                    <div class="h-48 bg-gray-100 flex items-center justify-center">
                        <?php if ($produk['foto']): ?>
                        <img src="uploads/<?= $produk['foto'] ?>" alt="<?= $produk['nama_produk'] ?>" class="h-full w-full object-cover">
                        <?php else: ?>
                        <i class="fas fa-image text-gray-300 text-6xl"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-4">
                        <span class="text-xs bg-blue-100 text-[#400dd9] px-3 py-1 rounded-full font-medium">
                            <?= $produk['nama_kategori'] ?>
                        </span>
                        <h3 class="font-bold text-lg mt-2 mb-1"><?= $produk['nama_produk'] ?></h3>
                        <p class="text-gray-600 text-sm mb-3"><?= substr($produk['deskripsi'], 0, 60) ?>...</p>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-bold text-[#400dd9]">
                                Rp <?= number_format($produk['harga'], 0, ',', '.') ?>
                            </span>
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-box"></i> Stok: <?= $produk['stok'] ?>
                            </span>
                        </div>
                        
                        <div class="mt-4 flex gap-2">
                            <a href="detail-produk.php?id=<?= $produk['id'] ?>" class="flex-1 bg-[#400dd9] text-white text-center py-2 rounded-lg hover:bg-[#350ba1] transition">
                                Detail
                            </a>
                            <?php if (isLoggedIn()): ?>
                            <form method="POST" action="cart-action.php" class="flex-1">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="produk_id" value="<?= $produk['id'] ?>">
                                <input type="hidden" name="jumlah" value="1">
                                <button type="submit" class="w-full bg-white border-2 border-[#400dd9] text-[#400dd9] py-2 rounded-lg hover:bg-blue-50 transition">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <button onclick="window.location.href='login.php'" class="flex-1 bg-white border-2 border-[#400dd9] text-[#400dd9] py-2 rounded-lg hover:bg-blue-50 transition">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div class="text-center mt-12">
                <a href="produk.php" class="inline-block bg-[#400dd9] text-white px-8 py-3 rounded-full font-semibold hover:bg-[#350ba1] hover:shadow-xl transition">
                    Lihat Semua Produk <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>
    
    <section class="py-16 bg-[#400dd9] text-white relative overflow-hidden">
        <!-- Background Blur Orbs -->
        <div class="absolute -top-32 -left-20 w-72 h-72 bg-[#a3c6ff] blur-3xl opacity-30 rounded-full"></div>
        <div class="absolute top-10 right-0 w-80 h-80 bg-[#c2a3ff] blur-3xl opacity-30 rounded-full"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Butuh Layanan Fotocopy?</h2>
            <p class="text-xl mb-8">Hasil berkualitas, harga terjangkau, proses cepat!</p>
            <a href="fotocopy.php" class="inline-block bg-white text-[#400dd9] px-8 py-3 rounded-full font-semibold hover:shadow-xl transition">
                <i class="fas fa-print mr-2"></i>
                Pesan Sekarang
            </a>
        </div>
    </section>

<section class="py-24 bg-gradient-to-b from-[#f7f9fc] to-[#e9eef5] relative overflow-hidden">
    <!-- Background Blur Orbs -->
    <div class="absolute -top-32 -left-20 w-72 h-72 bg-[#a3c6ff] blur-3xl opacity-30 rounded-full"></div>
    <div class="absolute top-10 right-0 w-80 h-80 bg-[#c2a3ff] blur-3xl opacity-30 rounded-full"></div>

    <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900 drop-shadow-sm">
                Meet Our Developers
            </h2>
            <p class="text-gray-600 mt-3 text-lg">Tim kreatif di balik aplikasi ini</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-10">
            
            <!-- Developer Card -->
            <div class="backdrop-blur-xl bg-white/20 border border-white/30 shadow-xl rounded-2xl p-8 text-center 
                        hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 
                        hover:bg-white/30 hover:border-white/50 relative">
                
                <div class="w-32 h-32 mx-auto rounded-full overflow-hidden border-2 border-white/60 shadow-lg mb-5">
                    <img src="./assets/alif.JPG" alt="Developer" class="w-full h-full object-cover">
                </div>

                <h3 class="text-2xl font-semibold text-gray-900">Alif Bima Pradana</h3>
                <p class="text-gray-700 text-sm mb-5 opacity-90">Fullstack Developer</p>

                <div class="flex justify-center space-x-5">
                    <a href="https://github.com/4lifbima" class="text-gray-700 hover:text-gray-900 transition text-2xl"><i class="fab fa-github"></i></a>
                    <a href="https://id.linkedin.com/in/alif-bima-pradana" class="text-gray-700 hover:text-gray-900 transition text-2xl"><i class="fab fa-linkedin"></i></a>
                    <a href="https://www.instagram.com/alifbimapradana07" class="text-gray-700 hover:text-gray-900 transition text-2xl"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <!-- Developer Card -->
            <div class="backdrop-blur-xl bg-white/20 border border-white/30 shadow-xl rounded-2xl p-8 text-center 
                        hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 
                        hover:bg-white/30 hover:border-white/50 relative">
                
                <div class="w-32 h-32 mx-auto rounded-full overflow-hidden border-2 border-white/60 shadow-lg mb-5">
                    <img src="./assets/alya.jpeg" alt="Developer" class="w-full h-full object-cover">
                </div>

                <h3 class="text-2xl font-semibold text-gray-900">Alya Awalia Tokalang</h3>
                <p class="text-gray-700 text-sm mb-5 opacity-90">UI/UX Design</p>

                <div class="flex justify-center space-x-5">
                    <a href="#" class="text-gray-700 hover:text-gray-900 transition text-2xl"><i class="fab fa-behance"></i></a>
                    <a href="#" class="text-gray-700 hover:text-gray-900 transition text-2xl"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="text-gray-700 hover:text-gray-900 transition text-2xl"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <!-- Developer Card -->
            <div class="backdrop-blur-xl bg-white/20 border border-white/30 shadow-xl rounded-2xl p-8 text-center 
                        hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 
                        hover:bg-white/30 hover:border-white/50 relative">
                
                <div class="w-32 h-32 mx-auto rounded-full overflow-hidden border-2 border-white/60 shadow-lg mb-5">
                    <img src="./assets/dea.jpeg" alt="Developer" class="w-full h-full object-cover">
                </div>

                <h3 class="text-2xl font-semibold text-gray-900">Dea Maharani R. Yunus</h3>
                <p class="text-gray-700 text-sm mb-5 opacity-90">UI/UX Design</p>

                <div class="flex justify-center space-x-5">
                    <a href="#" class="text-gray-700 hover:text-gray-900 transition text-2xl"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-700 hover:text-gray-900 transition text-2xl"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="text-gray-700 hover:text-gray-900 transition text-2xl"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>

        </div>
    </div>
</section>


    
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <i class="fas fa-print text-white text-2xl"></i>
                        <span class="text-xl font-bold text-white">
                            Copy&ATK Premium
                        </span>
                    <p class="text-gray-400">Solusi terpercaya untuk kebutuhan ATK dan fotocopy Anda</p>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold mb-4">Menu</h3>
                    <ul class="space-y-2">
                        <li><a href="produk.php" class="text-gray-400 hover:text-white">Produk</a></li>
                        <li><a href="fotocopy.php" class="text-gray-400 hover:text-white">Layanan Fotocopy</a></li>
                        <li><a href="cart.php" class="text-gray-400 hover:text-white">Keranjang</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold mb-4">Kontak</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><i class="fas fa-phone mr-2"></i> 0822-9138-3797</li>
                        <li><i class="fas fa-envelope mr-2"></i> info@copyatk.com</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i> Tilongkabila, Bonebolango</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-xl font-bold mb-4">Ikuti Kami</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="bg-white text-[#400dd9] w-10 h-10 rounded-full flex items-center justify-center hover:shadow-lg transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="bg-white text-[#400dd9] w-10 h-10 rounded-full flex items-center justify-center hover:shadow-lg transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="bg-white text-[#400dd9] w-10 h-10 rounded-full flex items-center justify-center hover:shadow-lg transition">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 Copy&ATK Premium. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Mobile Menu Toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>