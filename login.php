<?php
/**
 * Login Page
 * Halaman untuk login admin dan customer
 */

require_once 'config/database.php';
require_once 'config/session.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = escape($_POST['email']);
    $password = $_POST['password'];
    
    $db = getDB();
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = $db->query($query);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            loginUser($user);
            
            // Redirect berdasarkan role
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: ./");
            }
            exit();
        } else {
            $error = 'Email atau password salah!';
        }
    } else {
        $error = 'Email atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Copy&ATK Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #4b4ba2ff 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 bg-[#f0f4ff] relative overflow-hidden">
    <!--  Background Blur Orbs  -->
    <div class="absolute -top-32 -left-32 w-72 h-72 bg-purple-300 rounded-full filter blur-3xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-32 -right-32 w-72 h-72 bg-pink-300 rounded-full filter blur-3xl opacity-70 animate-blob animation-delay-4000"></div>
    
    
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="index.php" class="inline-flex items-center space-x-2">
                <i class="fas fa-print text-4xl text-[#400dd9]"></i>
                <span class="text-3xl font-bold text-[#400dd9]">Copy&ATK</span>
            </a>
            <p class="mt-2 text-[#400dd9]">Premium Stationery Store</p>
        </div>
        
        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-center mb-6 bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                Login ke Akun Anda
            </h2>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Email -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        placeholder="email@example.com"
                    >
                </div>
                
                <!-- Password -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600"
                        placeholder="••••••••"
                    >
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-blue-700 to-blue-600 text-white py-3 rounded-lg font-semibold hover:shadow-xl transition"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Login
                </button>
            </form>
            
            <!-- Register Link -->
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Belum punya akun? 
                    <a href="register.php" class="text-purple-600 font-semibold hover:text-purple-700">
                        Daftar Sekarang
                    </a>
                </p>
            </div>
            
            <!-- Back to Home -->
            <div class="mt-4 text-center">
                <a href="./" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
    
</body>
</html>