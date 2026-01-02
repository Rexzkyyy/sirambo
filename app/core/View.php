<?php
class View {
    public static function render(string $view, array $data = [], string $layout = 'main') {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("View not found: " . $viewFile);
        }

        if ($layout === 'auth') {
            include __DIR__ . '/../views/layouts/auth_header.php';
            include $viewFile;
            include __DIR__ . '/../views/layouts/auth_footer.php';
            return;
        }

        include __DIR__ . '/../views/layouts/header.php';
        include __DIR__ . '/../views/layouts/navbar.php';
        include __DIR__ . '/../views/layouts/sidebar.php';
        include $viewFile;
        include __DIR__ . '/../views/layouts/footer.php';
    }
}
