<?php
/**
 * Sidebar Partial - Tema Navy dengan Master Wilayah
 */
if (!function_exists('active')) {
    function active($key, $currentPage) {
        return $key === $currentPage ? "active" : "";
    }
}
?>
<aside class="sirambo-sidebar" id="siramboSidebar">
    <!-- Brand Section -->
    <div class="p-4 d-flex align-items-center gap-3">
        <div class="sirambo-logo-square">S</div>
        <div class="lh-1">
            <div class="fw-bold text-white tracking-wide fs-5">SIRAMBO</div>
            <small class="text-white-50" style="font-size: 0.65rem;">BPS Prov. Sultra</small>
        </div>
    </div>

    <!-- Navigasi Utama -->
    <div class="flex-grow-1 px-3 mt-3 overflow-auto">
        <p class="text-uppercase text-white-50 small fw-bold mb-3 px-3" style="font-size: 0.7rem; letter-spacing: 1px; opacity: 0.3;">Navigasi Utama</p>
        
        <nav class="nav flex-column gap-1">
            <a href="/sirambo/pages/dashboard.php" class="nav-link-custom d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?= active('dashboard', $page) ?>">
                <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
            </a>

            <a href="/sirambo/pages/pdrb/pdrb.php" class="nav-link-custom d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?= active('pdrb', $page) ?>">
                <i class="bi bi-bar-chart-line"></i> <span>Rilis & Rekonsiliasi</span>
            </a>

            <!-- Menu Master Wilayah Single -->
            <a href="/sirambo/pages/wilayah/master-wilayah.php" class="nav-link-custom d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?= active('master_wilayah', $page) ?>">
                <i class="bi bi-geo-alt"></i> <span>Master Wilayah</span>
            </a>

            <a href="/sirambo/pages/users/users.php" class="nav-link-custom d-flex align-items-center gap-3 px-3 py-2 rounded-3 <?= active('users', $page) ?>">
                <i class="bi bi-people"></i> <span>Manajemen User</span>
            </a>
        </nav>

        <p class="text-uppercase text-white-50 small fw-bold mt-5 mb-3 px-3" style="font-size: 0.7rem; letter-spacing: 1px; opacity: 0.3;">Operasional</p>
        <nav class="nav flex-column gap-1">
            <a href="#" class="nav-link-custom d-flex align-items-center gap-3 px-3 py-2 rounded-3">
                <i class="bi bi-file-earmark-text"></i> <span>Laporan Bulanan</span>
            </a>
            <a href="#" class="nav-link-custom d-flex align-items-center gap-3 px-3 py-2 rounded-3">
                <i class="bi bi-info-circle"></i> <span>Pusat Bantuan</span>
            </a>
        </nav>
    </div>

    <!-- Sidebar Footer -->
    <div class="p-4 border-top border-white border-opacity-10">
        <div class="d-grid">
            <button class="btn btn-primary btn-sm rounded-3 py-2" onclick="window.scrollTo({top:0, behavior:'smooth'})">
                <i class="bi bi-arrow-up-circle me-1"></i> Ke Atas
            </button>
        </div>
    </div>
</aside>

<style>
    .nav-link-custom {
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        transition: all 0.2s;
        font-weight: 500;
        font-size: 0.95rem;
    }
    .nav-link-custom:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.05);
    }
    .nav-link-custom.active {
        color: #fff;
        background: var(--accent-blue);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
    }
    .nav-link-custom i {
        font-size: 1.1rem;
    }
</style>