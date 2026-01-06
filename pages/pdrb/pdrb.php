<?php
/**
 * Halaman PDRB (Produk Domestik Regional Bruto)
 * Tema Navy Blue
 * Data contoh - akan dihubungkan ke database nanti
 */
require_once __DIR__ . "/../../partials/auth_guard.php";
require_once __DIR__ . '/../../config/config.php';

$title = "Produk Domestik Regional Bruto (PDRB)";
$page = "pdrb";

// Data kosong untuk PDRB
$pdrb_data = [];

// Parameter pencarian dan filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';
$filter_triwulan = isset($_GET['triwulan']) ? $_GET['triwulan'] : '';
$filter_wilayah = isset($_GET['wilayah']) ? $_GET['wilayah'] : '';
$page_num = isset($_GET['page']) ? intval($_GET['page']) : 1;

// Ambil data wilayah untuk dropdown filter
$wilayah_options = [];
$sql_wilayah = "SELECT kode_wilayah, nama_wilayah, level_wilayah 
                FROM mst_wilayah 
                WHERE is_active = 1 
                ORDER BY level_wilayah, nama_wilayah";
$result_wilayah = $conn->query($sql_wilayah);
if ($result_wilayah) {
    while ($row = $result_wilayah->fetch_assoc()) {
        $wilayah_options[] = $row;
    }
}

// Tahun options
$tahun_options = range(2020, 2025);
$triwulan_options = ['I', 'II', 'III', 'IV'];

