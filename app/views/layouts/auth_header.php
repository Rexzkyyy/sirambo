<?php $base = rtrim(BASE_URL, '/'); ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - PDRB App</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= $base ?>/assets/css/app.css" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="noise-layer"></div>
  <div class="auth-wrapper position-relative d-flex align-items-center min-vh-100 py-5">
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>
    <div class="grid-lines"></div>
    <div class="container position-relative">
      <div class="row align-items-center justify-content-center g-4">
        <div class="col-12 col-lg-6">
          <div class="hero-panel text-white p-4 p-lg-5 shadow-lg position-relative overflow-hidden auth-hero" style="background-image: linear-gradient(145deg, rgba(3,25,66,0.96), rgba(13,64,141,0.88)), radial-gradient(circle at 18% 28%, rgba(14,165,233,0.25), transparent 32%), radial-gradient(circle at 82% 42%, rgba(56,189,248,0.18), transparent 30%);">
            <div class="gradient-ring"></div>
            <div class="brand-strip mb-3">
              <div class="brand-mark brand-text-mark">
                <span class="brand-initials brand-initials-sm">BPS</span>
              </div>
              <div class="brand-title">
                <div class="fw-bold">BPS Provinsi Sulawesi Tenggara</div>
                <small class="text-white-50">Gerbang PDRB Satu Pintu</small>
              </div>
              <div class="brand-long d-none d-md-block">
                <span class="brand-long-chip">BPS Sultra • Statistik untuk Negeri</span>
              </div>
            </div>
            <div class="badge text-bg-info rounded-pill mb-3 px-3 py-2 text-dark fw-semibold">Tema biru modern Sultra</div>
            <h1 class="display-6 fw-bold mb-3">Panel Ekonomi Daerah</h1>
            <p class="fs-6 text-white-50 mb-4">Lapisan abstrak bernuansa biru laut Kendari yang modern, menjaga kesan profesional khas BPS.</p>
            <div class="row g-3 text-center text-lg-start mb-3">
              <div class="col-6 col-lg-4">
                <div class="stat-badge">
                  <div class="fw-bold fs-4">98%</div>
                  <small class="text-white-50">Uptime</small>
                </div>
              </div>
              <div class="col-6 col-lg-4">
                <div class="stat-badge">
                  <div class="fw-bold fs-4">+24</div>
                  <small class="text-white-50">Wilayah aktif</small>
                </div>
              </div>
              <div class="col-12 col-lg-4">
                <div class="stat-badge">
                  <div class="fw-bold fs-4">Gelar</div>
                  <small class="text-white-50">UI favorit</small>
                </div>
              </div>
            </div>
            <div class="feature-board">
              <div class="feature-pill">Satu akun untuk seluruh Sultra</div>
              <div class="feature-pill">Integrasi tabel users</div>
              <div class="feature-pill">Lapisan glow abstrak biru</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-5">
