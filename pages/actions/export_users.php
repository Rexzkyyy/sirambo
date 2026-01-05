<?php
/**
 * Logic Export Users - Excel & PDF/Print
 * File ini menangani proses ekspor data berdasarkan filter yang dikirim dari users.php
 */
require_once __DIR__ . "/../../partials/auth_guard.php";
require_once __DIR__ . '/../../config/config.php';

// Pastikan hanya ADMIN yang bisa ekspor (opsional)
if ($_SESSION['role'] !== 'ADMIN') {
    die("Akses ditolak.");
}

$type = isset($_GET['type']) ? $_GET['type'] : 'excel';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';
$filter_wilayah = isset($_GET['wilayah']) ? $_GET['wilayah'] : '';

// Bangun query (Sama dengan users.php agar data sinkron)
$where_conditions = [];
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

$sql_where = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT u.*, w.nama_wilayah 
        FROM users u
        LEFT JOIN mst_wilayah w ON u.kode_wilayah = w.kode_wilayah
        $sql_where
        ORDER BY u.created_at DESC";

$result = $conn->query($sql);
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) { $data[] = $row; }
}

// --- LOGIKA EKSPOR EXCEL ---
if ($type === 'excel') {
    $filename = "Data_Pengguna_" . date('Ymd_His') . ".xls";
    
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<table border="1">';
    echo '<tr><th colspan="7" style="font-size: 16px;">LAPORAN DATA PENGGUNA</th></tr>';
    echo '<tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Wilayah</th>
            <th>Status</th>
            <th>Dibuat Pada</th>
          </tr>';

    foreach ($data as $row) {
        $status = $row['is_active'] ? 'Aktif' : 'Non-aktif';
        echo "<tr>
                <td>{$row['id_user']}</td>
                <td>{$row['username']}</td>
                <td>{$row['email']}</td>
                <td>{$row['role']}</td>
                <td>{$row['kode_wilayah']} - {$row['nama_wilayah']}</td>
                <td>{$status}</td>
                <td>{$row['created_at']}</td>
              </tr>";
    }
    echo '</table>';
    exit;
}

// --- LOGIKA EKSPOR PDF (Tampilan Print) ---
if ($type === 'pdf'):
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Data Pengguna</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-center { text-align: center; }
        @media print {
            .no-print { display: none; }
            body { margin: 1cm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #fff3cd; padding: 10px; margin-bottom: 20px; border: 1px solid #ffeeba;">
        <button onclick="window.print()">Klik untuk Cetak / Simpan PDF</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="header">
        <h2 style="margin:0;">LAPORAN DATA PENGGUNA</h2>
        <p style="margin:5px 0;">Sistem Informasi Manajemen Wilayah</p>
        <small>Dicetak pada: <?= date('d/m/Y H:i') ?></small>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Wilayah</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr><td colspan="6" class="text-center">Tidak ada data.</td></tr>
            <?php else: $no = 1; foreach($data as $u): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= $u['role'] ?></td>
                    <td><?= htmlspecialchars($u['kode_wilayah'] ?: '-') ?></td>
                    <td><?= $u['is_active'] ? 'Aktif' : 'Non-aktif' ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <script>
        // Otomatis buka dialog print saat halaman dimuat
        window.onload = function() {
            // window.print();
        };
    </script>
</body>
</html>
<?php endif; ?>