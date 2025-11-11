<?php
if (!isset($activePage)) {
    $activePage = ''; // default
}
?>

<aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-gradient-to-b from-blue-700 to-blue-600 text-white shadow-2xl z-50">
    
    <!-- Logo -->
    <div class="p-6 border-b border-blue-700">
        <div class="flex items-center space-x-3">
            <i class="fas fa-print text-3xl"></i>
            <div>
                <h1 class="text-xl font-bold">Copy&ATK</h1>
                <p class="text-xs text-blue-300">Admin Panel</p>
            </div>
        </div>
    </div>
    
    <!-- User Info -->
    <div class="p-4 border-b border-blue-700">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                <i class="fas fa-user-shield"></i>
            </div>
            <div>
                <p class="font-semibold text-sm"><?= $_SESSION['nama'] ?></p>
                <p class="text-xs text-blue-300">Administrator</p>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="p-4 space-y-2">

        <!-- Dashboard -->
        <a href="dashboard.php"
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition
           <?= $activePage === 'dashboard' ? 'bg-blue-600' : 'hover:bg-blue-600' ?>">
            <i class="fas fa-chart-line w-5"></i>
            <span>Dashboard</span>
        </a>

        <!-- Produk -->
        <a href="produk.php"
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition
           <?= $activePage === 'produk' ? 'bg-blue-600' : 'hover:bg-blue-600' ?>">
            <i class="fas fa-box w-5"></i>
            <span>Kelola Produk</span>
        </a>

        <!-- Kategori -->
        <a href="kategori.php"
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition
           <?= $activePage === 'kategori' ? 'bg-blue-600' : 'hover:bg-blue-600' ?>">
            <i class="fas fa-tags w-5"></i>
            <span>Kategori</span>
        </a>

        <!-- Pesanan -->
        <a href="pesanan.php"
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition
           <?= $activePage === 'pesanan' ? 'bg-blue-600' : 'hover:bg-blue-600' ?>">
            <i class="fas fa-shopping-cart w-5"></i>
            <span>Pesanan</span>

            <?php if (!empty($pesanan_pending) && $pesanan_pending > 0): ?>
                <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                    <?= $pesanan_pending ?>
                </span>
            <?php endif; ?>
        </a>

        <!-- Order Fotocopy -->
        <a href="fotocopy-orders.php"
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition
           <?= $activePage === 'fotocopy' ? 'bg-blue-600' : 'hover:bg-blue-600' ?>">
            <i class="fas fa-print w-5"></i>
            <span>Order Fotocopy</span>
        </a>

        <!-- Keuangan -->
        <a href="keuangan.php"
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition
           <?= $activePage === 'keuangan' ? 'bg-blue-600' : 'hover:bg-blue-600' ?>">
            <i class="fas fa-wallet w-5"></i>
            <span>Keuangan</span>
        </a>

        <!-- Pengaturan -->
        <a href="settings.php"
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition
           <?= $activePage === 'settings' ? 'bg-blue-600' : 'hover:bg-blue-600' ?>">
            <i class="fas fa-cog w-5"></i>
            <span>Pengaturan</span>
        </a>

        <div class="border-t border-blue-600 pt-2 mt-2">

            <a href="../index.php"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition hover:bg-blue-600">
                <i class="fas fa-globe w-5"></i>
                <span>Ke Website</span>
            </a>

            <a href="../logout.php"
               class="flex items-center space-x-3 px-4 py-3 rounded-lg transition text-white hover:bg-blue-600">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span>Logout</span>
            </a>

        </div>
    </nav>
</aside>
