<?php
$base = rtrim(BASE_URL, '/');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - PDRB App</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= $base ?>/assets/css/app.css" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="auth-wrapper position-relative d-flex align-items-center min-vh-100 py-5">
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>
    <div class="container position-relative">
      <div class="row align-items-center justify-content-center g-4">
        <div class="col-12 col-lg-6">
          <div class="hero-panel text-white p-4 p-lg-5 shadow-lg">
            <div class="d-inline-flex align-items-center gap-2 mb-3 px-3 py-2 rounded-pill bg-white bg-opacity-10 border border-light border-opacity-25">
              <span class="badge text-bg-warning rounded-pill">Populer</span>
              <span class="small fw-semibold">Pantau ekonomi daerah secara real-time</span>
            </div>
            <h1 class="display-6 fw-bold mb-3">PDRB Panel</h1>
            <p class="fs-5 text-white-50 mb-4">Dashboard interaktif untuk melihat kinerja ekonomi wilayah dengan visual abstrak yang modern.</p>
            <div class="d-flex flex-wrap gap-2">
              <span class="feature-pill">Dashboard dinamis</span>
              <span class="feature-pill">Data terkini</span>
              <span class="feature-pill">Visual apik</span>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-5">
