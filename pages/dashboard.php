<?php
require_once __DIR__ . "/../partials/auth_guard.php";
$title = "Dashboard";
$page  = "dashboard";

include __DIR__ . "/../partials/header.php";
include __DIR__ . "/../partials/navbar.php";
?>
<div class="sirambo-layout">
  <?php include __DIR__ . "/../partials/sidebar.php"; ?>

  <main class="sirambo-content">
    <div class="container-fluid p-0">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
          <h4 class="mb-0 fw-bold">Dashboard</h4>
          <div class="text-muted small">Sistem Rilis dan Rekonsiliasi PDRB Online</div>
        </div>
        <span class="badge text-bg-primary"><i class="bi bi-shield-check me-1"></i>Internal</span>
      </div>

      <div class="row g-3">
        <div class="col-md-4">
          <div class="card sirambo-card">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="text-muted small">Rilis Bulan Ini</div>
                  <div class="fs-4 fw-bold">—</div>
                </div>
                <i class="bi bi-megaphone fs-3 text-primary"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card sirambo-card">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="text-muted small">Rekonsiliasi Berjalan</div>
                  <div class="fs-4 fw-bold">—</div>
                </div>
                <i class="bi bi-arrow-repeat fs-3 text-primary"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card sirambo-card">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="text-muted small">User Aktif</div>
                  <div class="fs-4 fw-bold">—</div>
                </div>
                <i class="bi bi-people fs-3 text-primary"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card sirambo-card mt-3">
        <div class="card-body">
          <div class="fw-semibold mb-2">Quick Actions</div>
          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary btn-sm" href="/sirambo/pages/pdrb.php"><i class="bi bi-bar-chart-line me-1"></i>Input/Rekons PDRB</a>
            <a class="btn btn-outline-secondary btn-sm" href="/sirambo/pages/users.php"><i class="bi bi-people me-1"></i>Kelola User</a>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . "/../partials/footer.php"; ?>
