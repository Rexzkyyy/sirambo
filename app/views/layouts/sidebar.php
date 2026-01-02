<?php $base = rtrim(BASE_URL, '/'); ?>
<div class="col-12 col-md-3 col-lg-2 p-0 border-end bg-white min-vh-100">
  <div class="p-3">
    <div class="fw-bold text-muted small mb-2">MENU</div>
    <div class="list-group list-group-flush">
      <a class="list-group-item list-group-item-action" href="<?= $base ?>/">Dashboard</a>

      <div class="fw-bold text-muted small mt-3 mb-2">MASTER</div>
      <a class="list-group-item list-group-item-action" href="<?= $base ?>/wilayah">Wilayah</a>

      <div class="fw-bold text-muted small mt-3 mb-2">PDRB</div>
      <a class="list-group-item list-group-item-action disabled" href="#">Transaksi (next)</a>
      <a class="list-group-item list-group-item-action disabled" href="#">Fenomena (next)</a>
    </div>
  </div>
</div>

<div class="col-12 col-md-9 col-lg-10 p-3">
