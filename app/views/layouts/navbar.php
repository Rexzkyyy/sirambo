<?php $base = rtrim(BASE_URL, '/'); ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="<?= $base ?>/">PDRB Panel</a>
    <div class="d-flex align-items-center ms-auto text-white small gap-3">
      <?php $user = $_SESSION['user'] ?? null; ?>
      <span class="fw-semibold">
        <?= htmlspecialchars(is_array($user) ? ($user['name'] ?? $user['email'] ?? 'Pengguna') : ($user ?: 'Tamu')) ?>
      </span>
      <?php if (!empty($user)): ?>
        <a class="btn btn-sm btn-outline-light" href="<?= $base ?>/logout">Keluar</a>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-light" href="<?= $base ?>/login">Masuk</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
