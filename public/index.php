<?php
require __DIR__ . '/../vendor/autoload.php';
$routes = include __DIR__ . '/../Routing/routes.php';
$path = ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');

if (isset($routes[$path])) {
    $view = $routes[$path];
    $viewPath = __DIR__ . '/../Views/' . $view . '.php';
    if (is_file($viewPath)) {
        include __DIR__ . '/../Views/layout/header.php';
        include $viewPath;
        include __DIR__ . '/../Views/layout/footer.php';
        exit;
    }
    http_response_code(500);
    echo "Internal error: view not found.";
    exit;
}
http_response_code(404);
echo "404 Not Found";
