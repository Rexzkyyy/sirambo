<?php
/**
 * Action: Update Wilayah
 * Lokasi: actions/wilayah_update.php
 */
session_start();
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi Input Dasar
    if (empty($_POST['kode_wilayah']) || empty($_POST['nama_wilayah']) || empty($_POST['level_wilayah'])) {
        $_SESSION['error'] = 'Kode, Nama, dan Level wilayah wajib diisi';
        header('Location: ../master-wilayah.php');
        exit;
    }

    $kode_wilayah  = $conn->real_escape_string(trim($_POST['kode_wilayah']));
    $nama_wilayah  = $conn->real_escape_string(trim(strtoupper($_POST['nama_wilayah'])));
    $level_wilayah = (int)$_POST['level_wilayah'];
    $kode_induk    = !empty($_POST['kode_induk']) ? $conn->real_escape_string(trim($_POST['kode_induk'])) : NULL;
    $is_active     = isset($_POST['is_active']) ? 1 : 0;

    // 2. Validasi Hubungan Induk dan Level
    $nama_induk_teks = "";

    if ($level_wilayah == 1) {
        // Jika Level 1 (Provinsi), induk harus dikosongkan
        $kode_induk = NULL;
    } elseif ($level_wilayah == 2) {
        // Jika Level 2 (Kab/Kota), wajib punya induk Level 1
        if (empty($kode_induk)) {
            $_SESSION['error'] = 'Kabupaten/Kota wajib memiliki Wilayah Induk (Provinsi)';
            header('Location: ../master-wilayah.php');
            exit;
        }

        // Cek validasi apakah kode_induk benar-benar ada dan merupakan Level 1
        $check_induk = $conn->prepare("SELECT nama_wilayah FROM mst_wilayah WHERE kode_wilayah = ? AND level_wilayah = 1");
        $check_induk->bind_param("s", $kode_induk);
        $check_induk->execute();
        $res_induk = $check_induk->get_result();

        if ($res_induk->num_rows === 0) {
            $_SESSION['error'] = 'Wilayah Induk tidak valid atau bukan merupakan tingkat Provinsi';
            header('Location: ../master-wilayah.php');
            exit;
        }
        
        $data_induk = $res_induk->fetch_assoc();
        $nama_induk_teks = $data_induk['nama_wilayah'];
        $check_induk->close();
    }

    // 3. Pastikan kode wilayah tidak menjadi induk bagi dirinya sendiri
    if ($kode_wilayah === $kode_induk) {
        $_SESSION['error'] = 'Wilayah tidak boleh menjadi induk bagi dirinya sendiri';
        header('Location: ../master-wilayah.php');
        exit;
    }

    // 4. Proses Update Data
    $sql = "UPDATE mst_wilayah 
            SET nama_wilayah = ?, kode_induk = ?, level_wilayah = ?, is_active = ? 
            WHERE kode_wilayah = ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssiis", $nama_wilayah, $kode_induk, $level_wilayah, $is_active, $kode_wilayah);
        
        if ($stmt->execute()) {
            // Membuat pesan sukses yang lebih detail
            $detail_msg = ($level_wilayah == 2) 
                ? "Wilayah '$nama_wilayah' (Kab/Kota) berhasil diperbarui di bawah Provinsi $nama_induk_teks" 
                : "Data Provinsi '$nama_wilayah' berhasil diperbarui";
                
            $_SESSION['success'] = $detail_msg;
        } else {
            $_SESSION['error'] = 'Gagal memperbarui data: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = 'Gagal mempersiapkan query database: ' . $conn->error;
    }

    // Redirect kembali ke halaman Master Wilayah
    header('Location: ../master-wilayah.php');
    exit;
} else {
    // Jika bukan metode POST, kembalikan ke halaman utama
    header('Location: ../master-wilayah.php');
    exit;
}