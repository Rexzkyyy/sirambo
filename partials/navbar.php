<?php
/**
 * Navbar Partial - Tema Navy
 */
$user_name = $_SESSION['nama_lengkap'] ?? "Pengguna";
?>
<header class="sirambo-navbar sticky-top shadow-none">
    <div class="container-fluid d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <!-- Toggle untuk Mobile -->
            <button class="btn btn-light d-lg-none border-0 shadow-none" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <h5 class="mb-0 fw-bold text-dark d-none d-md-block"><?= htmlspecialchars($title) ?></h5>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Search atau Info Status -->
            <div class="d-none d-lg-flex align-items-center bg-light px-3 py-1 rounded-pill border">
                <i class="bi bi-calendar3 text-primary me-2"></i>
                <small class="fw-medium text-muted"><?= date('d M Y') ?></small>
            </div>

            <!-- User Menu -->
            <div class="dropdown">
                <button class="btn btn-white border rounded-pill d-flex align-items-center gap-2 px-3 py-1 shadow-sm" data-bs-toggle="dropdown">
                    <div class="bg-navy-primary rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #0f172a;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <span class="fw-semibold small d-none d-sm-inline"><?= htmlspecialchars($user_name) ?></span>
                    <i class="bi bi-chevron-down small opacity-50"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 p-2 rounded-4">
                    <li><h6 class="dropdown-header small text-uppercase fw-bold opacity-50">Akun Saya</h6></li>
                    <li><a class="dropdown-item py-2 rounded-3" href="/sirambo/pages/profile.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                    <li><a class="dropdown-item py-2 rounded-3" href="/sirambo/pages/settings.php"><i class="bi bi-gear me-2"></i>Pengaturan</a></li>
                    <li><hr class="dropdown-divider opacity-50"></li>
                    <li><a class="dropdown-item py-2 rounded-3 text-danger" href="/sirambo/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>