<?php
// partials/navbar.php
$user_name = $_SESSION['nama_lengkap'] ?? "User";
?>
<nav class="navbar navbar-expand-lg navbar-dark sirambo-topbar sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="/sirambo/pages/dashboard.php">
      <span class="sirambo-logo">S</span>
      <span>SIRAMBO</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <div class="ms-auto d-flex align-items-center gap-2">
        <span class="text-white-50 small d-none d-lg-inline">BPS Prov. Sultra</span>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($user_name) ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="/sirambo/pages/users.php"><i class="bi bi-people me-2"></i>Manajemen User</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="/sirambo/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>
