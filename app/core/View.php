<?php
class View {
    public static function render(string $view, array $data = []) {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("View not found: " . $viewFile);
        }

        include __DIR__ . '/../views/layouts/header.php';
        include __DIR__ . '/../views/layouts/navbar.php';
        include __DIR__ . '/../views/layouts/sidebar.php';
        include $viewFile;
        include __DIR__ . '/../views/layouts/footer.php';
    }
}
