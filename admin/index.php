<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

$products = all_products();
$cats     = all_categories();
$orders   = load_json('data/orders.json', []);
$messages = load_json('data/messages.json', []);

$revenue = array_sum(array_column($orders, 'total'));
$today   = date('Y-m-d');
$todayOrders = array_filter($orders, function ($o) use ($today) {
    return str_starts_with($o['created_at'] ?? '', $today);
});

// Count how many ad units are switched on across every position.
$adsOn = 0;
foreach ($ADS['positions'] ?? [] as $pos => $units) {
    foreach ($units as $u) if (!empty($u['enabled'])) $adsOn++;
}

$stats = [
    ['Products',       count($products),                   '/admin/products.php'],
    ['Categories',     count($cats),                       '/admin/products.php'],
    ['Orders',         count($orders),                     '/admin/orders.php'],
    ['Orders today',   count($todayOrders),                '/admin/orders.php'],
    ['Revenue',        money($revenue),                    '/admin/orders.php'],
    ['Active ad units', $adsOn,                            '/admin/ads.php'],
];

admin_header('Dashboard');
admin_flash();
?>

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <?php foreach ($stats as [$label, $value, $url]): ?>
    <a href="<?= e($url) ?>" class="bg-white rounded-xl shadow p-5 hover:shadow-md transition-shadow">
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1"><?= e($label) ?></p>
        <p class="text-2xl font-bold text-gray-900"><?= e($value) ?></p>
    </a>
    <?php endforeach; ?>
</div>

<?php
$network = $ADS['network'] ?? [];
$warnings = [];
if (str_contains($network['adsense_client'] ?? '', '0000000000000000')) {
    $warnings[] = 'Your AdSense publisher ID is still the placeholder — set it in Ad Slots before going live.';
}
if (str_contains($network['gam_network_code'] ?? '', '00000000000')) {
    $warnings[] = 'Your Ad Manager network code is still the placeholder — set it in Ad Slots before going live.';
}
if (site('site_name') === 'My Store') {
    $warnings[] = 'Site name is still the default — update it in Settings.';
}
if ($warnings): ?>
<div class="bg-amber-50 border-l-4 border-amber-500 rounded p-4 mb-8">
    <p class="font-semibold text-amber-900 mb-2">Before you go live</p>
    <ul class="list-disc list-inside text-sm text-amber-800 space-y-1">
        <?php foreach ($warnings as $w): ?><li><?= e($w) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-gray-900">Recent orders</h2>
            <a href="/admin/orders.php" class="text-sm text-indigo-600 hover:underline">View all</a>
        </div>
        <?php $recent = array_slice(array_reverse($orders), 0, 6); ?>
        <?php if ($recent): ?>
        <table class="w-full text-sm">
            <tbody>
            <?php foreach ($recent as $o): ?>
                <tr class="border-b border-gray-100 last:border-0">
                    <td class="py-2 font-mono text-xs text-gray-500"><?= e($o['order_id'] ?? '') ?></td>
                    <td class="py-2 text-gray-800"><?= e($o['customer']['fullName'] ?? '') ?></td>
                    <td class="py-2 text-gray-600 truncate max-w-[12rem]"><?= e($o['title'] ?? '') ?></td>
                    <td class="py-2 text-right font-semibold"><?= e(money($o['total'] ?? 0)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="text-sm text-gray-500">No orders yet.</p>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="font-bold text-gray-900 mb-4">Products per category</h2>
        <?php if ($cats): $max = max($cats); ?>
        <div class="space-y-3">
            <?php foreach ($cats as $cat => $n): ?>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-700"><?= e(category_label($cat)) ?></span>
                    <span class="text-gray-500"><?= (int)$n ?></span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full" style="width: <?= (int)round($n / $max * 100) ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-sm text-gray-500">No products yet.</p>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow p-6 lg:col-span-2">
        <h2 class="font-bold text-gray-900 mb-4">Contact messages (<?= count($messages) ?>)</h2>
        <?php $recentMsgs = array_slice(array_reverse($messages), 0, 5); ?>
        <?php if ($recentMsgs): ?>
        <div class="space-y-4">
            <?php foreach ($recentMsgs as $m): ?>
            <div class="border-b border-gray-100 pb-3 last:border-0">
                <div class="flex flex-wrap justify-between gap-2 text-sm">
                    <span class="font-semibold text-gray-900"><?= e($m['name'] ?? '') ?>
                        <span class="font-normal text-gray-500">&lt;<?= e($m['email'] ?? '') ?>&gt;</span></span>
                    <span class="text-gray-400 text-xs"><?= e(substr($m['received_at'] ?? '', 0, 16)) ?></span>
                </div>
                <?php if (!empty($m['subject'])): ?>
                <p class="text-sm font-medium text-gray-700 mt-1"><?= e($m['subject']) ?></p>
                <?php endif; ?>
                <p class="text-sm text-gray-600 mt-1"><?= nl2br(e($m['message'] ?? '')) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-sm text-gray-500">No messages yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php admin_footer(); ?>
