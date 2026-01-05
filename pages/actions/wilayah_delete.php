<?php
/**
 * Action: Delete Wilayah (AJAX)
 * Lokasi: actions/wilayah_delete.php
 */
session_start();
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kode_wilayah'])) {
    $kode = $conn->real_escape_string($_POST['kode_wilayah']);

    // Cek apakah wilayah ini digunakan sebagai induk oleh wilayah lain
    $sql_ref = "SELECT kode_wilayah FROM mst_wilayah WHERE kode_induk = ?";
    $stmt_ref = $conn->prepare($sql_ref);
    $stmt_ref->bind_param("s", $kode);
    $stmt_ref->execute();
    $result_ref = $stmt_ref->get_result();

    if ($result_ref->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus wilayah ini karena masih memiliki wilayah bawahan']);
        exit;
    }
    $stmt_ref->close();

    // Proses hapus
    $sql_delete = "DELETE FROM mst_wilayah WHERE kode_wilayah = ?";
    $stmt_del = $conn->prepare($sql_delete);
    $stmt_del->bind_param("s", $kode);

    if ($stmt_del->execute()) {
        echo json_encode(['success' => true, 'message' => 'Wilayah berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus data: ' . $conn->error]);
    }
    $stmt_del->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid']);
}