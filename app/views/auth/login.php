<?php $base = rtrim(BASE_URL, '/'); ?>
<div class="card glass-card border-0 shadow-lg login-card">
  <div class="card-body p-4 p-lg-5">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
      <div class="brand-badge">
        <span class="pulse-dot"></span>
        <div>
          <div class="fw-semibold">Masuk &amp; Jelajahi</div>
          <small class="text-muted">Koneksi langsung ke tabel <code>users</code> BPS Sultra</small>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="soft-chip text-primary">Terverifikasi</span>
        <span class="soft-chip text-success">Terproteksi</span>
      </div>
    </div>

    <div class="login-brand text-center mb-4">
      <div class="brand-bubble brand-text-badge mx-auto mb-3 brand-bubble-blue">
        <span class="brand-initials">BPS</span>
      </div>
      <div class="brand-long-text mx-auto mb-2">
        <div class="brand-long-top text-primary">Gerbang Biru PDRB Sultra</div>
        <small class="text-primary fw-semibold">BPS Provinsi Sulawesi Tenggara</small>
      </div>
      <p class="text-muted mb-0">Masuk untuk melanjutkan ke panel statistik ekonomi Sulawesi Tenggara yang modern dan atraktif.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= rtrim(BASE_URL, '/') ?>/login" class="needs-validation" novalidate>
      <div class="mb-3 position-relative">
        <div class="d-flex justify-content-between align-items-center">
          <label for="username" class="form-label mb-0">Username atau Email</label>
          <span class="text-primary small fw-semibold">Terhubung DB</span>
        </div>
        <div class="input-group input-glow">
          <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-person-badge"></i></span>
          <input type="text" class="form-control form-control-lg border-start-0" id="username" name="username" placeholder="contoh: bps_admin atau admin@bps.go.id" required autofocus>
        </div>
        <div class="invalid-feedback">Username atau email wajib diisi.</div>
      </div>

      <div class="mb-4 position-relative">
        <div class="d-flex justify-content-between align-items-center">
          <label for="password" class="form-label mb-0">Password</label>
          <span class="text-success fw-semibold small">BCrypt friendly</span>
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
          <small>Validasi akun aktif &amp; last login tercatat</small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="soft-chip bg-white text-primary">Single sign-on wilayah</span>
          <span class="soft-chip bg-white text-success">UI favorit</span>
        </div>
      </div>

      <div class="d-grid mb-3">
        <button type="submit" class="btn btn-primary btn-lg shadow-sm w-100">Masuk sekarang</button>
      </div>

      <div class="row g-3 info-grid">
        <div class="col-12 col-md-4">
          <div class="info-tile morph-card morph-card-blue">
            <span class="icon-badge bg-gradient-primary text-white"><i class="bi bi-cloud-check"></i></span>
            <div>
              <div class="fw-semibold">Autentikasi DB</div>
              <small class="text-muted">Terhubung langsung ke tabel <code>users</code> Sultra.</small>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="info-tile morph-card morph-card-cyan">
            <span class="icon-badge bg-gradient-cyan text-white"><i class="bi bi-stars"></i></span>
            <div>
              <div class="fw-semibold">Tema Biru</div>
              <small class="text-muted">Aksen laut Kendari yang modern dan atraktif.</small>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="info-tile morph-card morph-card-navy highlight-tile">
            <span class="icon-badge bg-gradient-emerald text-white"><i class="bi bi-shield-lock"></i></span>
            <div class="flex-grow-1">
              <div class="fw-semibold">Aman &amp; Tercatat</div>
              <small class="text-muted">Regenerasi sesi, hash password, dan last login.</small>
            </div>
            <span class="badge rounded-pill text-bg-success">Aktif</span>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
