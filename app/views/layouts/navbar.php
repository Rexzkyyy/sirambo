<?php $base = rtrim(BASE_URL, '/'); ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="<?= $base ?>/">PDRB Panel</a>
    <div class="d-flex align-items-center ms-auto text-white small gap-3">
      <span class="fw-semibold"><?= htmlspecialchars($_SESSION['user'] ?? 'Tamu') ?></span>
      <?php if (!empty($_SESSION['user'])): ?>
        <a class="btn btn-sm btn-outline-light" href="<?= $base ?>/logout">Keluar</a>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-light" href="<?= $base ?>/login">Masuk</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
