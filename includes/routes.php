<?php
/**
 * The URL map, shared by both front controllers:
 *   router.php     — PHP's built-in server (local development)
 *   api/index.php  — Vercel serverless
 *
 * Apache uses .htaccess instead, but the rules are kept in step with this file.
 */

/** Paths that must never be served, whatever the host. */
function route_is_blocked($path) {
    return (bool)preg_match('#^/(config|data|includes|api)/#', $path)
        || $path === '/products/products.json'
        || $path === '/router.php'
        || basename($path) === '.htaccess';
}

/**
 * Resolve a request path to a script, filling $_GET on the way.
 * Returns an absolute file path, or null when nothing matches.
 */
function route_resolve($path) {
    $root = dirname(__DIR__);

    $routes = [
        '#^/page/(\d+)/?$#'                  => ['/index.php',              ['page' => 1]],
        '#^/category/([^/]+)/(\d+)/?$#'      => ['/category/view.php',      ['cat' => 1, 'page' => 2]],
        '#^/category/([^/]+)/?$#'            => ['/category/view.php',      ['cat' => 1]],
        '#^/products/(\d+)(?:/([^/]*))?/?$#' => ['/products/view.php',      ['id' => 1, 'url' => 2]],
        '#^/cart(?:/view)?/?$#'              => ['/cart/view.php',          []],
        '#^/checkout(?:/full-name)?/?$#'     => ['/checkout/full-name.php', []],
        '#^/confirm/thanks/?$#'              => ['/confirm/thanks.php',     []],
        '#^/sitemap\.xml$#'                  => ['/sitemap.php',            []],
    ];

    foreach ($routes as $pattern => [$script, $params]) {
        if (preg_match($pattern, $path, $m)) {
            foreach ($params as $key => $group) {
                if (isset($m[$group]) && $m[$group] !== '') $_GET[$key] = $m[$group];
            }
            if (is_file($root . $script)) return $root . $script;
        }
    }

    // Extensionless page: /pages/about-us -> /pages/about-us.php
    if (preg_match('#^/(pages/[A-Za-z0-9_-]+)/?$#', $path, $m) && is_file($root . '/' . $m[1] . '.php')) {
        return $root . '/' . $m[1] . '.php';
    }

    // Any other extensionless path that maps onto a .php file
    if ($path !== '/' && preg_match('#^/[A-Za-z0-9_/-]+$#', $path)) {
        $candidate = $root . rtrim($path, '/') . '.php';
        if (is_file($candidate)) return $candidate;
    }

    // Directory index: /admin/ -> /admin/index.php
    if (is_dir($root . $path) && is_file(rtrim($root . $path, '/') . '/index.php')) {
        return rtrim($root . $path, '/') . '/index.php';
    }

    // A .php file addressed directly
    if (preg_match('#\.php$#', $path) && is_file($root . $path)) {
        return $root . $path;
    }

    if ($path === '/' || $path === '/index.php') {
        return $root . '/index.php';
    }

    return null;
}
