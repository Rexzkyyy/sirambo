<div class="card glass-card border-0 shadow-lg">
  <div class="card-body p-4 p-lg-5">
    <div class="text-center mb-4">
      <div class="brand-badge mb-3">
        <span class="pulse-dot"></span>
        <span class="fw-semibold">Selamat datang</span>
      </div>
      <h3 class="fw-bold mb-1">Masuk ke PDRB Panel</h3>
      <p class="text-muted mb-0">Login cepat dengan tampilan abstrak yang atraktif</p>
    </div>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>
    <form method="POST" action="<?= rtrim(BASE_URL, '/') ?>/login" class="needs-validation">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control form-control-lg" id="username" name="username" placeholder="Masukkan username" required autofocus>
        <div class="invalid-feedback">Username wajib diisi.</div>
      </div>
      <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="••••••••" required>
        <div class="invalid-feedback">Password wajib diisi.</div>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary btn-lg shadow-sm">Masuk sekarang</button>
      </div>
      <div class="mt-4 text-center">
        <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2">Coba cepat: admin / admin123</span>
      </div>
    </form>
  </div>
</div>
