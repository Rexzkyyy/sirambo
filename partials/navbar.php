<?php
// partials/navbar.php
$user_name  = $_SESSION['nama_lengkap'] ?? "User";
$user_email = $_SESSION['email'] ?? "bpsprov@example.go.id";
?>
<header class="sirambo-header shadow-sm">
  <nav class="navbar navbar-expand-lg navbar-dark sirambo-topbar">
    <div class="container-fluid gap-2">
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-light btn-icon d-lg-none" id="sidebarToggle" aria-label="Buka menu">
          <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="/sirambo/pages/dashboard.php">
          <span class="sirambo-logo">S</span>
          <div class="d-flex flex-column lh-1">
            <span class="fw-bold">SIRAMBO</span>
            <small class="text-white-50">Rilis & Rekonsiliasi PDRB</small>
          </div>
        </a>
      </div>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="topNav">
        <div class="ms-auto d-flex align-items-center flex-wrap gap-2">
          <div class="sirambo-badge text-white d-none d-lg-flex align-items-center gap-2">
            <i class="bi bi-geo-alt"></i>
            <div class="lh-1">
              <div class="fw-semibold">BPS Provinsi Sulawesi Tenggara</div>
              <small class="text-white-75">Data rilis internal</small>
            </div>
          </div>

          <div class="sirambo-quickaction d-none d-lg-inline-flex align-items-center gap-1">
            <i class="bi bi-lightning-charge-fill text-warning"></i>
            <span>Shortcut</span>
          </div>

          <div class="dropdown">
            <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle text-primary"></i>
              <span class="text-start">
                <span class="d-block fw-semibold text-dark"><?= htmlspecialchars($user_name) ?></span>
                <small class="text-muted"><?= htmlspecialchars($user_email) ?></small>
              </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
              <li class="dropdown-header small text-muted">Akun</li>
              <li><a class="dropdown-item" href="/sirambo/pages/users.php"><i class="bi bi-people me-2"></i>Manajemen User</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="/sirambo/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>
</header>
