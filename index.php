<?php
// Allow client.html during local testing (remove for prod)
header("Access-Control-Allow-Origin: *");

// Composer autoload + your PSR-4
spl_autoload_extensions(".php");
spl_autoload_register(function($class) {
    $file = __DIR__ . '/'  . str_replace('\\', '/', $class). '.php';
    if (file_exists(stream_resolve_include_path($file))) include($file);
});

// Load routes
$routes = include('Routing/routes.php');

// Parse request path like "s/abcd1234"
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = ltrim($path, '/');

// 1) Try exact match (e.g., "paste", "paste/create", "api/snippets")
if (isset($routes[$path])) {
    $handler = $routes[$path];
}
// 2) Fallback: dynamic first-segment match (e.g., "s/{slug}", "raw/{slug}")
else {
    $first = strtok($path, '/');   // "s" from "s/abcd"
    if ($first && isset($routes[$first])) {
        $handler = $routes[$first];
    } else {
        http_response_code(404);
        echo "404 Not Found: The requested route was not found on this server.";
        exit;
    }
}

try {
    /** @var \Response\HTTPRenderer $renderer */
    $renderer = $handler();

    // Set headers from renderer (basic sanitize)
    foreach ($renderer->getFields() as $name => $value) {
        $sanitized = filter_var($value, FILTER_UNSAFE_RAW);
        header("{$name}: {$sanitized}");
    }

    echo $renderer->getContent();
} catch (Throwable $e) {
    http_response_code(500);
    echo "Internal error, please contact the admin.";
}

