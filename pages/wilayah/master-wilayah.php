<?php
/**
 * Master Wilayah Page - Tema Navy Blue
 * Menggunakan tabel: mst_wilayah
 * Kolom: kode_wilayah, nama_wilayah, kode_induk, level_wilayah, is_active
 */
require_once __DIR__ . "/../../partials/auth_guard.php";
require_once __DIR__ . '/../../config/config.php';

$title = "Master Wilayah";
$page = "master_wilayah";

// Inisialisasi variabel
$wilayah_data = [];
$error = '';
$success = '';

// Handle parameter pencarian dan filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_level = isset($_GET['level']) ? $_GET['level'] : '';
$page_num = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page_num - 1) * $limit;

// Bangun query
$where_conditions = [];
if (!empty($search)) {
    $safe_search = $conn->real_escape_string($search);
    $where_conditions[] = "(w.kode_wilayah LIKE '%$safe_search%' OR w.nama_wilayah LIKE '%$safe_search%')";
}
if (!empty($filter_level) && $filter_level !== 'semua') {
    $safe_level = $conn->real_escape_string($filter_level);
    $where_conditions[] = "w.level_wilayah = '$safe_level'";
}

$sql_where = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query total data untuk pagination
$res_count = $conn->query("SELECT COUNT(*) as total FROM mst_wilayah w $sql_where");
$total_rows = $res_count ? $res_count->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_rows / $limit);

// Query data wilayah dengan join ke induk (self-join)
$sql = "SELECT w.*, p.nama_wilayah as nama_induk 
        FROM mst_wilayah w 
        LEFT JOIN mst_wilayah p ON w.kode_induk = p.kode_wilayah 
        $sql_where 
        ORDER BY w.level_wilayah ASC, w.kode_wilayah ASC 
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) { $wilayah_data[] = $row; }
}

// Ambil data wilayah level 1 untuk dropdown induk
$wilayah_level1 = [];
$res_lvl1 = $conn->query("SELECT kode_wilayah, nama_wilayah FROM mst_wilayah WHERE level_wilayah = 1 ORDER BY nama_wilayah");
if ($res_lvl1) {
    while ($row = $res_lvl1->fetch_assoc()) {
        $wilayah_level1[] = $row;
    }
}

// Statistik Singkat
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as c FROM mst_wilayah")->fetch_assoc()['c'],
    'aktif' => $conn->query("SELECT COUNT(*) as c FROM mst_wilayah WHERE is_active = 1")->fetch_assoc()['c'],
    'provinsi' => $conn->query("SELECT COUNT(*) as c FROM mst_wilayah WHERE level_wilayah = 1")->fetch_assoc()['c'],
    'kabkota' => $conn->query("SELECT COUNT(*) as c FROM mst_wilayah WHERE level_wilayah = 2")->fetch_assoc()['c']
];

