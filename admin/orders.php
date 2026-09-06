<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

$orders = load_json('data/orders.json', []);

// CSV export before any output is sent.
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Order ID', 'Date', 'Product', 'Qty', 'Total', 'Payment',
                   'Name', 'Email', 'Phone', 'Address', 'Address 2', 'City', 'Pincode', 'State']);
    foreach ($orders as $o) {
        $c = $o['customer'] ?? [];
        fputcsv($out, [
            $o['order_id'] ?? '', $o['created_at'] ?? '', $o['title'] ?? '',
            $o['qty'] ?? '', $o['total'] ?? '', $o['payment'] ?? '',
            $c['fullName'] ?? '', $c['email'] ?? '', $c['phone'] ?? '',
            $c['address'] ?? '', $c['address1'] ?? '', $c['city'] ?? '',
            $c['pincode'] ?? '', $c['state'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_csrf();
    if (($_POST['op'] ?? '') === 'delete') {
        $id = $_POST['order_id'] ?? '';
        $orders = array_values(array_filter($orders, fn($o) => ($o['order_id'] ?? '') !== $id));
        admin_save_json('data/orders.json', $orders);
        admin_flash('Order ' . $id . ' deleted.');
    }
    header('Location: /admin/orders.php', true, 302);
    exit;
}

$revenue = array_sum(array_column($orders, 'total'));

admin_header('Orders (' . count($orders) . ')');
admin_flash();
?>

<div class="flex flex-wrap items-center gap-4 mb-6">
    <div class="bg-white rounded-xl shadow px-5 py-3">
        <p class="text-xs uppercase tracking-wide text-gray-500">Total revenue</p>
        <p class="text-xl font-bold text-gray-900"><?= e(money($revenue)) ?></p>
    </div>
    <?php if ($orders): ?>
    <a href="/admin/orders.php?export=csv"
       class="ml-auto bg-indigo-600 text-white py-2 px-5 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">Export CSV</a>
    <?php endif; ?>
</div>

<?php if (!$orders): ?>
<div class="bg-white rounded-xl shadow p-12 text-center text-gray-500">No orders yet.</div>
<?php else: ?>
<div class="space-y-4">
<?php foreach (array_reverse($orders) as $o): $c = $o['customer'] ?? []; ?>
    <details class="bg-white rounded-xl shadow overflow-hidden group">
        <summary class="px-6 py-4 flex flex-wrap items-center gap-x-6 gap-y-2 cursor-pointer list-none hover:bg-gray-50">
            <span class="font-mono text-sm text-gray-500"><?= e($o['order_id'] ?? '') ?></span>
            <span class="font-semibold text-gray-900"><?= e($c['fullName'] ?? '') ?></span>
            <span class="text-sm text-gray-600 truncate max-w-xs"><?= e($o['title'] ?? '') ?> &times; <?= (int)($o['qty'] ?? 1) ?></span>
            <span class="text-xs text-gray-400"><?= e(str_replace('T', ' ', substr($o['created_at'] ?? '', 0, 16))) ?></span>
            <span class="ml-auto font-bold text-gray-900"><?= e(money($o['total'] ?? 0)) ?></span>
            <svg class="w-4 h-4 text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </summary>

        <div class="px-6 pb-6 pt-2 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400 mb-2">Customer</p>
                <p class="font-medium text-gray-900"><?= e($c['fullName'] ?? '') ?></p>
                <p class="text-gray-600"><a href="mailto:<?= e($c['email'] ?? '') ?>" class="hover:underline"><?= e($c['email'] ?? '') ?></a></p>
                <p class="text-gray-600"><a href="tel:<?= e($c['phone'] ?? '') ?>" class="hover:underline"><?= e($c['phone'] ?? '') ?></a></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400 mb-2">Delivery address</p>
                <p class="text-gray-700">
                    <?= e($c['address'] ?? '') ?><?= !empty($c['address1']) ? ', ' . e($c['address1']) : '' ?><br>
                    <?= e($c['city'] ?? '') ?>, <?= e($c['state'] ?? '') ?> &mdash; <?= e($c['pincode'] ?? '') ?>
                </p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400 mb-2">Order</p>
                <p class="text-gray-700">
                    Product #<?= (int)($o['product_id'] ?? 0) ?> &middot; <?= e($o['title'] ?? '') ?><br>
                    <?= (int)($o['qty'] ?? 1) ?> &times; <?= e(money($o['unit_price'] ?? 0)) ?>
                    = <strong><?= e(money($o['total'] ?? 0)) ?></strong><br>
                    Payment: <?= e($o['payment'] ?? '') ?>
                </p>
            </div>
            <div class="sm:text-right sm:self-end">
                <form method="post" onsubmit="return confirm('Delete this order permanently?');">
                    <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
                    <input type="hidden" name="op" value="delete">
                    <input type="hidden" name="order_id" value="<?= e($o['order_id'] ?? '') ?>">
                    <button class="text-red-600 hover:underline text-sm">Delete order</button>
                </form>
            </div>
        </div>
    </details>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php admin_footer(); ?>
