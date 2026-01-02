<?php
require_once __DIR__ . '/View.php';

class Controller {
    protected function view(string $view, array $data = []) {
        View::render($view, $data);
    }

    protected function redirect(string $path) {
        header("Location: " . BASE_URL . $path);
        exit;
    }
}
