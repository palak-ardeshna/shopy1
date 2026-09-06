<?php
/**
 * Core bootstrap: loads site + ads config, exposes helpers.
 */
define('ROOT_PATH', dirname(__DIR__));

/**
 * On a serverless host (Vercel) the deployment is read-only — only the system
 * temp dir accepts writes, and it is wiped when the instance recycles. We keep
 * the deployed JSON as read-only seed data and copy each file into the writable
 * area the first time something needs to change it.
 *
 * That makes the whole site work as a demo: orders save, the admin panel saves,
 * uploads work — the data just does not survive an instance recycle.
 */
define('IS_SERVERLESS', getenv('VERCEL') !== false || getenv('AWS_LAMBDA_FUNCTION_NAME') !== false);
define('WRITABLE_ROOT', IS_SERVERLESS ? rtrim(sys_get_temp_dir(), '/\\') . '/store' : ROOT_PATH);

/** Absolute path to write $relPath to, creating its directory. */
function writable_path($relPath) {
    $file = WRITABLE_ROOT . '/' . ltrim($relPath, '/');
    $dir  = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    return $file;
}

/**
 * Absolute path to read $relPath from: the writable copy when one exists,
 * otherwise the file shipped with the deployment.
 */
function readable_path($relPath) {
    $relPath  = ltrim($relPath, '/');
    $writable = WRITABLE_ROOT . '/' . $relPath;
    if (IS_SERVERLESS && is_file($writable)) return $writable;
    return ROOT_PATH . '/' . $relPath;
}

function load_json($relPath, $default = []) {
    $file = readable_path($relPath);
    if (!is_file($file)) return $default;
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : $default;
}

/** Write a JSON file atomically, into the writable area. */
function save_json($relPath, $data) {
    $file = writable_path($relPath);
    $tmp  = $file . '.tmp';
    $ok   = @file_put_contents(
        $tmp,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    if ($ok === false) return false;
    return @rename($tmp, $file);
}

/**
 * Append one record to a JSON array file under an exclusive lock, so two
 * simultaneous orders cannot overwrite each other.
 */
function append_json_record($relPath, $record) {
    $seed = load_json($relPath, []);      // writable copy if there is one, else the shipped file
    $file = writable_path($relPath);

    $fh = @fopen($file, 'c+');
    if (!$fh) return false;

    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);

    if (trim($raw) === '') {
        // First write on this instance — carry the shipped records over.
        $list = $seed;
    } else {
        $list = json_decode($raw, true);
        if (!is_array($list)) $list = [];
    }

    $list[] = $record;

    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);

    return true;
}

/**
 * Start the session with a save path that exists on this host.
 * Must be called instead of session_start() — the default save path is not
 * writable on serverless.
 */
function app_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    if (IS_SERVERLESS) {
        $path = rtrim(sys_get_temp_dir(), '/\\') . '/sessions';
        if (!is_dir($path)) @mkdir($path, 0777, true);
        session_save_path($path);
    }
    session_start();
}

$SITE = load_json('config/site.json', []);
$ADS  = load_json('config/ads.json', ['positions' => [], 'network' => []]);

// Sensible fallbacks so a half-filled config never breaks a page.
$SITE += [
    'site_name'                 => 'My Store',
    'tagline'                   => 'Market Place, E-Commerce, Buy new fashion',
    'description'               => 'Market place dealing in daily new arriving fashion for men and women.',
    'keywords'                  => 'shopping, fashion, affordable',
    'currency'                  => '₹',
    'email'                     => 'support@example.com',
    'phone'                     => '+91 90000 00000',
    'address'                   => 'India',
    'products_per_page_home'    => 10,
    'products_per_page_category'=> 4,
    'related_products_count'    => 8,
];

function site($key, $fallback = '') {
    global $SITE;
    return $SITE[$key] ?? $fallback;
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function money($amount) {
    return site('currency') . number_format((float)$amount, 2);
}
