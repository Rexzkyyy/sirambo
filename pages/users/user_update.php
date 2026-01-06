<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $username = $conn->real_escape_string(trim($_POST['username']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $role = $conn->real_escape_string($_POST['role']);
    $kode_wilayah = !empty($_POST['kode_wilayah']) ? $conn->real_escape_string(trim($_POST['kode_wilayah'])) : NULL;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Cek apakah username/email sudah digunakan oleh user lain
    $check_sql = "SELECT id_user FROM users WHERE (username = '$username' OR email = '$email') AND id_user != $id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username atau email sudah digunakan']);
        exit;
    }
    
    // Update user
    $sql = "UPDATE users SET 
            username = '$username',
            email = '$email',
            role = '$role',
            kode_wilayah = '$kode_wilayah',
            is_active = '$is_active',
            updated_at = NOW()
            WHERE id_user = $id";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'User berhasil diupdate']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update user: ' . $conn->error]);
    }
}