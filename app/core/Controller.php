<?php
require_once __DIR__ . '/View.php';

class Controller {
    protected function view(string $view, array $data = [], string $layout = 'main') {
        View::render($view, $data, $layout);
    }

    protected function redirect(string $path) {
        header("Location: " . BASE_URL . $path);
        exit;
    }

    protected function requireAuth() {
        if (empty($_SESSION['user'])) {
            $this->redirect('/login');
        }
    }
}
