<?php
// proses login
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';

// redirect jika sudah login
if (isset($_SESSION['id_user'])) {
    header('Location: /sirambo/pages/dashboard.php');
    exit();
}

// siapkan token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Token tidak valid. Silakan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            $stmt = $conn->prepare('SELECT id_user, username, email, password, role, kode_wilayah, is_active FROM users WHERE (username = ? OR email = ?) LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('ss', $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if ($user) {
                    $storedPassword = (string)$user['password'];
                    $isPasswordValid = password_verify($password, $storedPassword) || hash_equals($storedPassword, (string)$password);

                    if (!$user['is_active']) {
                        $error = 'Akun tidak aktif. Hubungi admin.';
                    } elseif ($isPasswordValid) {
                        $_SESSION['id_user'] = $user['id_user'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['kode_wilayah'] = $user['kode_wilayah'];

                        $update = $conn->prepare('UPDATE users SET last_login = NOW() WHERE id_user = ?');
                        if ($update) {
                            $update->bind_param('s', $user['id_user']);
                            $update->execute();
                            $update->close();
                        }

                        header('Location: /sirambo/pages/dashboard.php');
                        exit();
                    } else {
                        $error = 'Username atau password salah.';
                    }
                } else {
                    $error = 'Username atau password salah.';
                }

                $stmt->close();
            } else {
                $error = 'Koneksi database gagal. Coba beberapa saat lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="SIRAMBO Digital BPS - Sistem Rilis & Rekonsiliasi PDRB Online Badan Pusat Statistik">
    <title>SIRAMBO Digital — Masuk ke Sistem</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Framework & Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Configuration Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bpsBlue: '#1e40af',
                        bpsLightBlue: '#3b82f6',
                        bpsCyan: '#06b6d4',
                        bpsNavy: '#1e293b'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
                        'slide-up': 'slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                        'float': 'float 6s ease-in-out infinite'
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { transform: 'translateY(20px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
                        float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-15px)' } }
                    }
                }
            }
        }
    </script>

    <style type="text/css">
        /* Base Overrides */
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
            background-color: #fcfdfe;
        }

        /* Background Visuals */
        .hero-bg {
            background-image: url('../assets/img/Gedung_Bps.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .hero-gradient {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.95) 0%, rgba(15, 23, 42, 0.85) 100%);
        }

        /* Custom Glassmorphism */
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Form Interaction */
        .input-focus-ring:focus {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }
        
        /* Loading spinner */
        .spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="overflow-hidden bg-slate-50">

    <!-- MAIN WRAPPER -->
    <main class="flex min-h-screen relative">

        <!-- PANEL KIRI: FORM LOGIN -->
        <section class="w-full lg:w-[420px] xl:w-[480px] bg-white z-20 flex flex-col shadow-2xl">
            
            <!-- Header Mobile Only -->
            <div class="lg:hidden p-6 flex justify-between items-center border-b border-slate-100">
                <img src="../assets/img/logo_color.png" class="h-8" alt="BPS" onerror="this.src='https://placehold.co/100x40?text=BPS'">
                <span class="text-xs font-bold text-blue-700 tracking-tighter uppercase">SIRAMBO Digital</span>
            </div>

            <div class="flex-1 flex flex-col justify-center px-8 sm:px-12 lg:px-16 py-8">
                
                <!-- Brand Identity -->
                <div class="mb-8 text-center lg:text-left animate-fade-in">
                    <img src="../assets/img/logo_color.png" class="hidden lg:block h-12 mb-6 mx-auto lg:mx-0 drop-shadow-sm" alt="SIRAMBO Digital BPS" onerror="this.style.display='none'">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-2">
                        Portal Login <span class="text-blue-600 font-black">SIRAMBO</span>
                    </h1>
                    <p class="text-slate-500 text-sm font-medium">
                        Sistem Rilis & Rekonsiliasi PDRB Online Badan Pusat Statistik.
                    </p>
                </div>

                <!-- Notifikasi Error -->
                <?php if (!empty($error)): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl animate-slide-up shadow-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500 mt-1"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-semibold text-red-800">Autentikasi Gagal</p>
                                <p class="text-xs text-red-700 mt-0.5"><?= htmlspecialchars($error) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form Login -->
                <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6 animate-slide-up" id="loginForm">
                    <!-- Token Keamanan -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <!-- Input Username -->
                    <div class="group">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-widest mb-2 ml-1">Username / Email</label>
                        <div class="relative transition-all duration-300">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600">
                                <i class="fas fa-user-circle text-lg"></i>
                            </div>
                            <input type="text" name="username" required
                                class="block w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 text-sm font-medium outline-none transition-all input-focus-ring focus:bg-white focus:border-blue-400"
                                placeholder="Username atau Email"
                                autocomplete="username"
                                value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                        </div>
                    </div>

                    <!-- Input Password -->
                    <div class="group">
                        <div class="relative transition-all duration-300">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600">
                                <i class="fas fa-lock text-lg"></i>
                            </div>
                            <input type="password" name="password" id="passwordField" required
                                class="block w-full pl-11 pr-12 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 text-sm font-medium outline-none transition-all input-focus-ring focus:bg-white focus:border-blue-400"
                                placeholder="••••••••"
                                autocomplete="current-password">
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Versi Info -->
                    <div class="flex items-center justify-between py-2">
                        <span class="text-[10px] text-slate-400 font-medium italic">v1.0-Non_stable</span>
                    </div>

                    <!-- Tombol Submit -->
                    <button type="submit" id="btnSubmit"
                        class="group relative w-full flex justify-center py-4 px-4 border border-transparent rounded-2xl text-sm font-bold text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all shadow-lg shadow-blue-200 active:scale-[0.98]">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-4 opacity-0 group-hover:opacity-100 transition-opacity">
                            <i class="fas fa-arrow-right animate-pulse"></i>
                        </span>
                        Akses Sistem Sekarang
                    </button>
                </form>

                <!-- Support Section & Footer -->
                <div class="mt-8 pt-8 border-t border-slate-100">
                    <!-- Support Info -->
                    <div class="flex flex-col space-y-3 mb-6">
                        <div class="flex items-center space-x-3 text-slate-500">
                            <div class="w-6 h-6 rounded-full bg-slate-50 flex items-center justify-center text-xs">
                                <i class="fas fa-info text-xs"></i>
                            </div>
                            <span class="text-xs font-medium leading-tight">Gunakan akun <strong>Single Sign On (SSO)</strong> BPS untuk masuk.</span>
                        </div>
                        <div class="flex items-center space-x-3 text-slate-500">
                            <div class="w-6 h-6 rounded-full bg-slate-50 flex items-center justify-center text-xs">
                                <i class="fas fa-headset text-xs"></i>
                            </div>
                            <span class="text-xs font-medium leading-tight">Kendala login? Hubungi <a href="mailto:it@bps.go.id" class="text-blue-600 hover:underline">Tim IT Helpdesk</a> lokal.</span>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="text-center pt-4 border-t border-slate-100">
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em]">
                            &copy; 2026 BPS Prov. Sultra • Neraca Nasional
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- PANEL KANAN: VISUAL PANEL (Hero Section) -->
        <section class="hidden lg:block lg:flex-1 hero-bg relative overflow-hidden group">
            <!-- Gradient Overlay Dinamis -->
            <div class="hero-gradient absolute inset-0 z-10 transition-opacity duration-1000 group-hover:opacity-90"></div>

            <!-- Konten Dekoratif (Shapes) -->
            <div class="absolute top-0 right-0 p-20 z-10 opacity-20">
                <svg width="400" height="400" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="200" cy="200" r="190" stroke="white" stroke-width="2" stroke-dasharray="20 20"/>
                    <circle cx="200" cy="200" r="150" stroke="white" stroke-width="1" stroke-dasharray="10 10"/>
                </svg>
            </div>

            <div class="relative z-20 h-full flex flex-col justify-center items-center px-12 xl:px-24">
                
                <div class="max-w-4xl w-full text-center">
                    
                    <!-- Logo Putih BPS -->
                    <div class="mb-12 inline-block animate-float">
                        <img src="../assets/img/logo_bps.png" class="h-20 drop-shadow-[0_10px_10px_rgba(0,0,0,0.5)]" alt="BPS White" onerror="this.style.display='none'">
                    </div>

                    <!-- Heading Utama -->
                    <div class="space-y-4 mb-12">
                        <h2 class="text-5xl xl:text-7xl font-black text-white tracking-tighter leading-none drop-shadow-xl">
                            SIRAMBO <span class="text-cyan-400 italic">Digital</span>
                        </h2>
                        <div class="h-1.5 w-32 bg-cyan-400 mx-auto rounded-full"></div>
                        <p class="text-lg xl:text-xl text-blue-100/90 font-medium max-w-2xl mx-auto leading-relaxed drop-shadow-md">
                            Sistem Rilis & Rekonsiliasi PDRB Online yang akuntabel, transparan, dan efisien bagi insan statistik.
                        </p>
                    </div>

                    <!-- Grid Fitur Utama -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-16">
                        <!-- Fitur 1 -->
                        <div class="glass-effect p-8 rounded-[2.5rem] text-left hover:bg-white/20 transition-all duration-500 cursor-default group/card translate-y-0 hover:-translate-y-2">
                            <div class="w-14 h-14 bg-cyan-400/20 rounded-2xl flex items-center justify-center text-cyan-400 text-2xl mb-6 shadow-inner transition-transform duration-500 group-hover/card:rotate-12">
                                <i class="fas fa-database"></i>
                            </div>
                            <h3 class="text-white font-bold text-lg mb-2">Rekonsiliasi PDRB</h3>
                            <p class="text-blue-100/70 text-xs leading-relaxed font-medium">Integrasi dan harmonisasi data PDRB antar periode secara real-time.</p>
                        </div>

                        <!-- Fitur 2 -->
                        <div class="glass-effect p-8 rounded-[2.5rem] text-left hover:bg-white/20 transition-all duration-500 cursor-default group/card translate-y-0 hover:-translate-y-2">
                            <div class="w-14 h-14 bg-blue-400/20 rounded-2xl flex items-center justify-center text-blue-300 text-2xl mb-6 shadow-inner transition-transform duration-500 group-hover/card:rotate-12">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 class="text-white font-bold text-lg mb-2">Rilis Data Terpadu</h3>
                            <p class="text-blue-100/70 text-xs leading-relaxed font-medium">Penerbitan data PDRB dengan standar validitas dan konsistensi tinggi.</p>
                        </div>

                        <!-- Fitur 3 -->
                        <div class="glass-effect p-8 rounded-[2.5rem] text-left hover:bg-white/20 transition-all duration-500 cursor-default group/card translate-y-0 hover:-translate-y-2">
                            <div class="w-14 h-14 bg-indigo-400/20 rounded-2xl flex items-center justify-center text-indigo-300 text-2xl mb-6 shadow-inner transition-transform duration-500 group-hover/card:rotate-12">
                                <i class="fas fa-clock-rotate-left"></i>
                            </div>
                            <h3 class="text-white font-bold text-lg mb-2">Monitoring Real-time</h3>
                            <p class="text-blue-100/70 text-xs leading-relaxed font-medium">Pantau status rekonsiliasi dan rilis data kapan saja dan di mana saja.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- SCRIPTS -->
    <script>
        /**
         * Global Variables & Selectors
         */
        const passwordField = document.getElementById('passwordField');
        const toggleIcon = document.getElementById('toggleIcon');
        const btnSubmit = document.getElementById('btnSubmit');
        const loginForm = document.getElementById('loginForm');

        /**
         * 1. Toggle Password Visibility
         */
        function togglePassword() {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        /**
         * 2. Form Submission Visual Effect
         */
        loginForm.addEventListener('submit', (e) => {
            // Validasi client-side sederhana
            const username = loginForm.querySelector('input[name="username"]').value.trim();
            const password = passwordField.value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Username dan password harus diisi!');
                return;
            }
            
            // Ubah tampilan tombol submit
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = `
                <i class="fas fa-spinner spinner mr-2"></i> Memproses...
            `;
            btnSubmit.classList.add('opacity-80', 'cursor-not-allowed');
            
            // Simpan data form sebelum submit
            sessionStorage.setItem('loginAttempt', 'true');
        });

        /**
         * 3. Auto-focus pada input username jika halaman baru dimuat
         */
        document.addEventListener('DOMContentLoaded', () => {
            const usernameInput = document.querySelector('input[name="username"]');
            if (usernameInput && !usernameInput.value) {
                usernameInput.focus();
            }
            
            // Cek apakah ada error message
            const errorDiv = document.querySelector('.bg-red-50');
            if (errorDiv) {
                // Auto scroll ke error message
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        /**
         * 4. Keyboard shortcuts
         */
        document.addEventListener('keydown', (e) => {
            // Ctrl + Enter untuk submit form
            if (e.ctrlKey && e.key === 'Enter') {
                loginForm.submit();
            }
            
            // Esc untuk focus ke username
            if (e.key === 'Escape') {
                document.querySelector('input[name="username"]').focus();
            }
        });

        /**
         * 5. Console Log Branding
         */
        console.log("%cSIRAMBO Digital BPS", "color: #1e40af; font-size: 30px; font-weight: bold;");
        console.log("Sistem Rilis & Rekonsiliasi PDRB Online");
        console.log("System Status: Online | Version: 2.1.0 Stable | BPS Prov. Sultra 2026");
        console.log("%c⚠️ PERHATIAN: Jangan masukkan kode sembarangan di console!", "color: red; font-weight: bold;");
    </script>
</body>
</html>