<?php
/**
 * Vercel serverless entry point. vercel.json routes every request that is not a
 * static asset here; the URL map itself lives in includes/routes.php so the
 * local server and this file cannot drift apart.
 */
require_once dirname(__DIR__) . '/includes/routes.php';

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

if (route_is_blocked($path)) {
    http_response_code(403);
    exit('Forbidden');
}

$script = route_resolve($path);

if ($script) {
    chdir(dirname($script));
    require $script;
    exit;
}

http_response_code(404);
require dirname(__DIR__) . '/404.php';
