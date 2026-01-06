<?php
/**
 * Action: Toggle Status Wilayah (AJAX)
 * Lokasi: actions/wilayah_toggle.php
 */
session_start();
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kode_wilayah'])) {
    $kode = $conn->real_escape_string($_POST['kode_wilayah']);

    // Ambil status saat ini
    $sql_check = "SELECT is_active FROM mst_wilayah WHERE kode_wilayah = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $kode);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Wilayah tidak ditemukan']);
        exit;
    }

    $current_status = $result->fetch_assoc()['is_active'];
    $new_status = ($current_status == 1) ? 0 : 1;
    $stmt_check->close();

    // Update status
    $sql_update = "UPDATE mst_wilayah SET is_active = ? WHERE kode_wilayah = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("is", $new_status, $kode);

    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status: ' . $conn->error]);
    }
    $stmt_update->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid']);
}