include __DIR__ . "/../../partials/header.php";
?>
<div class="sirambo-wrapper">
    <?php include __DIR__ . "/../../partials/sidebar.php"; ?>

    <div class="sirambo-main">
        <?php include __DIR__ . "/../../partials/navbar.php"; ?>

        <main class="sirambo-content-area">
            <!-- Header -->
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item small"><a href="../dashboard.php" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item small active">Master Wilayah</li>
                        </ol>
                    </nav>
                    <h4 class="fw-bold text-navy-primary mb-0">Manajemen Wilayah</h4>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-navy btn-sm border-secondary-subtle bg-white shadow-sm rounded-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i> Ekspor
                        </button>
                        <ul class="dropdown-menu shadow border-0">
                            <li><a class="dropdown-item py-2" href="export_wilayah.php?type=excel&<?= http_build_query($_GET) ?>"><i class="bi bi-file-earmark-excel me-2 text-success"></i> Ekspor Excel</a></li>
                            <li><a class="dropdown-item py-2" href="export_wilayah.php?type=pdf&<?= http_build_query($_GET) ?>" target="_blank"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i> Cetak PDF</a></li>
                        </ul>
                    </div>
                    <button class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm border-0" data-bs-toggle="modal" data-bs-target="#modalAddWilayah">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Wilayah
                    </button>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card border-0 bg-white p-3 shadow-sm">
                        <small class="text-muted d-block mb-1">Total Data</small>
                        <h4 class="fw-bold mb-0"><?= $stats['total'] ?></h4>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 bg-white p-3 shadow-sm border-start border-success border-4">
                        <small class="text-muted d-block mb-1">Aktif</small>
                        <h4 class="fw-bold mb-0 text-success"><?= $stats['aktif'] ?></h4>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 bg-white p-3 shadow-sm border-start border-primary border-4">
                        <small class="text-muted d-block mb-1">Provinsi (Lvl 1)</small>
                        <h4 class="fw-bold mb-0 text-primary"><?= $stats['provinsi'] ?></h4>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 bg-white p-3 shadow-sm border-start border-info border-4">
                        <small class="text-muted d-block mb-1">Kab/Kota (Lvl 2)</small>
                        <h4 class="fw-bold mb-0 text-info"><?= $stats['kabkota'] ?></h4>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="card card-sirambo border-0 bg-white overflow-hidden shadow-sm">
                <div class="card-header bg-white border-0 p-4 pb-0">
                    <form method="GET" class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Cari Kode atau Nama..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-8 text-md-end">
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                <select name="level" class="form-select form-select-sm" style="width: auto;">
                                    <option value="semua">Semua Level</option>
                                    <option value="1" <?= $filter_level == '1' ? 'selected' : '' ?>>Level 1 (Provinsi)</option>
                                    <option value="2" <?= $filter_level == '2' ? 'selected' : '' ?>>Level 2 (Kab/Kota)</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
                                <?php if(!empty($search) || !empty($filter_level)): ?>
                                    <a href="master-wilayah.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="card-body p-0 mt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small">
                                <tr>
                                    <th class="ps-4 py-3 text-uppercase fw-bold">Kode</th>
                                    <th class="py-3 text-uppercase fw-bold">Nama Wilayah</th>
                                    <th class="py-3 text-uppercase fw-bold">Induk</th>
                                    <th class="py-3 text-uppercase fw-bold text-center">Level</th>
                                    <th class="py-3 text-uppercase fw-bold">Status</th>
                                    <th class="pe-4 py-3 text-uppercase fw-bold text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($wilayah_data)): ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted">Data tidak ditemukan</td></tr>
                                <?php else: foreach($wilayah_data as $w): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-navy-dark small"><?= $w['kode_wilayah'] ?></td>
                                    <td>
                                        <div class="fw-medium"><?= htmlspecialchars($w['nama_wilayah']) ?></div>
                                    </td>
                                    <td class="small text-muted">
                                        <?= $w['nama_induk'] ? '<i class="bi bi-arrow-return-right me-1"></i>'.$w['nama_induk'] : '<span class="badge bg-light text-dark border">Pusat</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3">Lvl <?= $w['level_wilayah'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $w['is_active'] ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 text-<?= $w['is_active'] ? 'success' : 'danger' ?> rounded-pill px-3 py-1 small">
                                            <?= $w['is_active'] ? 'Aktif' : 'Non-aktif' ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                <li><a class="dropdown-item py-2" href="#" onclick="viewDetailWilayah('<?= $w['kode_wilayah'] ?>')"><i class="bi bi-info-circle me-2 text-info"></i> Detail</a></li>
                                                <li><a class="dropdown-item py-2" href="#" onclick="editWilayah('<?= $w['kode_wilayah'] ?>')"><i class="bi bi-pencil me-2 text-primary"></i> Edit</a></li>
                                                <li><a class="dropdown-item py-2" href="#" onclick="toggleStatusWilayah('<?= $w['kode_wilayah'] ?>')"><i class="bi bi-power me-2 text-warning"></i> Status</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item py-2 text-danger" href="#" onclick="deleteWilayah('<?= $w['kode_wilayah'] ?>')"><i class="bi bi-trash me-2"></i> Hapus</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="p-4 d-flex justify-content-between align-items-center border-top">
                        <small class="text-muted">Total <?= $total_rows ?> data wilayah</small>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $page_num <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $page_num-1 ?>&search=<?= $search ?>&level=<?= $filter_level ?>">Prev</a></li>
                                <li class="page-item active"><span class="page-link"><?= $page_num ?></span></li>
                                <li class="page-item <?= $page_num >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $page_num+1 ?>&search=<?= $search ?>&level=<?= $filter_level ?>">Next</a></li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        <?php include __DIR__ . "/../../partials/footer.php"; ?>
    </div>
