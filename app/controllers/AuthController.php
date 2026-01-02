<?php
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
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === AUTH_USER && $password === AUTH_PASS) {
            $_SESSION['user'] = $username;
            session_regenerate_id(true);
            $this->redirect('/');
        }

        $_SESSION['auth_error'] = 'Username atau password salah';
        $this->redirect('/login');
    }

    public function logout() {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        $this->redirect('/login');
    }
}
