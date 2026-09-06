<?php
require_once dirname(__DIR__) . '/includes/functions.php';
app_session_start();

/**
 * Minimal single-user admin auth.
 *
 * The password lives in config/admin.json as a password_hash(), never in plain
 * text. On first run — no admin.json yet — the setup screen creates it.
 */

define('ADMIN_FILE', ROOT_PATH . '/config/admin.json');

function admin_config() {
    return load_json('config/admin.json', []);
}

/**
 * A password set in the ADMIN_PASSWORD environment variable wins over
 * config/admin.json. Serverless hosts wipe the writable area when an instance
 * recycles, which would otherwise send the demo back to the setup screen; an
 * env var survives that and needs no writable storage at all.
 */
function admin_env_password() {
    $pass = getenv('ADMIN_PASSWORD');
    return ($pass === false || $pass === '') ? null : $pass;
}

function admin_is_setup() {
    if (admin_env_password() !== null) return true;
    $cfg = admin_config();
    return !empty($cfg['password_hash']);
}

/** True when $candidate matches whichever password source is in force. */
function admin_verify_password($candidate) {
    $env = admin_env_password();
    if ($env !== null) {
        return hash_equals($env, (string)$candidate);
    }
    $cfg = admin_config();
    return password_verify((string)$candidate, $cfg['password_hash'] ?? '');
}

function admin_logged_in() {
    return !empty($_SESSION['admin_ok']);
}

function admin_logout() {
    unset($_SESSION['admin_ok']);
    session_destroy();
}

function admin_csrf() {
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['admin_csrf'];
}

function admin_check_csrf() {
    $token = $_POST['csrf'] ?? '';
    if (empty($_SESSION['admin_csrf']) || !hash_equals($_SESSION['admin_csrf'], $token)) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back and try again.');
    }
}

/**
 * Write a JSON file atomically. save_json() targets the writable area, which on
 * a serverless host is the temp dir rather than the read-only deployment.
 */
function admin_save_json($relPath, $data) {
    return save_json($relPath, $data);
}

/** Call at the top of every admin page. Redirects to login when not signed in. */
function admin_require_login() {
    if (!admin_is_setup()) {
        header('Location: /admin/setup.php', true, 302);
        exit;
    }
    if (!admin_logged_in()) {
        header('Location: /admin/login.php', true, 302);
        exit;
    }
}

/** Shared chrome for admin screens. */
function admin_header($title) {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> — Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 min-h-screen">
<?php if (admin_logged_in()): ?>
<nav class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4 flex flex-wrap items-center gap-x-6 gap-y-2 py-3">
        <a href="/admin/" class="font-bold text-lg mr-4"><?= e(site('site_name')) ?> <span class="text-gray-400 font-normal text-sm">admin</span></a>
        <?php
        $nav = [
            '/admin/'            => 'Dashboard',
            '/admin/products.php'=> 'Products',
            '/admin/ads.php'     => 'Ad Slots',
            '/admin/orders.php'  => 'Orders',
            '/admin/settings.php'=> 'Settings',
        ];
        $here = strtok($_SERVER['REQUEST_URI'], '?');
        foreach ($nav as $url => $label) {
            $active = ($here === $url) || ($url !== '/admin/' && str_starts_with($here, rtrim($url, '/')));
            printf('<a href="%s" class="text-sm %s">%s</a>', e($url),
                $active ? 'text-white font-semibold' : 'text-gray-300 hover:text-white', e($label));
        }
        ?>
        <div class="ml-auto flex items-center gap-4">
            <a href="/" target="_blank" class="text-sm text-gray-300 hover:text-white">View site &rarr;</a>
            <a href="/admin/login.php?logout=1" class="text-sm text-red-300 hover:text-red-200">Log out</a>
        </div>
    </div>
</nav>
<?php endif; ?>
<main class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6"><?= e($title) ?></h1>
    <?php
}

function admin_footer() {
    echo "</main>\n</body>\n</html>\n";
}

/** Flash message helpers. */
function admin_flash($msg = null, $type = 'success') {
    if ($msg !== null) {
        $_SESSION['admin_flash'] = ['msg' => $msg, 'type' => $type];
        return;
    }
    if (empty($_SESSION['admin_flash'])) return;
    $f = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
    $colors = $f['type'] === 'error'
        ? 'bg-red-50 border-red-500 text-red-800'
        : 'bg-green-50 border-green-500 text-green-800';
    printf('<div class="border-l-4 rounded p-4 mb-6 %s">%s</div>', $colors, e($f['msg']));
}
