<?php
require_once __DIR__ . '/../config/database.php';

class AuthController extends Controller {
    public function showLogin() {
        if (!empty($_SESSION['user'])) {
            $this->redirect('/');
        }

        $error = $_SESSION['auth_error'] ?? null;
        unset($_SESSION['auth_error']);

        $this->view('auth/login', [
            'title' => 'Login',
            'error' => $error,
        ], 'auth');
    }

    public function login() {
        $identifier = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($identifier === '' || $password === '') {
            $_SESSION['auth_error'] = 'Username/email dan password wajib diisi';
            $this->redirect('/login');
        }

        try {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT id_user, nama, email, password_hash, role, kode_wilayah, is_active FROM users WHERE (id_user = :id OR email = :id) LIMIT 1');
            $stmt->execute([':id' => $identifier]);
            $user = $stmt->fetch();

            $passwordMatch = $user && (password_verify($password, $user['password_hash']) || hash_equals((string)$user['password_hash'], $password));

            if ($user && (int)$user['is_active'] === 1 && $passwordMatch) {
                $_SESSION['user'] = [
                    'id' => $user['id_user'],
                    'name' => $user['nama'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'kode_wilayah' => $user['kode_wilayah'],
                ];
                session_regenerate_id(true);

                $update = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id_user = :id');
                $update->execute([':id' => $user['id_user']]);

                $this->redirect('/');
            }

            if ($user && (int)$user['is_active'] !== 1) {
                $_SESSION['auth_error'] = 'Akun Anda tidak aktif. Hubungi administrator BPS.';
            } else {
                $_SESSION['auth_error'] = 'Kredensial tidak sesuai dengan data pengguna.';
            }
        } catch (Exception $e) {
            $_SESSION['auth_error'] = 'Login gagal karena koneksi database bermasalah.';
        }

        $this->redirect('/login');
    }

    public function logout() {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        $this->redirect('/login');
    }
}
