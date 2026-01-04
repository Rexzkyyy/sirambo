<?php
$base = rtrim(BASE_URL, '/');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';

function isActive(string $path, string $current): string {
  return rtrim($current, '/') === rtrim($path, '/') ? 'active' : '';
}
?>
<div class="app-main flex-grow-1">
  <div class="container-fluid py-3">
    <div class="row g-0 flex-nowrap">
      <aside class="app-sidebar col-12 col-lg-3 col-xl-2 bg-white border-end" id="sidebar">
        <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
          <div>
            <div class="fw-semibold">Navigasi</div>
            <small class="text-muted">Pilih modul</small>
          </div>
          <span class="badge text-bg-primary">Bootstrap 5</span>
        </div>

        <div class="list-group list-group-flush">
          <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?= isActive('/', $currentPath) ?>" href="<?= $base ?>/">
            <i class="bi bi-speedometer2 text-primary"></i>
            <span>Dashboard</span>
          </a>

          <div class="list-group-title px-3 pt-3 text-uppercase small text-muted">Master</div>
          <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?= isActive('/wilayah', $currentPath) ?>" href="<?= $base ?>/wilayah">
            <i class="bi bi-geo-alt text-info"></i>
            <span>Wilayah</span>
          </a>

          <div class="list-group-title px-3 pt-3 text-uppercase small text-muted">PDRB</div>
          <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 disabled" href="#">
            <i class="bi bi-list-check"></i>
            <span>Transaksi (next)</span>
          </a>
          <a class="list-group-item list-group-item-action d-flex align-items-center gap-2 disabled" href="#">
            <i class="bi bi-lightbulb"></i>
            <span>Fenomena (next)</span>
          </a>
        </div>
      </aside>

      <main class="col px-3 px-lg-4 py-3">
        <div class="page-heading d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
          <div>
            <div class="breadcrumb small text-muted mb-1">PDRB / Panel</div>
            <h5 class="mb-0">Selamat datang</h5>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-success">Online</span>
            <span class="badge text-bg-light text-dark">Bootstrap 5 UI</span>
          </div>
        </div>