</div>

<!-- Modal Tambah Wilayah -->
<div class="modal fade" id="modalAddWilayah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy-primary">Tambah Wilayah Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="wilayah_add.php" method="POST" id="formAddWilayah">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Kode Wilayah <span class="text-danger">*</span></label>
                        <input type="text" name="kode_wilayah" class="form-control form-control-sm" required placeholder="Contoh: 32">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Nama Wilayah <span class="text-danger">*</span></label>
                        <input type="text" name="nama_wilayah" class="form-control form-control-sm" required placeholder="Contoh: JAWA BARAT">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-medium">Level Wilayah</label>
                            <select name="level_wilayah" class="form-select form-select-sm level-select" required>
                                <option value="1">1 (Provinsi)</option>
                                <option value="2">2 (Kab/Kota)</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3 induk-container" style="display:none;">
                            <label class="form-label small fw-medium">Wilayah Induk</label>
                            <select name="kode_induk" class="form-select form-select-sm">
                                <option value="">Pilih Provinsi</option>
                                <?php foreach($wilayah_level1 as $prov): ?>
                                    <option value="<?= $prov['kode_wilayah'] ?>"><?= htmlspecialchars($prov['nama_wilayah']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Wilayah -->
<div class="modal fade" id="modalEditWilayah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy-primary">Edit Wilayah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="wilayah_update.php" method="POST" id="formEditWilayah">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Kode Wilayah</label>
                        <input type="text" name="kode_wilayah" id="edit_kode_wilayah" class="form-control form-control-sm bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Nama Wilayah <span class="text-danger">*</span></label>
                        <input type="text" name="nama_wilayah" id="edit_nama_wilayah" class="form-control form-control-sm" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-medium">Level Wilayah</label>
                            <select name="level_wilayah" id="edit_level_wilayah" class="form-select form-select-sm level-select" required>
                                <option value="1">1 (Provinsi)</option>
                                <option value="2">2 (Kab/Kota)</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3 induk-container">
                            <label class="form-label small fw-medium">Wilayah Induk</label>
                            <select name="kode_induk" id="edit_kode_induk" class="form-select form-select-sm">
                                <option value="">Pilih Provinsi</option>
                                <?php foreach($wilayah_level1 as $prov): ?>
                                    <option value="<?= $prov['kode_wilayah'] ?>"><?= htmlspecialchars($prov['nama_wilayah']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                            <label class="form-check-label small" for="edit_is_active">Status Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Perbarui Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Wilayah -->
<div class="modal fade" id="modalDetailWilayah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy-primary">Detail Wilayah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded-3 mb-3">
                    <div class="row g-2">
                        <div class="col-4 text-muted small">Kode Wilayah</div>
                        <div class="col-8 fw-bold" id="detail_kode"></div>
                        
                        <div class="col-4 text-muted small">Nama Wilayah</div>
                        <div class="col-8 fw-bold text-navy-primary" id="detail_nama"></div>
                        
                        <div class="col-4 text-muted small">Level</div>
                        <div class="col-8" id="detail_level"></div>
                        
                        <div class="col-4 text-muted small">Status</div>
                        <div class="col-8" id="detail_status"></div>
                    </div>
                </div>
                
                <div id="container_induk" style="display:none;">
                    <h6 class="fw-bold small mb-2"><i class="bi bi-diagram-2 me-1"></i> Informasi Induk</h6>
                    <div class="p-3 border rounded-3 d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div>
                            <div class="small text-muted lh-1 mb-1">Terdaftar di Provinsi:</div>
                            <div class="fw-bold" id="detail_induk_nama"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi untuk mengatur tampilan dropdown induk berdasarkan level
document.querySelectorAll('.level-select').forEach(select => {
    select.addEventListener('change', function() {
        const container = this.closest('form').querySelector('.induk-container');
        if (this.value == '2') {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    });
});

function editWilayah(kode) {
    fetch('wilayah_get.php?kode=' + kode)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const w = data.data;
                document.getElementById('edit_kode_wilayah').value = w.kode_wilayah;
                document.getElementById('edit_nama_wilayah').value = w.nama_wilayah;
                document.getElementById('edit_level_wilayah').value = w.level_wilayah;
                document.getElementById('edit_kode_induk').value = w.kode_induk || '';
                document.getElementById('edit_is_active').checked = w.is_active == 1;
                
                // Trigger view
                const container = document.getElementById('formEditWilayah').querySelector('.induk-container');
                container.style.display = (w.level_wilayah == '2') ? 'block' : 'none';
                
                const modal = new bootstrap.Modal(document.getElementById('modalEditWilayah'));
                modal.show();
            } else {
                alert('Gagal memuat data: ' + data.message);
            }
        });
}