include __DIR__ . "/../../partials/header.php";
?>
<div class="sirambo-wrapper">
    <!-- Sidebar -->
    <?php include __DIR__ . "/../../partials/sidebar.php"; ?>

    <!-- Bagian Utama -->
    <div class="sirambo-main">
        <!-- Navbar -->
        <?php include __DIR__ . "/../../partials/navbar.php"; ?>

        <!-- Konten Utama -->
        <main class="sirambo-content-area">
            <!-- Header Halaman & Statistik Singkat -->
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item small"><a href="../dashboard.php" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item small active" aria-current="page">PDRB</li>
                        </ol>
                    </nav>
                    <h4 class="fw-bold text-navy-primary mb-0">Produk Domestik Regional Bruto</h4>
                    <p class="text-muted small mb-0">Data ekonomi regional per triwulan</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <!-- Dropdown Ekspor -->
                    <div class="dropdown">
                        <button class="btn btn-outline-navy btn-sm border-secondary-subtle bg-white shadow-sm rounded-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" disabled>
                            <i class="bi bi-download me-1"></i> Ekspor
                        </button>
                        <ul class="dropdown-menu shadow border-0">
                            <li><a class="dropdown-item py-2" href="#"><i class="bi bi-file-earmark-excel me-2 text-success"></i> Ekspor ke Excel</a></li>
                            <li><a class="dropdown-item py-2" href="#"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i> Ekspor ke PDF</a></li>
                        </ul>
                    </div>
                    
                    <button class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm border-0" data-bs-toggle="modal" data-bs-target="#modalImportPDRB">
                        <i class="bi bi-upload me-1"></i> Import Data
                    </button>
                    <button class="btn btn-success btn-sm px-3 rounded-3 shadow-sm border-0" data-bs-toggle="modal" data-bs-target="#modalAddPDRB">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Data
                    </button>
                </div>
            </div>

            <!-- Kartu Ringkasan -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card card-sirambo border-0 bg-white p-3 shadow-sm">
                        <small class="text-muted d-block mb-1">Total Data PDRB</small>
                        <h4 class="fw-bold mb-0">0</h4>
                        <small class="text-muted">Belum ada data</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-sirambo border-0 bg-white p-3 shadow-sm border-start border-primary border-4">
                        <small class="text-muted d-block mb-1">Pertumbuhan Rata-rata</small>
                        <h4 class="fw-bold mb-0 text-muted">-</h4>
                        <small class="text-muted">Data belum tersedia</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-sirambo border-0 bg-white p-3 shadow-sm border-start border-warning border-4">
                        <small class="text-muted d-block mb-1">Wilayah Terdaftar</small>
                        <h4 class="fw-bold mb-0 text-warning"><?= count($wilayah_options) ?></h4>
                        <small class="text-muted">Dari database</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-sirambo border-0 bg-white p-3 shadow-sm border-start border-info border-4">
                        <small class="text-muted d-block mb-1">Lapangan Usaha</small>
                        <h4 class="fw-bold mb-0 text-info">0</h4>
                        <small class="text-muted">Belum ada data</small>
                    </div>
                </div>
            </div>

            <!-- Tabel PDRB -->
            <div class="card card-sirambo border-0 bg-white overflow-hidden shadow-sm">
                <div class="card-header bg-white border-0 p-4 pb-0">
                    <form method="GET" action="" class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" 
                                       name="search" 
                                       class="form-control bg-light border-start-0" 
                                       placeholder="Cari lapangan usaha..." 
                                       value="<?= htmlspecialchars($search) ?>"
                                       disabled>
                            </div>
                        </div>
                        <div class="col-md-8 text-md-end">
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border dropdown-toggle" data-bs-toggle="dropdown" disabled>
                                        Tahun: <?= !empty($filter_tahun) ? $filter_tahun : 'Semua' ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&triwulan=<?= urlencode($filter_triwulan) ?>&wilayah=<?= urlencode($filter_wilayah) ?>">Semua Tahun</a></li>
                                        <?php foreach($tahun_options as $tahun): ?>
                                        <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&tahun=<?= $tahun ?>&triwulan=<?= urlencode($filter_triwulan) ?>&wilayah=<?= urlencode($filter_wilayah) ?>"><?= $tahun ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border dropdown-toggle" data-bs-toggle="dropdown" disabled>
                                        Triwulan: <?= !empty($filter_triwulan) ? $filter_triwulan : 'Semua' ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&tahun=<?= urlencode($filter_tahun) ?>&wilayah=<?= urlencode($filter_wilayah) ?>">Semua Triwulan</a></li>
                                        <?php foreach($triwulan_options as $triwulan): ?>
                                        <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&tahun=<?= urlencode($filter_tahun) ?>&triwulan=<?= $triwulan ?>&wilayah=<?= urlencode($filter_wilayah) ?>">Triwulan <?= $triwulan ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border dropdown-toggle" data-bs-toggle="dropdown" disabled>
                                        Wilayah: <?= !empty($filter_wilayah) ? $filter_wilayah : 'Semua' ?>
                                    </button>
                                    <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                                        <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&tahun=<?= urlencode($filter_tahun) ?>&triwulan=<?= urlencode($filter_triwulan) ?>">Semua Wilayah</a></li>
                                        <?php foreach($wilayah_options as $wilayah): ?>
                                        <li>
                                            <a class="dropdown-item" href="?search=<?= urlencode($search) ?>&tahun=<?= urlencode($filter_tahun) ?>&triwulan=<?= urlencode($filter_triwulan) ?>&wilayah=<?= urlencode($wilayah['kode_wilayah']) ?>">
                                                <?= htmlspecialchars($wilayah['kode_wilayah']) ?> - <?= htmlspecialchars($wilayah['nama_wilayah']) ?>
                                            </a>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-sm" disabled>Terapkan</button>
                                <?php if (!empty($search) || !empty($filter_tahun) || !empty($filter_triwulan) || !empty($filter_wilayah)): ?>
                                    <a href="pdrb.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0 mt-3">
                    <?php if (empty($pdrb_data)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-graph-up display-1 text-muted"></i>
                            <p class="text-muted mt-3">Belum ada data PDRB</p>
                            <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#modalAddPDRB">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Data Pertama
                            </button>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small">
                                <tr>
                                    <th class="ps-4 py-3 text-uppercase fw-bold" style="width: 50px;">No</th>
                                    <th class="py-3 text-uppercase fw-bold">Tahun & Triwulan</th>
                                    <th class="py-3 text-uppercase fw-bold">Wilayah</th>
                                    <th class="py-3 text-uppercase fw-bold">Lapangan Usaha</th>
                                    <th class="py-3 text-uppercase fw-bold text-end">Harga Berlaku (Miliar)</th>
                                    <th class="py-3 text-uppercase fw-bold text-end">Harga Konstan (Miliar)</th>
                                    <th class="py-3 text-uppercase fw-bold text-end">Pertumbuhan (%)</th>
                                    <th class="py-3 text-uppercase fw-bold text-end">Kontribusi (%)</th>
                                    <th class="pe-4 py-3 text-uppercase fw-bold text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Tidak ada data untuk ditampilkan -->
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Pesan Data Kosong -->
                    <div class="alert alert-warning alert-dismissible fade show m-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Database PDRB Masih Kosong</h6>
                                <p class="mb-0">Silakan tambah data PDRB pertama Anda menggunakan tombol "Tambah Data" di atas.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="p-4 d-flex flex-column flex-md-row align-items-center justify-content-between border-top">
                        <small class="text-muted mb-2 mb-md-0">
                            Menampilkan 0 data PDRB
                        </small>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item disabled">
                                    <a class="page-link rounded-start-3" href="#">
                                        Prev
                                    </a>
                                </li>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#">1</a>
                                </li>
                                <li class="page-item disabled">
                                    <a class="page-link rounded-end-3" href="#">
                                        Next
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Chart Placeholder -->
            <div class="card card-sirambo border-0 bg-white overflow-hidden shadow-sm mt-4">
                <div class="card-header bg-white border-0 p-4 pb-3">
                    <h6 class="fw-bold text-navy-primary mb-0">Grafik Pertumbuhan PDRB</h6>
                    <small class="text-muted">Visualisasi data pertumbuhan ekonomi</small>
                </div>
                <div class="card-body p-4">
                    <div class="text-center py-5">
                        <i class="bi bi-bar-chart display-1 text-muted"></i>
                        <p class="text-muted mt-3">Tidak ada data untuk ditampilkan</p>
                        <p class="text-muted small">Data grafik akan tersedia setelah data PDRB diinput</p>
                        <button class="btn btn-outline-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#modalAddPDRB">
                            <i class="bi bi-plus-circle me-1"></i> Mulai Input Data
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php include __DIR__ . "/../../partials/footer.php"; ?>
    </div>
</div>

<!-- Modal Tambah Data PDRB -->
<div class="modal fade" id="modalAddPDRB" tabindex="-1" aria-labelledby="modalAddPDRBLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy-primary" id="modalAddPDRBLabel">Tambah Data PDRB</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="#" method="POST">
                <div class="modal-body pt-1">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Perhatian:</strong> Fitur ini masih dalam pengembangan. Data yang dimasukkan adalah contoh sementara.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_tahun" class="form-label small fw-medium">Tahun <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="add_tahun" name="tahun" required>
                                <option value="">Pilih Tahun</option>
                                <?php foreach($tahun_options as $tahun): ?>
                                <option value="<?= $tahun ?>"><?= $tahun ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_triwulan" class="form-label small fw-medium">Triwulan <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="add_triwulan" name="triwulan" required>
                                <option value="">Pilih Triwulan</option>
                                <?php foreach($triwulan_options as $triwulan): ?>
                                <option value="<?= $triwulan ?>">Triwulan <?= $triwulan ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_wilayah" class="form-label small fw-medium">Wilayah <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="add_wilayah" name="wilayah" required>
                            <option value="">Pilih Wilayah</option>
                            <?php foreach($wilayah_options as $wilayah): ?>
                            <option value="<?= htmlspecialchars($wilayah['kode_wilayah']) ?>">
                                <?= htmlspecialchars($wilayah['kode_wilayah']) ?> - <?= htmlspecialchars($wilayah['nama_wilayah']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_lapangan_usaha" class="form-label small fw-medium">Lapangan Usaha <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="add_lapangan_usaha" name="lapangan_usaha" required>
                            <option value="">Pilih Lapangan Usaha</option>
                            <option value="Pertanian, Kehutanan, dan Perikanan">Pertanian, Kehutanan, dan Perikanan</option>
                            <option value="Pertambangan dan Penggalian">Pertambangan dan Penggalian</option>
                            <option value="Industri Pengolahan">Industri Pengolahan</option>
                            <option value="Pengadaan Listrik dan Gas">Pengadaan Listrik dan Gas</option>
                            <option value="Konstruksi">Konstruksi</option>
                            <option value="Perdagangan Besar dan Eceran">Perdagangan Besar dan Eceran</option>
                            <option value="Transportasi dan Pergudangan">Transportasi dan Pergudangan</option>
                            <option value="Penyediaan Akomodasi dan Makan Minum">Penyediaan Akomodasi dan Makan Minum</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_harga_berlaku" class="form-label small fw-medium">Harga Berlaku (Miliar) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control form-control-sm" id="add_harga_berlaku" name="harga_berlaku" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_harga_konstan" class="form-label small fw-medium">Harga Konstan (Miliar) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control form-control-sm" id="add_harga_konstan" name="harga_konstan" placeholder="0.00" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_pertumbuhan" class="form-label small fw-medium">Pertumbuhan (%)</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" id="add_pertumbuhan" name="pertumbuhan" placeholder="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_kontribusi" class="form-label small fw-medium">Kontribusi (%)</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" id="add_kontribusi" name="kontribusi" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="alert('Fitur dalam pengembangan. Data PDRB akan tersedia segera.')">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import PDRB -->
<div class="modal fade" id="modalImportPDRB" tabindex="-1" aria-labelledby="modalImportPDRBLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy-primary" id="modalImportPDRBLabel">Import Data PDRB</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-1">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Informasi:</strong> Fitur import data PDRB dari file Excel akan tersedia segera.
                </div>
                
                <div class="text-center py-4">
                    <i class="bi bi-file-earmark-excel display-1 text-success mb-3"></i>
                    <p class="text-muted">Import data PDRB dari file Excel</p>
                    <p class="small text-muted">Format file: .xlsx, .xls, .csv</p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-medium">Upload File</label>
                    <div class="input-group input-group-sm">
                        <input type="file" class="form-control" id="inputGroupFile02" disabled>
                        <button class="btn btn-outline-secondary" type="button" disabled>
                            <i class="bi bi-upload"></i>
                        </button>
                    </div>
                    <small class="text-muted">Fitur upload akan tersedia segera</small>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="flexCheckDefault" disabled>
                    <label class="form-check-label small" for="flexCheckDefault">
                        Update data yang sudah ada
                    </label>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm" disabled>Import Data</button>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-outline-navy {
        color: var(--navy-primary);
        border-color: #e2e8f0;
    }
    .btn-outline-navy:hover {
        background-color: #f1f5f9;
        color: var(--navy-primary);
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.02);
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        content: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='currentColor'/%3E%3C/svg%3E");
    }
    
    /* Chart placeholder styling */
    .chart-placeholder {
        height: 300px;
        background: linear-gradient(45deg, #f8fafc 25%, #ffffff 25%, #ffffff 50%, #f8fafc 50%, #f8fafc 75%, #ffffff 75%, #ffffff);
        background-size: 20px 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sirambo-content-area {
            padding: 1rem !important;
        }
        .card-header .row {
            flex-direction: column;
            gap: 1rem;
        }
        .card-header .col-md-4,
        .card-header .col-md-8 {
            width: 100%;
        }
        .card-header .text-md-end {
            text-align: left !important;
        }
        .card-header .d-flex {
            flex-direction: column;
            gap: 0.5rem;
        }
        .card-header .btn-group {
            width: 100%;
        }
        .card-header .btn-group .dropdown-toggle {
            width: 100%;
            justify-content: space-between;
        }
        .table-responsive {
            font-size: 0.875rem;
        }
        .dropdown-menu {
            font-size: 0.875rem;
        }
    }
</style>

<script>
// Auto-hide alert setelah 5 detik
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// Demo untuk tombol tambah data
document.querySelectorAll('.btn-primary').forEach(button => {
    if (button.textContent.includes('Simpan')) {
        button.addEventListener('click', function(e) {
            if (!this.hasAttribute('data-bs-dismiss')) {
                e.preventDefault();
                alert('Fitur PDRB sedang dalam pengembangan. Data aktual akan tersedia segera!');
            }
        });
    }
});

// Demo untuk import data
document.getElementById('modalImportPDRB')?.addEventListener('show.bs.modal', function() {
    setTimeout(() => {
        alert('Fitur import data PDRB akan tersedia segera setelah sistem terhubung dengan database.');
    }, 500);
});

// Nonaktifkan tombol filter jika tidak ada data
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    const filterButtons = document.querySelectorAll('.btn-group .dropdown-toggle');
    const applyButton = document.querySelector('button[type="submit"]');
    
    if (<?= empty($pdrb_data) ? 'true' : 'false' ?>) {
        if (searchInput) searchInput.disabled = true;
        filterButtons.forEach(btn => btn.disabled = true);
        if (applyButton) applyButton.disabled = true;
    }
});
</script>