<?php
class Router {
    private array $routes = [];

    public function get(string $path, callable $handler) { $this->map('GET', $path, $handler); }
    public function post(string $path, callable $handler) { $this->map('POST', $path, $handler); }

    private function map(string $method, string $path, callable $handler) {
        $this->routes[] = compact('method','path','handler');
    }

    public function dispatch(string $method, string $uri) {
        $path = parse_url($uri, PHP_URL_PATH);

        // hapus BASE_URL dari awal path
        $base = rtrim(BASE_URL, '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
            if ($path === '') $path = '/';
        }

        foreach ($this->routes as $r) {
            if ($r['method'] === $method && $r['path'] === $path) {
                return call_user_func($r['handler']);
            }
        }

        http_response_code(404);
        echo "404 - Route not found: " . htmlspecialchars($path);
    }
}