function viewDetailWilayah(kode) {
    fetch('wilayah_get.php?kode=' + kode)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const w = data.data;
                document.getElementById('detail_kode').innerText = w.kode_wilayah;
                document.getElementById('detail_nama').innerText = w.nama_wilayah;
                document.getElementById('detail_level').innerHTML = `<span class="badge bg-secondary">Level ${w.level_wilayah}</span>`;
                document.getElementById('detail_status').innerHTML = w.is_active == 1 
                    ? '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Aktif</span>' 
                    : '<span class="text-danger fw-bold"><i class="bi bi-x-circle-fill me-1"></i>Non-aktif</span>';
                
                const containerInduk = document.getElementById('container_induk');
                if (w.level_wilayah == 2 && w.kode_induk) {
                    containerInduk.style.display = 'block';
                    // Ambil nama induk (opsional jika wilayah_get tidak menyertakan join)
                    // Untuk sementara kita cari dari dropdown level1 yang ada di HTML
                    const opt = document.querySelector(`#edit_kode_induk option[value="${w.kode_induk}"]`);
                    document.getElementById('detail_induk_nama').innerText = opt ? opt.text : w.kode_induk;
                } else {
                    containerInduk.style.display = 'none';
                }
                
                const modal = new bootstrap.Modal(document.getElementById('modalDetailWilayah'));
                modal.show();
            }
        });
}

function toggleStatusWilayah(kode) {
    if(confirm('Ubah status aktif wilayah ini?')) {
        fetch('wilayah_toggle.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'kode_wilayah=' + kode
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
            else alert(data.message);
        });
    }
}

function deleteWilayah(kode) {
    if(confirm('Hapus wilayah ' + kode + '? Wilayah yang masih memiliki bawahan tidak dapat dihapus.')) {
        fetch('wilayah_delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'kode_wilayah=' + kode
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
            else alert(data.message);
        });
    }
}
</script>

<style>
    .btn-outline-navy { color: var(--navy-primary); border-color: #e2e8f0; }
    .btn-outline-navy:hover { background-color: #f1f5f9; }
    .table-hover tbody tr:hover { background-color: rgba(59, 130, 246, 0.02); }
    .breadcrumb-item + .breadcrumb-item::before { content: "›"; font-size: 1.2rem; vertical-align: middle; }
</style>