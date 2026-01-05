<?php
// partials/sidebar.php
function active($key, $page) { return $key === $page ? "active" : ""; }

$user_name  = $_SESSION['nama_lengkap'] ?? "User";
$user_unit  = $_SESSION['unit'] ?? "Tim Statistik PDRB";
?>
<aside class="sirambo-sidebar" id="siramboSidebar">
  <div class="sidebar-inner">
    <div class="d-flex align-items-start justify-content-between mb-3">
      <div class="d-flex align-items-center gap-2">
        <div class="avatar-soft">
          <i class="bi bi-person-fill"></i>
        </div>
        <div class="lh-sm">
          <div class="fw-semibold"><?= htmlspecialchars($user_name) ?></div>
          <small class="text-muted"><?= htmlspecialchars($user_unit) ?></small>
        </div>
      </div>
      <button class="btn btn-sm btn-light border d-lg-none" id="sidebarClose" aria-label="Tutup menu">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div class="sidebar-block mb-4">
      <div class="sidebar-title">Menu</div>

      <a class="sirambo-nav <?= active('dashboard', $page) ?>" href="/sirambo/pages/dashboard.php">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>

      <a class="sirambo-nav <?= active('pdrb', $page) ?>" href="/sirambo/pages/pdrb.php">
        <i class="bi bi-bar-chart-line"></i> Rilis & Rekonsiliasi PDRB
      </a>

      <a class="sirambo-nav <?= active('users', $page) ?>" href="/sirambo/pages/users.php">
        <i class="bi bi-people"></i> User
      </a>
    </div>

    <div class="sidebar-block">
      <div class="sidebar-title">Shortcut</div>
      <div class="d-grid gap-2">
        <a class="btn btn-outline-primary btn-sm" href="/sirambo/pages/pdrb.php"><i class="bi bi-plus-circle me-1"></i>Catat rilis</a>
        <a class="btn btn-outline-secondary btn-sm" href="/sirambo/pages/users.php"><i class="bi bi-people-fill me-1"></i>Kelola user</a>
        <button class="btn btn-light btn-sm" onclick="window.scrollTo({top:0,behavior:'smooth'})">
          <i class="bi bi-arrow-up me-1"></i> Ke atas
        </button>
      </div>
    </div>
  </div>
</aside>
