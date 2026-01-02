<div class="card shadow-sm">
  <div class="card-body p-4">
    <h5 class="fw-semibold mb-3 text-center">Masuk ke PDRB Panel</h5>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>
    <form method="POST" action="<?= rtrim(BASE_URL, '/') ?>/login">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required autofocus>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary">Masuk</button>
      </div>
      <p class="text-muted small mt-3 mb-0 text-center">Gunakan kredensial admin bawaan untuk mencoba: admin / admin123</p>
    </form>
  </div>
</div>
