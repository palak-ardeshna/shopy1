<?php
/**
 * Vercel serverless entry point. vercel.json routes every request that is not a
 * static asset here; the URL map itself lives in includes/routes.php so the
 * local server and this file cannot drift apart.
 */

// Surface PHP errors in the response. Vercel otherwise swallows a fatal and
// shows only its generic "This Serverless Function has crashed" page, which
// says nothing about the cause. Set APP_DEBUG=0 in the project's environment
// variables to hide them again once the deployment is healthy.
$debug = getenv('APP_DEBUG') !== '0';
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$root = dirname(__DIR__);

/**
 * /?__diag=1 reports what this Lambda can actually see — PHP version, paths and
 * which project files made it into the bundle. The single most useful thing to
 * hit first when a deployment misbehaves.
 */
if (isset($_GET['__diag'])) {
    header('Content-Type: text/plain; charset=utf-8');

    echo "PHP version : " . PHP_VERSION . "\n";
    echo "root path   : $root\n";
    echo "cwd         : " . getcwd() . "\n";
    echo "temp dir    : " . sys_get_temp_dir() . "\n";
    echo "temp write  : " . (is_writable(sys_get_temp_dir()) ? 'yes' : 'NO') . "\n";
    echo "VERCEL env  : " . var_export(getenv('VERCEL'), true) . "\n";
    echo "memory limit: " . ini_get('memory_limit') . "\n\n";

    echo "Expected files:\n";
    $expected = [
        'index.php', 'includes/routes.php', 'includes/config.php',
        'includes/functions.php', 'includes/ads.php', 'includes/components.php',
        'includes/header.php', 'includes/footer.php',
        'config/site.json', 'config/ads.json', 'products/products.json',
        'category/view.php', 'products/view.php', 'cart/view.php',
        'checkout/full-name.php', 'confirm/thanks.php', '404.php',
    ];
    foreach ($expected as $f) {
        printf("  %-28s %s\n", $f, is_file("$root/$f") ? 'ok' : 'MISSING');
    }

    echo "\nRoot directory listing:\n";
    foreach (scandir($root) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $full = "$root/$entry";
        printf("  %-24s %s\n", $entry, is_dir($full) ? '<dir>' : filesize($full) . ' bytes');
    }

    $imgDir = "$root/assets/img";
    echo "\nassets/img  : " . (is_dir($imgDir) ? count(scandir($imgDir)) - 2 . ' files' : 'MISSING') . "\n";

    exit;
}

$routes = $root . '/includes/routes.php';
if (!is_file($routes)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit("includes/routes.php is not in the deployment bundle.\nVisit /?__diag=1 to see what is.\n");
}
require_once $routes;

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
require $root . '/404.php';
