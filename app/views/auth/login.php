<div class="card glass-card border-0 shadow-lg">
  <div class="card-body p-4 p-lg-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div class="brand-badge">
        <span class="pulse-dot"></span>
        <div>
          <div class="fw-semibold">Masuk &amp; Jelajahi</div>
          <small class="text-muted">Dashboard ekonomi favorit</small>
        </div>
      </div>
      <span class="soft-chip text-primary">Mode aman</span>
    </div>

    <div class="text-center mb-4">
      <h3 class="fw-bold mb-1">Login ke PDRB Panel</h3>
      <p class="text-muted mb-0">Tampilan abstrak interaktif untuk akses cepat dan populer.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= rtrim(BASE_URL, '/') ?>/login" class="needs-validation">
      <div class="mb-3 position-relative">
        <label for="username" class="form-label">Username</label>
        <div class="input-group input-glow">
          <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-person"></i></span>
          <input type="text" class="form-control form-control-lg border-start-0" id="username" name="username" placeholder="Masukkan username" required autofocus>
        </div>
        <div class="invalid-feedback">Username wajib diisi.</div>
      </div>

      <div class="mb-4 position-relative">
        <div class="d-flex justify-content-between align-items-center">
          <label for="password" class="form-label mb-0">Password</label>
          <span class="text-primary fw-semibold small">Terproteksi</span>
        </div>
        <div class="input-group input-glow">
          <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-shield-lock"></i></span>
          <input type="password" class="form-control form-control-lg border-start-0" id="password" name="password" placeholder="••••••••" required>
        </div>
        <div class="invalid-feedback">Password wajib diisi.</div>
      </div>

      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 text-muted">
          <span class="status-bullet bg-success"></span>
          <small>Server aktif &amp; terenkripsi</small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="soft-chip bg-white text-primary">Gestur cepat</span>
          <span class="soft-chip bg-white text-success">UI populer</span>
        </div>
      </div>

      <div class="d-grid">
        <button type="submit" class="btn btn-primary btn-lg shadow-sm w-100">Masuk sekarang</button>
      </div>

      <div class="mt-4">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <div class="info-tile">
              <span class="icon-badge bg-gradient-primary text-white"><i class="bi bi-activity"></i></span>
              <div>
                <div class="fw-semibold">Statistik langsung</div>
                <small class="text-muted">Pantau pergerakan ekonomi secara real-time.</small>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="info-tile">
              <span class="icon-badge bg-gradient-pink text-white"><i class="bi bi-stars"></i></span>
              <div>
                <div class="fw-semibold">Desain favorit</div>
                <small class="text-muted">Visual abstrak yang menarik dan interaktif.</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-4 text-center">
        <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2">Coba cepat: admin / admin123</span>
      </div>
    </form>
  </div>
</div>
