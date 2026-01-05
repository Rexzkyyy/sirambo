<?php
// partials/sidebar.php
function active($key, $page) { return $key === $page ? "active" : ""; }
?>
<aside class="sirambo-sidebar">
  <div class="p-3">
    <div class="small text-muted mb-2">MENU</div>

    <a class="sirambo-nav <?= active('dashboard', $page) ?>" href="/sirambo/pages/dashboard.php">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <a class="sirambo-nav <?= active('pdrb', $page) ?>" href="/sirambo/pages/pdrb.php">
      <i class="bi bi-bar-chart-line"></i> Rilis & Rekonsiliasi PDRB
    </a>

    <a class="sirambo-nav <?= active('users', $page) ?>" href="/sirambo/pages/users.php">
      <i class="bi bi-people"></i> User
    </a>

    <div class="mt-4 small text-muted">SHORTCUT</div>
    <button class="btn btn-sm btn-outline-secondary w-100 mt-2" onclick="window.scrollTo({top:0,behavior:'smooth'})">
      <i class="bi bi-arrow-up"></i> Ke atas
    </button>
  </div>
</aside>
