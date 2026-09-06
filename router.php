<?php
/**
 * Front controller for `php -S` only. Apache uses .htaccess and Vercel uses
 * api/index.php; all three follow the same map in includes/routes.php.
 */
require_once __DIR__ . '/includes/routes.php';

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (route_is_blocked($path)) {
    http_response_code(403);
    exit('Forbidden');
}

// Serve real files (images, css) straight from disk.
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

$script = route_resolve($path);

if ($script) {
    // Windows paths come back with backslashes; the SCRIPT_NAME PHP exposes
    // to the app should look like the URL it was reached by.
    $rel = substr($script, strlen(__DIR__));
    $_SERVER['SCRIPT_NAME']     = strtr($rel, [chr(92) => '/']);
    $_SERVER['SCRIPT_FILENAME'] = $script;
    require $script;
    return true;
}

http_response_code(404);
require __DIR__ . '/404.php';
return true;
