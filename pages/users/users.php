<?php
/**
 * User Management Page - Tema Navy Blue
 * Menggunakan kolom: id_user, username, email, role, kode_wilayah, is_active, last_login
 * Wilayah dari tabel mst_wilayah
 * Dilengkapi dengan fitur ekspor Excel/PDF
 */
require_once __DIR__ . "/../../partials/auth_guard.php";
require_once __DIR__ . '/../../config/config.php';

$title = "Manajemen Pengguna";
$page = "users";

// Inisialisasi variabel
$users = [];
$error = '';
$success = '';

// Handle pesan dari proses sebelumnya
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Handle parameter pencarian dan filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';
$filter_wilayah = isset($_GET['wilayah']) ? $_GET['wilayah'] : '';
$page_num = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page_num - 1) * $limit;

// Bangun query untuk pencarian dan filter
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $safe_search = $conn->real_escape_string($search);
    $where_conditions[] = "(u.username LIKE '%$safe_search%' OR u.email LIKE '%$safe_search%')";
}

if (!empty($filter_role) && $filter_role !== 'semua') {
    $safe_role = $conn->real_escape_string($filter_role);
    $where_conditions[] = "u.role = '$safe_role'";
}

if (!empty($filter_wilayah) && $filter_wilayah !== 'semua') {
    $safe_wilayah = $conn->real_escape_string($filter_wilayah);
    $where_conditions[] = "u.kode_wilayah = '$safe_wilayah'";
}

$sql_where = '';
if (!empty($where_conditions)) {
    $sql_where = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Query untuk total data (untuk pagination)
$sql_count = "SELECT COUNT(*) as total FROM users u $sql_where";
$result_count = $conn->query($sql_count);
$total_rows = 0;
$total_pages = 1;

if ($result_count) {
    $row_count = $result_count->fetch_assoc();
    $total_rows = $row_count['total'];
    $total_pages = ceil($total_rows / $limit);
}

// Query untuk data dengan pagination
$sql = "SELECT 
            u.id_user, 
            u.username, 
            u.email, 
            u.role, 
            u.kode_wilayah,
            w.nama_wilayah,
            u.is_active, 
            u.last_login, 
            u.created_at 
        FROM users u
        LEFT JOIN mst_wilayah w ON u.kode_wilayah = w.kode_wilayah
        $sql_where
        ORDER BY u.created_at DESC 
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
} else {
    $error = "Gagal mengambil data pengguna: " . $conn->error;
}

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

// Hitung statistik
$total_users = 0;
$active_users = 0;

// Total user
$sql_total = "SELECT COUNT(*) as total FROM users";
$result_total = $conn->query($sql_total);
if ($result_total) {
    $row_total = $result_total->fetch_assoc();
    $total_users = $row_total['total'];
}

