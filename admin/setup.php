<?php
require_once __DIR__ . '/auth.php';

// Once an admin password exists this screen is closed for good.
if (admin_is_setup()) {
    header('Location: /admin/login.php', true, 302);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm']  ?? '';

    if (strlen($pass) < 8) {
        $error = 'Choose a password of at least 8 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'The two passwords do not match.';
    } else {
        $saved = admin_save_json('config/admin.json', [
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'created_at'    => date('c'),
        ]);
        if ($saved) {
            $_SESSION['admin_ok'] = true;
            header('Location: /admin/', true, 302);
            exit;
        }
        $error = 'Could not write config/admin.json — check folder permissions.';
    }
}

admin_header('First-time setup');
?>
<div class="max-w-md">
    <p class="text-gray-600 mb-6">
        Set an admin password. It is stored as a hash in <code class="bg-gray-200 px-1 rounded">config/admin.json</code>,
        and this setup page locks itself once that file exists.
    </p>

    <?php if ($error): ?>
    <div class="bg-red-50 border-l-4 border-red-500 text-red-800 rounded p-4 mb-6"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="bg-white rounded-xl shadow p-6 space-y-4">
        <div>
            <label for="password" class="block text-sm font-semibold text-gray-800 mb-2">Password</label>
            <input type="password" id="password" name="password" required minlength="8" autofocus
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label for="confirm" class="block text-sm font-semibold text-gray-800 mb-2">Confirm password</label>
            <input type="password" id="confirm" name="confirm" required minlength="8"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
            Create admin account
        </button>
    </form>
</div>
<?php admin_footer(); ?>
