<?php
/**
 * Dashboard Page - Tema Navy Blue
 */
require_once __DIR__ . "/../partials/auth_guard.php";
$title = "Ringkasan Statistik";
$page  = "dashboard";

include __DIR__ . "/../partials/header.php";
?>

<div class="sirambo-wrapper">
    <!-- Sidebar -->
    <?php include __DIR__ . "/../partials/sidebar.php"; ?>

    <!-- Bagian Utama -->
    <div class="sirambo-main">
        <!-- Navbar -->
        <?php include __DIR__ . "/../partials/navbar.php"; ?>

        <!-- Konten Utama -->
        <main class="sirambo-content-area">
            <!-- Header Konten -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-8">
                    <div class="card card-sirambo bg-white p-4 h-100 border-0">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="fw-bold text-navy-primary mb-2">Halo, <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User') ?>!</h4>
                                <p class="text-muted mb-4">Selamat datang kembali di dashboard SIRAMBO. Berikut adalah ringkasan progres data rilis hari ini.</p>
                                <a href="/sirambo/pages/pdrb.php" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm border-0">
                                    <i class="bi bi-plus-lg me-2"></i>Input Data Baru
                                </a>
                            </div>
                            <div class="col-md-4 d-none d-md-block text-center">
                                <i class="bi bi-bar-chart-fill text-primary opacity-10" style="font-size: 6rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card card-sirambo bg-primary text-white h-100 border-0 p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="fw-bold mb-1">Target Triwulan IV</div>
                            <p class="small opacity-75">Batas waktu tinggal 12 hari lagi.</p>
                        </div>
                        <div class="d-flex align-items-end justify-content-between">
                            <h2 class="fw-bold mb-0">92%</h2>
                            <i class="bi bi-speedometer fs-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Statistik -->
            <div class="row g-4">
                <?php
                $stats = [
                    ['title' => 'Total Rilis PDRB', 'value' => '32', 'icon' => 'bi-file-earmark-check', 'color' => 'primary'],
                    ['title' => 'Rekonsiliasi Selesai', 'value' => '14', 'icon' => 'bi-check2-circle', 'color' => 'success'],
                    ['title' => 'User Terdaftar', 'value' => '17', 'icon' => 'bi-people', 'color' => 'info'],
                ];
                foreach ($stats as $s):
                ?>
                <div class="col-md-4">
                    <div class="card card-sirambo bg-white p-4 border-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-medium mb-1"><?= $s['title'] ?></div>
                                <h3 class="fw-bold mb-0"><?= $s['value'] ?></h3>
                            </div>
                            <div class="bg-<?= $s['color'] ?> bg-opacity-10 text-<?= $s['color'] ?> p-3 rounded-4">
                                <i class="bi <?= $s['icon'] ?> fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Tabel Singkat / Aktivitas Terbaru -->
            <div class="card card-sirambo bg-white border-0 mt-4 overflow-hidden">
                <div class="card-header bg-white border-0 p-4 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-navy-primary">Aktivitas Terbaru</h5>
                    <button class="btn btn-light btn-sm rounded-pill px-3">Lihat Semua</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 border-0 small text-uppercase text-muted">Aktivitas</th>
                                    <th class="py-3 border-0 small text-uppercase text-muted">Kategori</th>
                                    <th class="py-3 border-0 small text-uppercase text-muted">Waktu</th>
                                    <th class="pe-4 py-3 border-0 small text-uppercase text-muted text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="ps-4 fw-medium">Update PDRB Kota Kendari</td>
                                    <td>Rilis Tahunan</td>
                                    <td class="small text-muted">2 jam yang lalu</td>
                                    <td class="pe-4 text-end"><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Selesai</span></td>
                                </tr>
                                <tr>
                                    <td class="ps-4 fw-medium">Rekonsiliasi Triwulan II</td>
                                    <td>Rekons PDRB</td>
                                    <td class="small text-muted">5 jam yang lalu</td>
                                    <td class="pe-4 text-end"><span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">Proses</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php include __DIR__ . "/../partials/footer.php"; ?>
    </div>
</div>