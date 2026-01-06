<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    
    // Cek apakah user ada
    $check_sql = "SELECT id_user FROM users WHERE id_user = $id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit;
    }
    
    // Hapus user
    $sql = "DELETE FROM users WHERE id_user = $id";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'User berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus user: ' . $conn->error]);
    }
}