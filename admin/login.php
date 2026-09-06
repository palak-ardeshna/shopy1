<?php
require_once __DIR__ . '/auth.php';

if (isset($_GET['logout'])) {
    admin_logout();
    header('Location: /admin/login.php', true, 302);
    exit;
}

if (!admin_is_setup()) {
    header('Location: /admin/setup.php', true, 302);
    exit;
}

if (admin_logged_in()) {
    header('Location: /admin/', true, 302);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Slow down brute force without needing any extra storage.
    usleep(300000);
    if (admin_verify_password($_POST['password'] ?? '')) {
        session_regenerate_id(true);
        $_SESSION['admin_ok'] = true;
        header('Location: /admin/', true, 302);
        exit;
    }
    $error = 'Incorrect password.';
}

admin_header('Admin login');
?>
<div class="max-w-sm">
    <?php if ($error): ?>
    <div class="bg-red-50 border-l-4 border-red-500 text-red-800 rounded p-4 mb-6"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white rounded-xl shadow p-6 space-y-4">
        <div>
            <label for="password" class="block text-sm font-semibold text-gray-800 mb-2">Password</label>
            <input type="password" id="password" name="password" required autofocus
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
            Log in
        </button>
    </form>
</div>
<?php admin_footer(); ?>
