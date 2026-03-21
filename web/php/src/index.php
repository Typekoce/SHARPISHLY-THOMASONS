<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
// Location: php/src/index.php
use App\Controllers\HomeController;

// ────────────────────────────────────────────────
//  Extract subdomain & path
// ────────────────────────────────────────────────
$host      = $_SERVER['HTTP_HOST']      ?? 'localhost';
$uri       = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$uriParts  = explode('/', trim($uri, '/'));
$firstSlug = $uriParts[0] ?? '';

$hostParts   = explode('.', $host);
$subdomain   = count($hostParts) >= 3 ? $hostParts[0] : null;

// ────────────────────────────────────────────────
//  Tier 1 – Subdomain routing (docs.sharpishly.vm → DocsController)
// ────────────────────────────────────────────────
if ($subdomain && !in_array($subdomain, ['www', 'sharpishly', 'localhost'])) {
    $controllerClass = "App\\Controllers\\" . ucfirst($subdomain) . "Controller";

    if (class_exists($controllerClass)) {
        (new $controllerClass())->index();
        exit;
    }
}

// ────────────────────────────────────────────────
//  Tier 2 – Hard-coded / special paths
// ────────────────────────────────────────────────
$specialRoutes = [
    '/jeff_bezo' => 'App\\Controllers\\AmazonController',
    '/neural'    => 'App\\Controllers\\OllamaController',
    '/docs'      => 'App\\Controllers\\DocsController',
    // Add more here as needed – very explicit & easy to read
];

if (isset($specialRoutes[$uri])) {
    (new $specialRoutes[$uri]())->index();
    exit;
}

// ────────────────────────────────────────────────
//  Tier 3 – Slug-based auto-mapping (most common case)
// ────────────────────────────────────────────────
$controllerName = ucfirst(str_replace('_', '', ucwords($firstSlug, '_')));
$controllerClass = "App\\Controllers\\{$controllerName}Controller";

if (class_exists($controllerClass)) {
    $controller = new $controllerClass();
    $method     = $uriParts[1] ?? 'index';

    if (method_exists($controller, $method)) {
        $controller->$method(...array_slice($uriParts, 2));
    } else {
        $controller->index();
    }
} else {
    (new HomeController())->index();
}
