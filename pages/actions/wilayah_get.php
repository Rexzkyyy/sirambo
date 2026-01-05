<?php
/**
 * Action: Get Wilayah Data (AJAX)
 * Lokasi: actions/wilayah_get.php
 */
session_start();
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (!isset($_GET['kode'])) {
    echo json_encode(['success' => false, 'message' => 'Kode wilayah tidak disertakan']);
    exit;
}

$kode = $conn->real_escape_string($_GET['kode']);

// Ambil data wilayah berdasarkan kode
$sql = "SELECT * FROM mst_wilayah WHERE kode_wilayah = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $kode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Data wilayah tidak ditemukan']);
}

$stmt->close();