<?php $base = rtrim(BASE_URL, '/'); ?>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="icon-circle bg-info-subtle text-info"><i class="bi bi-geo-alt"></i></div>
          <span class="badge text-bg-info text-dark">Master</span>
        </div>
        <div class="fw-semibold">Master Wilayah</div>
        <div class="text-muted small">Kelola mst_wilayah (prov/kab/kota)</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="icon-circle bg-success-subtle text-success"><i class="bi bi-plus-circle"></i></div>
          <span class="badge text-bg-success">Modal</span>
        </div>
        <div class="fw-semibold">Tambah &amp; edit cepat</div>
        <div class="text-muted small">Gunakan tombol <strong>Tambah</strong> atau ikon edit di tabel.</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="icon-circle bg-warning-subtle text-warning"><i class="bi bi-info-circle"></i></div>
          <span class="badge text-bg-light text-dark">Status</span>
        </div>
        <div class="fw-semibold">Aktif/non-aktif</div>
        <div class="text-muted small">Centang opsi <em>Aktif</em> untuk mengatur ketersediaan wilayah.</div>
      </div>
    </div>
  </div>
</div>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <div>
    <h4 class="mb-0">Daftar Wilayah</h4>
    <div class="text-muted small">Tabel responsif dengan aksi inline</div>
  </div>
  <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalWilayah"
          onclick="openCreateWilayah()">
    <i class="bi bi-plus-circle"></i> Tambah
  </button>
</div>

<div class="card shadow-sm border-0">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Kode</th>
          <th>Nama</th>
          <th>Level</th>
          <th>Kode Induk</th>
          <th>Aktif</th>
          <th style="width:180px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="fw-semibold text-primary"><?= htmlspecialchars($r['kode_wilayah']) ?></td>
          <td><?= htmlspecialchars($r['nama_wilayah']) ?></td>
          <td><span class="badge text-bg-secondary"><?= htmlspecialchars($r['level_wilayah']) ?></span></td>
          <td><?= htmlspecialchars($r['kode_induk'] ?? '-') ?></td>
          <td><?= $r['is_active'] ? 'Ya' : 'Tidak' ?></td>
          <td class="d-flex flex-wrap gap-2">
            <button class="btn btn-sm btn-outline-primary"
              data-bs-toggle="modal" data-bs-target="#modalWilayah"
              onclick='openEditWilayah(<?= json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>
              <i class="bi bi-pencil"></i> Edit
            </button>

            <form method="POST" action="<?= $base ?>/wilayah/delete" class="d-inline"
                  onsubmit="return confirm('Hapus wilayah ini?')">
              <input type="hidden" name="kode_wilayah" value="<?= htmlspecialchars($r['kode_wilayah']) ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Create/Edit -->
<div class="modal fade" id="modalWilayah" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" method="POST" id="formWilayah">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Tambah Wilayah</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Kode Wilayah</label>
            <input type="text" class="form-control" name="kode_wilayah" id="kode_wilayah" maxlength="10" required>
            <div class="form-text">Contoh: 7400, 7401, 7471</div>
          </div>
          <div class="col-md-8">
            <label class="form-label">Nama Wilayah</label>
            <input type="text" class="form-control" name="nama_wilayah" id="nama_wilayah" maxlength="100" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Level</label>
            <select class="form-select" name="level_wilayah" id="level_wilayah" required>
              <option value="PROVINSI">PROVINSI</option>
              <option value="KABUPATEN">KABUPATEN</option>
              <option value="KOTA">KOTA</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Kode Induk (opsional)</label>
            <input type="text" class="form-control" name="kode_induk" id="kode_induk" maxlength="10">
            <div class="form-text">Untuk kab/kota isi prov induknya</div>
          </div>

          <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
              <label class="form-check-label" for="is_active">Aktif</label>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
function openCreateWilayah() {
  document.getElementById('modalTitle').innerText = 'Tambah Wilayah';
  const form = document.getElementById('formWilayah');
  form.action = "<?= $base ?>/wilayah/store";

  document.getElementById('kode_wilayah').readOnly = false;
  document.getElementById('kode_wilayah').value = '';
  document.getElementById('nama_wilayah').value = '';
  document.getElementById('kode_induk').value = '';
  document.getElementById('level_wilayah').value = 'PROVINSI';
  document.getElementById('is_active').checked = true;
}

function openEditWilayah(row) {
  document.getElementById('modalTitle').innerText = 'Edit Wilayah';
  const form = document.getElementById('formWilayah');
  form.action = "<?= $base ?>/wilayah/update";

  document.getElementById('kode_wilayah').value = row.kode_wilayah;
  document.getElementById('kode_wilayah').readOnly = true;
  document.getElementById('nama_wilayah').value = row.nama_wilayah ?? '';
  document.getElementById('kode_induk').value = row.kode_induk ?? '';
  document.getElementById('level_wilayah').value = row.level_wilayah ?? 'PROVINSI';
  document.getElementById('is_active').checked = (parseInt(row.is_active) === 1);
}
</script>
