<?php
/**
 * Registration Page
 * Halaman registrasi untuk customer baru
 */

require_once 'config/database.php';
require_once 'config/session.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $telepon = trim($_POST['telepon']);
    $alamat = trim($_POST['alamat']);
    
    // Validasi
    if (empty($nama) || empty($email) || empty($password) || empty($telepon)) {
        $error = 'Semua field wajib diisi!';
    } elseif ($password !== $password_confirm) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        $db = getDB();
        $email_escaped = escape($email);
        
        // Cek email sudah terdaftar
        $check_query = "SELECT id FROM users WHERE email = '$email_escaped'";
        $check_result = $db->query($check_query);
        
        if ($check_result->num_rows > 0) {
            $error = 'Email sudah terdaftar! Silakan gunakan email lain.';
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $nama_escaped = escape($nama);
            $telepon_escaped = escape($telepon);
            $alamat_escaped = escape($alamat);
            
            // Insert user baru
            $query = "INSERT INTO users (nama, email, password, role, telepon, alamat) 
                      VALUES ('$nama_escaped', '$email_escaped', '$password_hash', 'customer', '$telepon_escaped', '$alamat_escaped')";
            
            if ($db->query($query)) {
                // Auto login
                $user_id = $db->insert_id;
                $user_data = [
                    'id' => $user_id,
                    'nama' => $nama,
                    'email' => $email,
                    'role' => 'customer'
                ];
                
                loginUser($user_data);
                setFlash('success', 'Registrasi berhasil! Selamat datang, ' . $nama . '!');
                header("Location: index.php");
                exit();
            } else {
                $error = 'Terjadi kesalahan saat registrasi. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Copy&ATK Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 bg-[#f0f4ff]">
    <!--  Background Blur Orbs  -->
    <div class="absolute -top-32 -left-32 w-72 h-72 bg-purple-300 rounded-full filter blur-3xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-32 -right-32 w-72 h-72 bg-pink-300 rounded-full filter blur-3xl opacity-70 animate-blob animation-delay-4000"></div>   
    
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="index.php" class="inline-flex items-center space-x-2 text-white">
                <i class="fas fa-print text-4xl text-[#400dd9]"></i>
                <span class="text-3xl font-bold text-[#400dd9]">Copy&ATK</span>
            </a>
            <p class="text-[#400dd9] mt-2">Buat Akun Baru</p>
        </div>
        
        <!-- Register Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-center mb-6 text-[#400dd9]">
                Daftar Sekarang
            </h2>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Nama -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-user mr-2"></i>Nama Lengkap *
                    </label>
                    <input 
                        type="text" 
                        name="nama" 
                        required 
                        value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        placeholder="John Doe"
                    >
                </div>
                
                <!-- Email -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email *
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        required 
                        value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        placeholder="email@example.com"
                    >
                </div>
                
                <!-- Telepon -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-phone mr-2"></i>No. Telepon *
                    </label>
                    <input 
                        type="tel" 
                        name="telepon" 
                        required 
                        value="<?= isset($_POST['telepon']) ? htmlspecialchars($_POST['telepon']) : '' ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        placeholder="08123456789"
                    >
                </div>
                
                <!-- Alamat -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-map-marker-alt mr-2"></i>Alamat
                    </label>
                    <textarea 
                        name="alamat" 
                        rows="2"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        placeholder="Alamat lengkap (opsional)"
                    ><?= isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : '' ?></textarea>
                </div>
                
                <!-- Password -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-lock mr-2"></i>Password *
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        required 
                        minlength="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        placeholder="Minimal 6 karakter"
                    >
                    <p class="text-xs text-gray-500 mt-1">Password minimal 6 karakter</p>
                </div>
                
                <!-- Konfirmasi Password -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-lock mr-2"></i>Konfirmasi Password *
                    </label>
                    <input 
                        type="password" 
                        name="password_confirm" 
                        required 
                        minlength="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        placeholder="Ketik ulang password"
                    >
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-[#400dd9] text-white py-3 rounded-lg font-semibold hover:shadow-xl transition"
                >
                    <i class="fas fa-user-plus mr-2"></i>
                    Daftar
                </button>
            </form>
            
            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Sudah punya akun? 
                    <a href="login.php" class="text-purple-600 font-semibold hover:text-purple-700">
                        Login di sini
                    </a>
                </p>
            </div>
            
            <!-- Back to Home -->
            <div class="mt-4 text-center">
                <a href="index.php" class="text-gray-500 hover:text-gray-700 text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
    
</body>
</html>