// User aktif
$sql_active = "SELECT COUNT(*) as active FROM users WHERE is_active = 1";
$result_active = $conn->query($sql_active);
if ($result_active) {
    $row_active = $result_active->fetch_assoc();
    $active_users = $row_active['active'];
}

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
                            <li class="breadcrumb-item small active" aria-current="page">Pengguna</li>
                        </ol>
                    </nav>
                    <h4 class="fw-bold text-navy-primary mb-0">Kelola Pengguna</h4>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <!-- Dropdown Ekspor -->
                    <div class="dropdown">
                        <button class="btn btn-outline-navy btn-sm border-secondary-subtle bg-white shadow-sm rounded-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i> Ekspor
                        </button>
                        <ul class="dropdown-menu shadow border-0">
                            <li><a class="dropdown-item py-2" href="export_users.php?type=excel&<?= http_build_query($_GET) ?>"><i class="bi bi-file-earmark-excel me-2 text-success"></i> Ekspor ke Excel</a></li>
                            <li><a class="dropdown-item py-2" href="export_users.php?type=pdf&<?= http_build_query($_GET) ?>" target="_blank"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i> Ekspor ke PDF (Cetak)</a></li>
                        </ul>
                    </div>
                    
                    <button class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm border-0" data-bs-toggle="modal" data-bs-target="#modalAddUser">
                        <i class="bi bi-person-plus-fill me-1"></i> Tambah User
                    </button>
                </div>
            </div>

            <!-- Pesan Error/Success -->
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Kartu Ringkasan -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card card-sirambo border-0 bg-white p-3 shadow-sm">
                        <small class="text-muted d-block mb-1">Total User</small>
                        <h4 class="fw-bold mb-0"><?= $total_users ?></h4>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-sirambo border-0 bg-white p-3 shadow-sm border-start border-success border-4">
                        <small class="text-muted d-block mb-1">User Aktif</small>
                        <h4 class="fw-bold mb-0 text-success"><?= $active_users ?></h4>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-sirambo border-0 bg-white p-3 shadow-sm border-start border-warning border-4">
                        <small class="text-muted d-block mb-1">Non-Aktif</small>
                        <h4 class="fw-bold mb-0 text-warning"><?= $total_users - $active_users ?></h4>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card card-sirambo border-0 bg-white p-3 shadow-sm border-start border-info border-4">
                        <small class="text-muted d-block mb-1">Ditampilkan</small>
                        <h4 class="fw-bold mb-0 text-info"><?= count($users) ?></h4>
                    </div>
                </div>
            </div>

            <!-- Tabel Pengguna -->
            <div class="card card-sirambo border-0 bg-white overflow-hidden shadow-sm">
                <div class="card-header bg-white border-0 p-4 pb-0">
                    <form method="GET" action="" class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" 
                                       name="search" 
                                       class="form-control bg-light border-start-0" 
                                       placeholder="Cari username atau email..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-8 text-md-end">
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border dropdown-toggle" data-bs-toggle="dropdown">
                                        Role: <?= !empty($filter_role) ? ucfirst($filter_role) : 'Semua' ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&wilayah=<?= urlencode($filter_wilayah) ?>">Semua Role</a></li>
                                        <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&role=admin&wilayah=<?= urlencode($filter_wilayah) ?>">ADMIN</a></li>
                                        <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&role=prov&wilayah=<?= urlencode($filter_wilayah) ?>">PROV</a></li>
                                        <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&role=kabkota&wilayah=<?= urlencode($filter_wilayah) ?>">KABKOTA</a></li>
                                        <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&role=viewer&wilayah=<?= urlencode($filter_wilayah) ?>">VIEWER</a></li>
                                    </ul>
                                </div>
                                
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border dropdown-toggle" data-bs-toggle="dropdown">
                                        Wilayah: <?= !empty($filter_wilayah) ? $filter_wilayah : 'Semua' ?>
                                    </button>
                                    <ul class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                                        <li><a class="dropdown-item" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($filter_role) ?>">Semua Wilayah</a></li>
                                        <?php foreach($wilayah_options as $wilayah): ?>
                                        <li>
                                            <a class="dropdown-item" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($filter_role) ?>&wilayah=<?= urlencode($wilayah['kode_wilayah']) ?>">
                                                <?= htmlspecialchars($wilayah['kode_wilayah']) ?> - <?= htmlspecialchars($wilayah['nama_wilayah']) ?>
                                            </a>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
                                <?php if (!empty($search) || !empty($filter_role) || !empty($filter_wilayah)): ?>
                                    <a href="users.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Hidden fields untuk pagination -->
                        <input type="hidden" name="page" value="1">
                    </form>
                </div>
                <div class="card-body p-0 mt-3">
                    <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-person-x display-1 text-muted"></i>
                            <p class="text-muted mt-3">Tidak ada data pengguna ditemukan</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small">
                                <tr>
                                    <th class="ps-4 py-3 text-uppercase fw-bold" style="width: 50px;">ID</th>
                                    <th class="py-3 text-uppercase fw-bold">Pengguna</th>
                                    <th class="py-3 text-uppercase fw-bold">Wilayah</th>
                                    <th class="py-3 text-uppercase fw-bold">Role</th>
                                    <th class="py-3 text-uppercase fw-bold">Status</th>
                                    <th class="py-3 text-uppercase fw-bold">Login Terakhir</th>
                                    <th class="pe-4 py-3 text-uppercase fw-bold text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $u): ?>
                                <tr id="user-row-<?= $u['id_user'] ?>">
                                    <td class="ps-4 text-muted small">#<?= $u['id_user'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 38px; height: 38px; font-size: 0.8rem;">
                                                <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                            </div>
                                            <div class="lh-1">
                                                <div class="fw-bold text-navy-dark small"><?= htmlspecialchars($u['username']) ?></div>
                                                <small class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($u['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($u['kode_wilayah'])): ?>
                                        <div class="lh-1">
                                            <span class="badge bg-light text-dark border fw-medium px-2 py-1 rounded-2 small">
                                                <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($u['kode_wilayah']) ?>
                                            </span>
                                            <?php if (!empty($u['nama_wilayah'])): ?>
                                            <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                                <?= htmlspecialchars($u['nama_wilayah']) ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1 rounded-2 small">
                                            <i class="bi bi-dash-circle me-1"></i> Tidak ada
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                    <?php
                                        $role = strtoupper($u['role']); // biar aman dari beda huruf besar/kecil

                                        switch ($role) {
                                            case 'ADMIN':
                                                $roleClass = 'bg-success';
                                                $textClass = 'success';
                                                $roleIcon  = 'bi-shield-lock';
                                                break;

                                            case 'KABKOTA':
                                                $roleClass = 'bg-primary';
                                                $textClass = 'primary';
                                                $roleIcon  = 'bi-building';
                                                break;

                                            case 'VIEWER':
                                                $roleClass = 'bg-warning';
                                                $textClass = 'warning';
                                                $roleIcon  = 'bi-eye';
                                                break;

                                            default:
                                                $roleClass = 'bg-secondary';
                                                $textClass = 'secondary';
                                                $roleIcon  = 'bi-person';
                                        }
                                        ?>

                                        <span class="badge <?= $roleClass ?> bg-opacity-10 text-<?= $textClass ?> 
                                        rounded-pill px-3 py-1 small d-inline-flex align-items-center gap-1">
                                            <i class="bi <?= $roleIcon ?>"></i>
                                            <?= ucfirst(strtolower($role)) ?>
                                        </span>


                                    </td>
                                    <td>
                                        <?php if($u['is_active']): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 fw-medium small">
                                                <i class="bi bi-check-circle me-1"></i>Aktif
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1 fw-medium small">
                                                <i class="bi bi-x-circle me-1"></i>Non-aktif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= !empty($u['last_login']) ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Belum pernah login' ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" 
                                                    style="width: 30px; height: 30px;" 
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 small">
                                                <li>
                                                    <a class="dropdown-item py-2" href="#" onclick="editUser(<?= $u['id_user'] ?>)">
                                                        <i class="bi bi-pencil me-2 text-primary"></i>Edit User
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2" href="#" onclick="toggleStatus(<?= $u['id_user'] ?>)">
                                                        <i class="bi bi-power me-2 text-<?= $u['is_active'] ? 'warning' : 'success' ?>"></i>
                                                        <?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2" href="#" onclick="resetPassword(<?= $u['id_user'] ?>)">
                                                        <i class="bi bi-key me-2 text-info"></i>Reset Password
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <a class="dropdown-item py-2 text-danger" href="#" onclick="deleteUser(<?= $u['id_user'] ?>)">
                                                        <i class="bi bi-trash me-2"></i>Hapus
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="p-4 d-flex flex-column flex-md-row align-items-center justify-content-between border-top">
                        <small class="text-muted mb-2 mb-md-0">
                            Menampilkan <?= count($users) ?> dari <?= $total_rows ?> pengguna
                        </small>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $page_num <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link rounded-start-3" 
                                       href="?search=<?= urlencode($search) ?>&role=<?= urlencode($filter_role) ?>&wilayah=<?= urlencode($filter_wilayah) ?>&page=<?= $page_num - 1 ?>">
                                        Prev
                                    </a>
                                </li>
                                
                                <?php 
                                $start_page = max(1, $page_num - 2);
                                $end_page = min($total_pages, $page_num + 2);
                                
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?search=' . urlencode($search) . '&role=' . urlencode($filter_role) . '&wilayah=' . urlencode($filter_wilayah) . '&page=1">1</a></li>';
                                    if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?= $i == $page_num ? 'active' : '' ?>">
                                        <a class="page-link" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($filter_role) ?>&wilayah=<?= urlencode($filter_wilayah) ?>&page=<?= $i ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; 
                                
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    echo '<li class="page-item"><a class="page-link" href="?search=' . urlencode($search) . '&role=' . urlencode($filter_role) . '&wilayah=' . urlencode($filter_wilayah) . '&page=' . $total_pages . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                
                                <li class="page-item <?= $page_num >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link rounded-end-3" 
                                       href="?search=<?= urlencode($search) ?>&role=<?= urlencode($filter_role) ?>&wilayah=<?= urlencode($filter_wilayah) ?>&page=<?= $page_num + 1 ?>">
                                        Next
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php include __DIR__ . "/../../partials/footer.php"; ?>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="modalAddUser" tabindex="-1" aria-labelledby="modalAddUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy-primary" id="modalAddUserLabel">Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="user_add.php" method="POST" id="formAddUser">
                <div class="modal-body pt-1">
                    <div class="mb-3">
                        <label for="username" class="form-label small fw-medium">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-medium">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control form-control-sm" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-medium">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-sm" id="password" name="password" required>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label small fw-medium">Role <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="role" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="ADMIN">ADMIN</option>
                                <option value="KABKOTA">KABKOTA</option>
                                <option value="PROV">PROV</option>
                                <option value="VIEWER">Viewer</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="kode_wilayah" class="form-label small fw-medium">Wilayah</label>
                            <select class="form-select form-select-sm" id="kode_wilayah" name="kode_wilayah">
                                <option value="">Pilih Wilayah</option>
                                <?php foreach($wilayah_options as $wilayah): ?>
                                <option value="<?= htmlspecialchars($wilayah['kode_wilayah']) ?>">
                                    <?= htmlspecialchars($wilayah['kode_wilayah']) ?> - <?= htmlspecialchars($wilayah['nama_wilayah']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-navy-primary" id="modalEditUserLabel">Edit Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="user_update.php" method="POST" id="formEditUser">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body pt-1">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label small fw-medium">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label small fw-medium">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control form-control-sm" id="edit_email" name="email" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_role" class="form-label small fw-medium">Role <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="edit_role" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="ADMIN">Admin</option>
                                <option value="KABKOTA">KABKOTA</option>
                                <option value="PROV">PROV</option>
                                <option value="VIEWER">Viewer</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_kode_wilayah" class="form-label small fw-medium">Wilayah</label>
                            <select class="form-select form-select-sm" id="edit_kode_wilayah" name="kode_wilayah">
                                <option value="">Pilih Wilayah</option>
                                <?php foreach($wilayah_options as $wilayah): ?>
                                <option value="<?= htmlspecialchars($wilayah['kode_wilayah']) ?>">
                                    <?= htmlspecialchars($wilayah['kode_wilayah']) ?> - <?= htmlspecialchars($wilayah['nama_wilayah']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="edit_is_active" name="is_active">
                        <label class="form-check-label small" for="edit_is_active">Aktifkan user</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
                </div>
            </form>
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
    .bg-navy-dark { background-color: #0f172a !important; }
    
    /* Hover effect for rows */
    .table-hover tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.02);
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        content: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='currentColor'/%3E%3C/svg%3E");
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
// Fungsi untuk mengedit user
function editUser(id) {
    fetch(`user_get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                document.getElementById('edit_id').value = user.id_user;
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_role').value = user.role;
                document.getElementById('edit_kode_wilayah').value = user.kode_wilayah || '';
                document.getElementById('edit_is_active').checked = user.is_active == 1;
                
                const modal = new bootstrap.Modal(document.getElementById('modalEditUser'));
                modal.show();
            } else {
                alert('Gagal mengambil data user: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengambil data user');
        });
}

// Fungsi untuk toggle status user
function toggleStatus(id) {
    if (confirm('Apakah Anda yakin ingin mengubah status user ini?')) {
        fetch('user_toggle_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal mengubah status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengubah status');
        });
    }
}

// Fungsi untuk reset password
function resetPassword(id) {
    if (confirm('Reset password ke default (password123)?')) {
        fetch('user_reset_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                // Bisa tambahkan notifikasi sukses
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat reset password');
        });
    }
}

// Fungsi untuk menghapus user
function deleteUser(id) {
    if (confirm('Apakah Anda yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan.')) {
        fetch('user_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hapus baris dari tabel
                const row = document.getElementById(`user-row-${id}`);
                if (row) {
                    row.remove();
                }
                // Tampilkan pesan sukses
                alert('User berhasil dihapus');
                // Refresh halaman untuk update statistik
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Gagal menghapus user: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus user');
        });
    }
}

// Validasi form tambah user
document.getElementById('formAddUser')?.addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    if (password.length < 6) {
        e.preventDefault();
        alert('Password minimal 6 karakter');
        return false;
    }
});

// Handle form edit user
document.getElementById('formEditUser')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('user_update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditUser'));
            modal.hide();
            location.reload();
        } else {
            alert('Gagal update user: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat update user');
    });
});

// Auto-hide alert setelah 5 detik
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>