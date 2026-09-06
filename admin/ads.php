<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

$ads = load_json('config/ads.json', ['network' => [], 'positions' => []]);

/** Human labels for the slot positions, in the order they appear in the funnel. */
$POSITION_LABELS = [
    'global_top'                => ['Every page — top',            'Shows above the header on every page. Holds the AdSense loader, the top banner, and the interstitial + anchor units.'],
    'global_pixels'             => ['Tracking pixels',             'Meta, Snapchat and GA4 tags. These are not ads — they measure traffic and conversions.'],
    'home_below_grid'           => ['Home — below product grid',   'After the featured products on the home page.'],
    'category_below_grid'       => ['Category — below product grid','After the product grid on a category page.'],
    'product_above_add_to_cart' => ['Product — above Add to Cart', 'The highest-earning placement: directly above the Add to Cart button.'],
    'product_below_description' => ['Product — below description', 'After the product description text.'],
    'cart_above_checkout_btn'   => ['Cart — above Checkout button','Between the cart totals and Proceed to Checkout.'],
    'cart_below_checkout_btn'   => ['Cart — below Checkout button','Under the Proceed to Checkout button.'],
    'checkout_above_form'       => ['Checkout — above form',       'Above the Delivery Details card.'],
    'checkout_above_button'     => ['Checkout — above Place Order','Between the form fields and the Place Order button.'],
    'checkout_below_button'     => ['Checkout — below Place Order','Under the Place Order button.'],
    'thanks_below_continue'     => ['Thank you — below Continue',  'On the order confirmation page.'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_csrf();
    $op = $_POST['op'] ?? '';

    if ($op === 'network') {
        $ads['network']['adsense_client']   = trim($_POST['adsense_client'] ?? '');
        $ads['network']['gam_network_code'] = trim($_POST['gam_network_code'] ?? '');
        admin_save_json('config/ads.json', $ads);
        admin_flash('Network IDs saved.');

    } elseif ($op === 'toggle') {
        $pos = $_POST['position'] ?? '';
        $idx = (int)($_POST['index'] ?? -1);
        if (isset($ads['positions'][$pos][$idx])) {
            $ads['positions'][$pos][$idx]['enabled'] = empty($ads['positions'][$pos][$idx]['enabled']);
            admin_save_json('config/ads.json', $ads);
            admin_flash('Ad unit ' . ($ads['positions'][$pos][$idx]['enabled'] ? 'enabled' : 'disabled') . '.');
        }

    } elseif ($op === 'unit') {
        $pos = $_POST['position'] ?? '';
        $idx = (int)($_POST['index'] ?? -1);
        if (isset($ads['positions'][$pos][$idx])) {
            $unit = &$ads['positions'][$pos][$idx];

            if (isset($_POST['slot']))   $unit['slot'] = trim($_POST['slot']);
            if (isset($_POST['div']))    $unit['div']  = trim($_POST['div']);
            if (isset($_POST['id']))     $unit['id']   = trim($_POST['id']);

            if (isset($_POST['sizes'])) {
                // "300x250, 336x280, fluid" -> [[300,250],[336,280],"fluid"]
                $sizes = [];
                foreach (explode(',', $_POST['sizes']) as $chunk) {
                    $chunk = trim($chunk);
                    if ($chunk === '') continue;
                    if (preg_match('/^(\d+)\s*[xX]\s*(\d+)$/', $chunk, $m)) {
                        $sizes[] = [(int)$m[1], (int)$m[2]];
                    } else {
                        $sizes[] = strtolower($chunk);
                    }
                }
                if ($sizes) $unit['sizes'] = $sizes;
            }
            unset($unit);
            admin_save_json('config/ads.json', $ads);
            admin_flash('Ad unit updated.');
        }
    }

    header('Location: /admin/ads.php', true, 302);
    exit;
}

/** Render [[300,250],"fluid"] back into the "300x250, fluid" text form. */
function sizes_text($sizes) {
    $out = [];
    foreach ((array)$sizes as $s) {
        $out[] = is_array($s) ? ($s[0] . 'x' . $s[1]) : (string)$s;
    }
    return implode(', ', $out);
}

admin_header('Ad Slots');
admin_flash();
?>

<div class="bg-white rounded-xl shadow p-6 mb-8 max-w-3xl">
    <h2 class="font-bold text-gray-900 mb-1">Network IDs</h2>
    <p class="text-sm text-gray-600 mb-4">Every slot on the site is built from these two values.</p>
    <form method="post" class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
        <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
        <input type="hidden" name="op" value="network">
        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">AdSense publisher ID</label>
            <input type="text" name="adsense_client" value="<?= e($ads['network']['adsense_client'] ?? '') ?>"
                   placeholder="ca-pub-1234567890123456"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Ad Manager network code</label>
            <input type="text" name="gam_network_code" value="<?= e($ads['network']['gam_network_code'] ?? '') ?>"
                   placeholder="22822495633"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>
        <div class="sm:col-span-2">
            <button class="bg-indigo-600 text-white py-2 px-6 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">Save network IDs</button>
        </div>
    </form>
</div>

<div class="space-y-6">
<?php foreach ($ads['positions'] as $pos => $units):
    if (str_starts_with($pos, '_')) continue;
    [$label, $hint] = $POSITION_LABELS[$pos] ?? [$pos, ''];
    $activeCount = count(array_filter($units, fn($u) => !empty($u['enabled'])));
    ?>
    <section class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-baseline gap-x-3">
            <h2 class="font-bold text-gray-900"><?= e($label) ?></h2>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $activeCount ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' ?>">
                <?= (int)$activeCount ?> of <?= count($units) ?> active
            </span>
            <code class="text-xs text-gray-400 ml-auto"><?= e($pos) ?></code>
            <p class="w-full text-sm text-gray-500 mt-1"><?= e($hint) ?></p>
        </div>

        <div class="divide-y divide-gray-100">
        <?php foreach ($units as $i => $unit): $on = !empty($unit['enabled']); ?>
            <div class="px-6 py-4 flex flex-wrap items-center gap-4 <?= $on ? '' : 'opacity-60' ?>">

                <form method="post" class="flex-shrink-0">
                    <input type="hidden" name="csrf"     value="<?= e(admin_csrf()) ?>">
                    <input type="hidden" name="op"       value="toggle">
                    <input type="hidden" name="position" value="<?= e($pos) ?>">
                    <input type="hidden" name="index"    value="<?= (int)$i ?>">
                    <button type="submit" title="<?= $on ? 'Disable' : 'Enable' ?>"
                            class="w-11 h-6 rounded-full transition-colors <?= $on ? 'bg-green-500' : 'bg-gray-300' ?> relative">
                        <span class="absolute top-0.5 <?= $on ? 'left-6' : 'left-0.5' ?> w-5 h-5 bg-white rounded-full shadow transition-all"></span>
                    </button>
                </form>

                <div class="min-w-[8rem]">
                    <p class="text-xs uppercase tracking-wide text-gray-400">Type</p>
                    <p class="font-mono text-sm text-gray-800"><?= e($unit['type'] ?? '') ?></p>
                </div>

                <?php if (in_array($unit['type'] ?? '', ['gpt', 'gpt_interstitial', 'gpt_anchor'], true)): ?>
                <form method="post" class="flex flex-wrap items-end gap-3 flex-1">
                    <input type="hidden" name="csrf"     value="<?= e(admin_csrf()) ?>">
                    <input type="hidden" name="op"       value="unit">
                    <input type="hidden" name="position" value="<?= e($pos) ?>">
                    <input type="hidden" name="index"    value="<?= (int)$i ?>">

                    <div class="flex-1 min-w-[10rem]">
                        <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Ad unit name</label>
                        <input type="text" name="slot" value="<?= e($unit['slot'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <?php if (($unit['type'] ?? '') === 'gpt'): ?>
                    <div class="flex-1 min-w-[12rem]">
                        <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Sizes</label>
                        <input type="text" name="sizes" value="<?= e(sizes_text($unit['sizes'] ?? [])) ?>"
                               placeholder="300x250, 336x280, fluid"
                               class="w-full px-3 py-2 border border-gray-300 rounded font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div class="min-w-[9rem]">
                        <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Div ID</label>
                        <input type="text" name="div" value="<?= e($unit['div'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <?php endif; ?>

                    <button class="px-4 py-2 border border-gray-300 rounded text-sm font-medium hover:bg-gray-100">Update</button>
                </form>

                <?php elseif (in_array($unit['type'] ?? '', ['meta_pixel', 'snap_pixel', 'ga4'], true)): ?>
                <form method="post" class="flex items-end gap-3 flex-1">
                    <input type="hidden" name="csrf"     value="<?= e(admin_csrf()) ?>">
                    <input type="hidden" name="op"       value="unit">
                    <input type="hidden" name="position" value="<?= e($pos) ?>">
                    <input type="hidden" name="index"    value="<?= (int)$i ?>">
                    <div class="flex-1 min-w-[14rem]">
                        <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1">Pixel / measurement ID</label>
                        <input type="text" name="id" value="<?= e($unit['id'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <button class="px-4 py-2 border border-gray-300 rounded text-sm font-medium hover:bg-gray-100">Update</button>
                </form>

                <?php else: ?>
                <div class="flex-1 text-sm text-gray-500">
                    <?= e($unit['text'] ?? 'No editable fields.') ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
</div>

<p class="text-sm text-gray-500 mt-8 max-w-3xl">
    Slots are stored in <code class="bg-gray-200 px-1 rounded">config/ads.json</code>. To add a new position, add it there
    and call <code class="bg-gray-200 px-1 rounded">ad_slot('your_position')</code> from the template you want it on.
</p>

<?php admin_footer(); ?>
