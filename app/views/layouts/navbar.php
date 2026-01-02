<?php $base = rtrim(BASE_URL, '/'); ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="<?= $base ?>/">PDRB Panel</a>
    <div class="d-flex align-items-center ms-auto text-white small gap-3">
      <?php $user = $_SESSION['user'] ?? null; ?>
      <?php
        $displayName = 'Tamu';
        if (is_array($user)) {
            $candidate = $user['name'] ?? $user['nama'] ?? $user['email'] ?? 'Pengguna';
            if (is_array($candidate)) {
                $candidate = reset($candidate) ?: 'Pengguna';
            }
            $displayName = (string)$candidate;
        } elseif (is_string($user) && $user !== '') {
            $displayName = $user;
        }
      ?>
      <span class="fw-semibold d-flex align-items-center gap-2">
        <i class="bi bi-person-circle"></i>
        <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
      </span>
      <?php if (!empty($user)): ?>
        <a class="btn btn-sm btn-outline-light" href="<?= $base ?>/logout">Keluar</a>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-light" href="<?= $base ?>/login">Masuk</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
