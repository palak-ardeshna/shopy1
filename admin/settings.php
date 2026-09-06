<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

$settings = load_json('config/site.json', []);
$passMsg  = '';

$FIELDS = [
    'site_name'                  => ['Site name',              'text',     'Shown in the header, footer, page titles and emails.'],
    'tagline'                    => ['Tagline',                'text',     'Appended to the home page title.'],
    'description'                => ['Description',            'textarea', 'Used in the footer and as the default meta description.'],
    'keywords'                   => ['Meta keywords',          'text',     'Comma-separated.'],
    'currency'                   => ['Currency symbol',        'text',     'Prefixed to every price.'],
    'email'                      => ['Support email',          'email',    'Shown on the contact, FAQ and policy pages.'],
    'phone'                      => ['Support phone',          'text',     ''],
    'address'                    => ['Business address',       'text',     ''],
    'products_per_page_home'     => ['Products per page — home',     'number', ''],
    'products_per_page_category' => ['Products per page — category', 'number', 'The reference site uses 4, which spreads a catalogue over more ad-bearing pages.'],
    'related_products_count'     => ['Related products shown',       'number', ''],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_csrf();
    $op = $_POST['op'] ?? '';

    if ($op === 'settings') {
        foreach ($FIELDS as $key => [$label, $type, $hint]) {
            if (!isset($_POST[$key])) continue;
            $settings[$key] = $type === 'number' ? max(1, (int)$_POST[$key]) : trim($_POST[$key]);
        }
        admin_save_json('config/site.json', $settings);
        admin_flash('Settings saved.');
        header('Location: /admin/settings.php', true, 302);
        exit;
    }

    if ($op === 'password') {
        $cfg     = admin_config();
        $current = $_POST['current'] ?? '';
        $new     = $_POST['new'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if (!admin_verify_password($current)) {
            $passMsg = 'Your current password is not correct.';
        } elseif (strlen($new) < 8) {
            $passMsg = 'The new password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $passMsg = 'The new passwords do not match.';
        } else {
            $cfg['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
            $cfg['updated_at']    = date('c');
            admin_save_json('config/admin.json', $cfg);
            admin_flash('Password changed.');
            header('Location: /admin/settings.php', true, 302);
            exit;
        }
    }
}

admin_header('Settings');
admin_flash();
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

    <form method="post" class="lg:col-span-2 bg-white rounded-xl shadow p-6 space-y-5">
        <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
        <input type="hidden" name="op" value="settings">

        <h2 class="font-bold text-gray-900">Site details</h2>

        <?php foreach ($FIELDS as $key => [$label, $type, $hint]):
            $value = $settings[$key] ?? ''; ?>
        <div>
            <label for="<?= e($key) ?>" class="block text-sm font-semibold text-gray-800 mb-2"><?= e($label) ?></label>
            <?php if ($type === 'textarea'): ?>
            <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="3"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"><?= e($value) ?></textarea>
            <?php else: ?>
            <input type="<?= e($type) ?>" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($value) ?>"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <?php endif; ?>
            <?php if ($hint): ?><p class="text-xs text-gray-500 mt-1"><?= e($hint) ?></p><?php endif; ?>
        </div>
        <?php endforeach; ?>

        <button class="bg-indigo-600 text-white py-3 px-8 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">Save settings</button>
    </form>

    <div class="space-y-6">
        <form method="post" class="bg-white rounded-xl shadow p-6 space-y-4">
            <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
            <input type="hidden" name="op" value="password">

            <h2 class="font-bold text-gray-900">Change password</h2>

            <?php if (admin_env_password() !== null): ?>
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-900 rounded p-3 text-sm">
                The login password comes from the <code>ADMIN_PASSWORD</code> environment variable, so it
                cannot be changed here. Update it in your host's environment settings instead.
            </div>
            <?php endif; ?>

            <?php if ($passMsg): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-800 rounded p-3 text-sm"><?= e($passMsg) ?></div>
            <?php endif; ?>

            <div>
                <label class="block text-sm font-semibold text-gray-800 mb-2">Current password</label>
                <input type="password" name="current" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-800 mb-2">New password</label>
                <input type="password" name="new" required minlength="8"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-800 mb-2">Confirm new password</label>
                <input type="password" name="confirm" required minlength="8"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <button class="w-full bg-gray-900 text-white py-2 rounded-lg font-semibold hover:bg-gray-800 transition-colors">Change password</button>
        </form>

        <div class="bg-white rounded-xl shadow p-6 text-sm text-gray-600 space-y-2">
            <h2 class="font-bold text-gray-900 mb-2">Where things live</h2>
            <p><code class="bg-gray-100 px-1 rounded">config/site.json</code> — these settings</p>
            <p><code class="bg-gray-100 px-1 rounded">config/ads.json</code> — ad slots</p>
            <p><code class="bg-gray-100 px-1 rounded">products/products.json</code> — catalogue</p>
            <p><code class="bg-gray-100 px-1 rounded">data/orders.json</code> — orders</p>
            <p><code class="bg-gray-100 px-1 rounded">data/messages.json</code> — contact form</p>
            <p class="pt-2 text-xs text-gray-500">Back these files up before any bulk edit — there is no undo.</p>
        </div>
    </div>
</div>

<?php admin_footer(); ?>
