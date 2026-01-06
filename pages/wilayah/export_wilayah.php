<?php
/**
 * Logic Export Wilayah - Excel & PDF/Print
 * File ini menangani proses ekspor data berdasarkan filter yang dikirim dari master_wilayah.php
 */
require_once __DIR__ . "/../../partials/auth_guard.php";
require_once __DIR__ . '/../../config/config.php';

// Pastikan hanya ADMIN yang bisa ekspor (opsional, sesuaikan dengan kebutuhan sistem Anda)
// Pastikan hanya ADMIN yang bisa ekspor (opsional)
if ($_SESSION['role'] !== 'ADMIN') {
    die("Akses ditolak.");
}


$type = isset($_GET['type']) ? $_GET['type'] : 'excel';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_level = isset($_GET['level']) ? $_GET['level'] : '';

// Bangun query (Sama dengan master_wilayah.php agar data sinkron)
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

// Query data wilayah dengan join ke induk (self-join) untuk mendapatkan nama wilayah induk
$sql = "SELECT w.*, p.nama_wilayah as nama_induk 
        FROM mst_wilayah w 
        LEFT JOIN mst_wilayah p ON w.kode_induk = p.kode_wilayah 
        $sql_where 
        ORDER BY w.level_wilayah ASC, w.kode_wilayah ASC";

$result = $conn->query($sql);
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) { 
        $data[] = $row; 
    }
}

// --- LOGIKA EKSPOR EXCEL ---
if ($type === 'excel') {
    $filename = "Master_Wilayah_" . date('Ymd_His') . ".xls";
    
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<table border="1">';
    echo '<tr><th colspan="6" style="font-size: 16px; background-color: #0f172a; color: white;">LAPORAN DATA MASTER WILAYAH</th></tr>';
    echo '<tr>
            <th style="background-color: #f1f5f9;">No</th>
            <th style="background-color: #f1f5f9;">Kode Wilayah</th>
            <th style="background-color: #f1f5f9;">Nama Wilayah</th>
            <th style="background-color: #f1f5f9;">Wilayah Induk</th>
            <th style="background-color: #f1f5f9;">Level</th>
            <th style="background-color: #f1f5f9;">Status</th>
          </tr>';

    $no = 1;
    foreach ($data as $row) {
        $status = $row['is_active'] ? 'Aktif' : 'Non-aktif';
        $induk = !empty($row['nama_induk']) ? $row['nama_induk'] : ($row['level_wilayah'] == 1 ? 'Pusat' : '-');
        
        // Gunakan tanda kutip tunggal di depan kode agar nol di depan tidak hilang di Excel
        echo "<tr>
                <td align='center'>{$no}</td>
                <td>'{$row['kode_wilayah']}</td>
                <td>" . strtoupper($row['nama_wilayah']) . "</td>
                <td>{$induk}</td>
                <td align='center'>Level {$row['level_wilayah']}</td>
                <td>{$status}</td>
              </tr>";
        $no++;
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
    <title>Cetak Master Wilayah</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #0f172a; padding-bottom: 15px; }
        .header h2 { margin: 0; color: #0f172a; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 5px 0; font-size: 13px; color: #64748b; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px 8px; text-align: left; }
        th { background-color: #f8fafc; color: #0f172a; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        
        .text-center { text-align: center; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .badge-success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        .footer { margin-top: 30px; text-align: right; font-style: italic; color: #94a3b8; }

        @media print {
            .no-print { display: none; }
            body { margin: 0.5cm; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #f1f5f9; padding: 15px; margin-bottom: 25px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: 600; color: #475569;"><i class="bi bi-info-circle me-2"></i> Pratinjau Cetak Laporan</span>
        <div>
            <button onclick="window.print()" style="background: #0f172a; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" style="background: white; color: #64748b; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-left: 8px;">
                Tutup
            </button>
        </div>
    </div>

    <div class="header">
        <h2>LAPORAN DATA MASTER WILAYAH</h2>
        <p>Sistem Informasi Manajemen Wilayah (SIRAMBO)</p>
        <small>Dicetak pada: <?= date('d/m/Y H:i:s') ?> oleh <?= htmlspecialchars($_SESSION['username']) ?></small>
    </div>

    <table>
        <thead>
            <tr>
                <th width="40" class="text-center">No</th>
                <th width="100">Kode</th>
                <th>Nama Wilayah</th>
                <th>Wilayah Induk</th>
                <th width="80" class="text-center">Level</th>
                <th width="80" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr><td colspan="6" class="text-center" style="padding: 30px;">Tidak ada data wilayah yang ditemukan.</td></tr>
            <?php else: $no = 1; foreach($data as $u): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td style="font-family: monospace; font-weight: bold;"><?= $u['kode_wilayah'] ?></td>
                    <td><?= htmlspecialchars(strtoupper($u['nama_wilayah'])) ?></td>
                    <td>
                        <?php 
                        if (!empty($u['nama_induk'])) {
                            echo htmlspecialchars($u['nama_induk']);
                        } else {
                            echo $u['level_wilayah'] == 1 ? '<span style="color: #64748b; font-style: italic;">Pusat</span>' : '-';
                        }
                        ?>
                    </td>
                    <td class="text-center">Level <?= $u['level_wilayah'] ?></td>
                    <td class="text-center">
                        <?php if($u['is_active']): ?>
                            <span class="badge badge-success">AKTIF</span>
                        <?php else: ?>
                            <span class="badge badge-danger">NON-AKTIF</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Dokumen ini dihasilkan secara otomatis oleh sistem.</p>
    </div>

    <script>
        // Dialog print otomatis bisa diaktifkan jika diperlukan
        // window.onload = function() { window.print(); };
    </script>
</body>
</html>
<?php endif; ?>