<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login | SIRAMBO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Halaman Login SIRAMBO - Sistem Informasi Rekonsiliasi data dan Monitoring PDRB BPS Provinsi Sulawesi Tenggara.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Work+Sans:wght@400;500;600&display=optional" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-white: #ffffff;
            --orange-primary: #FF6B35;
            --orange-light: #FF8E53;
            --orange-dark: #E55A2B;
            --navy-primary: #1A3A5F;
            --navy-light: #2D4F7A;
            --navy-dark: #0F2645;
            --gradient-orange: linear-gradient(135deg, #FF6B35 0%, #FF8E53 100%);
            --gradient-blue: linear-gradient(135deg, #1A3A5F 0%, #2D4F7A 100%);
            --shadow-lg: 0 20px 40px rgba(26, 58, 95, 0.15);
            --shadow-xl: 0 25px 50px rgba(255, 107, 53, 0.12);
        }

        body {
            font-family: "Work Sans", "Segoe UI", sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #fef3e9 100%);
            overflow-x: hidden;
        }

        .heading-font {
            font-family: "Sora", "Segoe UI", sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: var(--shadow-lg);
        }

        .gradient-bg {
            background: var(--gradient-orange);
        }

        .gradient-text {
            background: var(--gradient-orange);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .floating-animation {
            animation: floating 6s ease-in-out infinite;
        }

        .pulse-animation {
            animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .reveal {
            opacity: 0;
            transform: translateY(20px);
            animation: reveal 0.8s ease-out forwards;
        }

        .reveal.delay-1 { animation-delay: 0.1s; }
        .reveal.delay-2 { animation-delay: 0.2s; }
        .reveal.delay-3 { animation-delay: 0.3s; }
        .reveal.delay-4 { animation-delay: 0.4s; }

        @keyframes reveal {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-btn {
            background: var(--gradient-orange);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.7s;
        }

        .login-btn:hover::after {
            left: 100%;
        }

        .input-focus:focus {
            border-color: var(--orange-light);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .circle-ornament {
            position: fixed;
            border-radius: 50%;
            z-index: 0;
            opacity: 0.25;
            filter: blur(6px);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem !important;
                line-height: 1.2 !important;
            }
            
            .subtitle {
                font-size: 1.5rem !important;
            }
            
            .glass-card {
                padding: 1.25rem !important;
            }
            
            .feature-card {
                padding: 1rem !important;
            }
        }

        @media (max-width: 640px) {
            .hero-title {
                font-size: 1.75rem !important;
            }
            
            .nav-links {
                display: none !important;
            }
        }
    </style>
</head>

<body class="min-h-screen overflow-hidden">
    <!-- Background Ornaments - Bulat lembut seperti contoh -->
    <div class="circle-ornament floating-animation" style="width: 220px; height: 220px; top: -40px; left: -60px; background: rgba(255, 107, 53, 0.35);"></div>
    <div class="circle-ornament pulse-animation" style="width: 320px; height: 320px; top: 18%; left: 46%; background: rgba(255, 193, 152, 0.45);"></div>
    <div class="circle-ornament floating-animation" style="width: 260px; height: 260px; bottom: -80px; right: -40px; background: rgba(255, 107, 53, 0.28);"></div>
    <div class="circle-ornament pulse-animation" style="width: 140px; height: 140px; bottom: 18%; left: 6%; background: rgba(255, 193, 152, 0.4);"></div>

    <div class="relative z-10 mx-auto min-h-screen max-w-7xl px-4 sm:px-6 lg:px-8 py-4 lg:py-8">
        <!-- Header Navigation -->
        <nav class="mb-6 lg:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3 sm:gap-4 reveal">
                    <img src="{{ asset('assets/img/logo_bps.png') }}" alt="BPS Logo" width="48" height="48" class="h-10 sm:h-12 w-auto drop-shadow-sm">
                    <div class="text-xs sm:text-sm font-semibold uppercase tracking-[0.1em] sm:tracking-[0.2em] text-navy-dark">
                        BADAN PUSAT STATISTIK
                    </div>
                </div>
                
                <div class="flex items-center justify-between gap-4 sm:gap-6 reveal delay-1">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <img src="{{ asset('assets/img/logo_bps.png') }}" alt="BPS Sultra" width="32" height="32" class="h-7 sm:h-8 w-auto rounded-lg bg-white p-1 shadow-sm">
                        <div class="text-xs font-medium text-navy-dark bg-white/80 px-2 sm:px-3 py-1 sm:py-1.5 rounded-full whitespace-nowrap">
                            <span class="text-orange-primary">●</span> Prov. Sulawesi Tenggara
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="grid lg:grid-cols-2 gap-6 lg:gap-8 xl:gap-10 items-center">
            <!-- Left Content Section -->
            <section class="space-y-6 lg:space-y-8 order-2 lg:order-1">
                <div class="space-y-4 reveal">
                    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-orange-50 to-white px-3 sm:px-4 py-1.5 sm:py-2 shadow-sm">
                        <span class="h-2 w-2 rounded-full gradient-bg"></span>
                        <span class="text-xs sm:text-sm font-medium text-navy-dark">Sistem Terintegrasi</span>
                    </div>
                    
                    <h1 class="heading-font hero-title text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold leading-tight">
                        <span class="text-navy-dark">SIRAMBO</span>
                        <span class="block subtitle text-2xl sm:text-3xl md:text-4xl lg:text-5xl gradient-text">Sistem Rilis & Rekonsiliasi</span>
                    </h1>
                    
                    <p class="text-base sm:text-lg text-navy-light max-w-2xl">
                        Platform terintegrasi untuk sinkronisasi rilis dan rekonsiliasi data PDRB dengan presisi dan jejak audit yang transparan.
                    </p>
                </div>

                <!-- Feature Cards -->
                <div class="hidden sm:grid grid-cols-1 sm:grid-cols-2 gap-4 reveal delay-3">
                    <div class="feature-card glass-card rounded-2xl p-4 sm:p-5 space-y-2">
                        <div class="w-10 h-10 rounded-xl gradient-bg flex items-center justify-center">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                        <h3 class="font-semibold text-navy-dark text-sm sm:text-base">Analisis Real-time</h3>
                        <p class="text-xs sm:text-sm text-navy-light">Monitor data PDRB secara langsung dengan update otomatis</p>
                    </div>
                    
                    <div class="feature-card glass-card rounded-2xl p-4 sm:p-5 space-y-2">
                        <div class="w-10 h-10 rounded-xl gradient-bg flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <h3 class="font-semibold text-navy-dark text-sm sm:text-base">Keamanan </h3>
                        <p class="text-xs sm:text-sm text-navy-light">Proteksi data dengan enkripsi end-to-end dan audit trail</p>
                    </div>
                </div>

                <!-- Bottom Logos -->
                <div class="hidden sm:flex items-center gap-4 sm:gap-6 pt-6 sm:pt-8 reveal delay-4">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('assets/img/logo_color.png') }}" alt="SIRAMBO" width="48" height="48" class="h-10 sm:h-12 w-auto drop-shadow-md">
                        <div class="h-10 sm:h-12 w-px bg-gradient-to-b from-orange-light to-transparent"></div>
                        <div class="text-xs text-navy-light max-w-xs">
                            <span class="font-semibold text-navy-dark">SIRAMBO</span> - Inovasi digital BPS untuk transformasi data ekonomi daerah.
                        </div>
                    </div>
                </div>
            </section>

            <!-- Login Card Section -->
            <section class="relative order-1 lg:order-2">
                <div class="glass-card rounded-2xl sm:rounded-3xl p-6 sm:p-8 md:p-10 relative overflow-hidden reveal delay-2">
                    <img src="{{ asset('assets/img/Gedung_Bps.jpg') }}" alt="Gedung BPS" width="640" height="160" class="lg:hidden w-full h-32 sm:h-40 object-cover rounded-xl mb-4">
                    <!-- Decorative elements -->
                    <div class="absolute top-0 right-0 w-24 sm:w-32 h-24 sm:h-32 bg-gradient-to-br from-orange-primary/5 to-transparent rounded-bl-full"></div>
                    <div class="absolute bottom-0 left-0 w-16 sm:w-20 h-16 sm:h-20 bg-gradient-to-tr from-navy-primary/5 to-transparent rounded-tr-full"></div>
                    
                    <div class="relative z-10">
                        <div class="mb-6 sm:mb-8 space-y-3">
                            <div class="flex items-center justify-between">
                                <h2 class="heading-font text-xl sm:text-2xl font-bold text-navy-dark">Masuk ke Dashboard</h2>
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full gradient-bg flex items-center justify-center">
                                    <i class="fas fa-lock text-white text-sm sm:text-base"></i>
                                </div>
                            </div>
                            <p class="text-xs sm:text-sm text-navy-light">Gunakan kredensial resmi BPS untuk mengakses sistem</p>
                        </div>

                        @if ($errors->any())
                            <div class="mb-4 sm:mb-6 rounded-xl border border-red-200 bg-red-50/80 px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-red-600 flex items-center gap-2">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.post') }}" class="space-y-4 sm:space-y-6">
                            @csrf

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-navy-dark">Email Institusi</label>
                                <div class="relative">
                                    <input type="email" name="email"
                                        value="{{ old('email') }}"
                                        placeholder="nama@bps.go.id"
                                        class="w-full rounded-xl sm:rounded-2xl border border-slate-200 bg-white/90 px-4 sm:px-5 py-3 sm:py-3.5 text-sm text-navy-dark shadow-sm outline-none transition input-focus pl-10 sm:pl-12"
                                        required>
                                    <i class="fas fa-envelope absolute left-3 sm:left-4 top-1/2 transform -translate-y-1/2 text-orange-primary text-sm"></i>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-medium text-navy-dark">Password</label>
                                </div>
                                <div class="relative">
                                    <input type="password" name="password"
                                        placeholder="••••••••"
                                        class="w-full rounded-xl sm:rounded-2xl border border-slate-200 bg-white/90 px-4 sm:px-5 py-3 sm:py-3.5 text-sm text-navy-dark shadow-sm outline-none transition input-focus pl-10 sm:pl-12"
                                        required>
                                    <i class="fas fa-key absolute left-3 sm:left-4 top-1/2 transform -translate-y-1/2 text-orange-primary text-sm"></i>
                                    <button type="button" aria-label="Tampilkan Password" class="absolute right-3 sm:right-4 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-navy-dark">
                                        <i class="far fa-eye text-sm"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="remember" name="remember" class="rounded border-slate-300 text-orange-primary focus:ring-orange-primary">
                                <label for="remember" class="text-xs sm:text-sm text-navy-light">Ingat saya</label>
                            </div>

                            <button type="submit"
                                class="login-btn w-full rounded-xl sm:rounded-2xl py-3 sm:py-4 text-sm font-semibold text-white shadow-lg active:scale-[0.98]">
                                <span class="flex items-center justify-center gap-2">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Masuk ke Dashboard
                                </span>
                            </button>

                            <div class="relative my-4 sm:my-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-slate-200"></div>
                                </div>
                                <div class="relative flex justify-center text-xs uppercase">
                                    <span class="bg-white px-2 text-slate-400">Atau</span>
                                </div>
                            </div>

                            <a href="{{ route('login.sso') }}"
                                class="group flex w-full items-center justify-center gap-3 rounded-xl sm:rounded-2xl border-2 border-navy-primary py-3 sm:py-4 text-sm font-semibold text-navy-primary transition-all duration-300 hover:bg-navy-primary hover:text-white active:scale-[0.98] hover:shadow-lg">
                                <img src="{{ asset('assets/img/logo_bps.png') }}" alt="BPS" width="20" height="20" class="h-5 w-auto transition-all duration-300 group-hover:brightness-0 group-hover:invert">
                                <span>Single Sign-On BPS</span>
                            </a>
            
                        </form>

                        <p class="mt-6 sm:mt-8 text-center text-xs text-slate-400">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Dilindungi oleh sistem keamanan BPS 
                            <span class="block mt-1">© {{ date('Y') }} SIRAMBO • BPS Provinsi Sulawesi Tenggara</span>
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.fa-eye').forEach(icon => {
            icon.addEventListener('click', function() {
                const input = this.closest('.relative').querySelector('input');
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });

        // Input focus effects
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('ring-2', 'ring-orange-100');
            });
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('ring-2', 'ring-orange-100');
            });
        });

        // Responsive adjustments on resize
        window.addEventListener('resize', function() {
            const title = document.querySelector('.hero-title');
            const subtitle = document.querySelector('.subtitle');
            
            if (window.innerWidth < 640) {
                title.style.fontSize = '2rem';
                subtitle.style.fontSize = '1.5rem';
            } else if (window.innerWidth < 768) {
                title.style.fontSize = '2.5rem';
                subtitle.style.fontSize = '1.75rem';
            } else {
                title.style.fontSize = '';
                subtitle.style.fontSize = '';
            }
        });
    </script>
</body>
</html>


    




    