<?php
/**
 * Action: Tambah Wilayah
 * Lokasi: actions/wilayah_add.php
 */
session_start();
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi Input Wajib
    if (empty($_POST['kode_wilayah']) || empty($_POST['nama_wilayah']) || empty($_POST['level_wilayah'])) {
        $_SESSION['error'] = 'Kode, Nama, dan Level wilayah wajib diisi';
        header('Location: ../master-wilayah.php');
        exit;
    }
    
    // 2. Sanitasi Data
    $kode_wilayah  = $conn->real_escape_string(trim($_POST['kode_wilayah']));
    $nama_wilayah  = $conn->real_escape_string(trim(strtoupper($_POST['nama_wilayah']))); // Nama wilayah biasanya kapital
    $level_wilayah = (int)$_POST['level_wilayah'];
    $kode_induk    = !empty($_POST['kode_induk']) ? $conn->real_escape_string(trim($_POST['kode_induk'])) : NULL;
    $is_active     = isset($_POST['is_active']) ? 1 : 0;
    
    // 3. Validasi Logika Bisnis
    // Jika level 2 (Kab/Kota), kode_induk wajib ada
    if ($level_wilayah == 2 && empty($kode_induk)) {
        $_SESSION['error'] = 'Wilayah induk (Provinsi) wajib dipilih untuk level 2';
        header('Location: ../master-wilayah.php');
        exit;
    }

    // 4. Cek Duplikasi Kode Wilayah
    $check_sql = "SELECT kode_wilayah FROM mst_wilayah WHERE kode_wilayah = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("s", $kode_wilayah);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $_SESSION['error'] = "Kode wilayah '$kode_wilayah' sudah terdaftar";
        header('Location: ../master-wilayah.php');
        exit;
    }
    $stmt_check->close();

    // 5. Simpan Data dengan Prepared Statement
    $sql = "INSERT INTO mst_wilayah (kode_wilayah, nama_wilayah, kode_induk, level_wilayah, is_active) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // "ssisi" -> string, string, string (nullable), integer, integer
        $stmt->bind_param("sssii", $kode_wilayah, $nama_wilayah, $kode_induk, $level_wilayah, $is_active);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Wilayah '$nama_wilayah' berhasil ditambahkan";
        } else {
            $_SESSION['error'] = 'Gagal menyimpan data: ' . $stmt->error;
        }
        
        $stmt->close();
    } else {
        $_SESSION['error'] = 'Gagal mempersiapkan query: ' . $conn->error;
    }
    
    header('Location: ../master-wilayah.php');
    exit;
} else {
    // Jika akses langsung via URL (bukan POST)
    header('Location: ../master-wilayah.php');
    exit;
}