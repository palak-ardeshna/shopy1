<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/components.php';

$products = all_products();
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$result   = paginate($products, $page, (int)site('products_per_page_home'));

$page_title       = site('site_name') . ' — ' . site('tagline');
$page_description = site('description');

require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="container mx-auto px-4 sm:px-6 md:px-12 pt-10 pb-6">
        <div class="bg-gradient-to-r from-indigo-600 to-blue-600 rounded-2xl px-6 py-10 md:px-12 md:py-14 text-white shadow-lg">
            <h1 class="text-3xl md:text-5xl font-bold mb-3">New Arrivals, Every Day</h1>
            <p class="text-indigo-100 text-base md:text-lg max-w-2xl">
                <?= e(site('description')) ?>
            </p>
        </div>
    </section>

    <?php $categories = all_categories(); if ($categories): ?>
    <section class="container mx-auto px-4 sm:px-6 md:px-12 py-6">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6">Shop by Category</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($categories as $cat => $count):
                $first = products_in_category($cat)[0] ?? null; ?>
            <a href="<?= e(category_url($cat)) ?>"
               class="product-card group block bg-white rounded-xl shadow-md overflow-hidden">
                <?php if ($first): ?>
                <div class="overflow-hidden">
                    <img src="<?= e(product_images($first)[0]) ?>" alt="<?= e(category_label($cat)) ?>" loading="lazy"
                         class="w-full h-32 sm:h-40 object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <?php endif; ?>
                <div class="p-3 text-center">
                    <h3 class="font-semibold text-gray-800 text-sm sm:text-base"><?= e(category_label($cat)) ?></h3>
                    <p class="text-xs text-gray-500 mt-1"><?= (int)$count ?> items</p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="container mx-auto px-4 sm:px-6 md:px-12 py-10">
        <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-12">Featured Products</h2>

        <?php if ($result['items']): ?>
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            <?php foreach ($result['items'] as $p) product_card($p); ?>
        </div>
        <?php else: ?>
        <p class="text-center text-gray-500 py-10">No products yet. Add some from the admin panel.</p>
        <?php endif; ?>

        <?php pagination_nav($result['page'], $result['total_pages'], '/page', '/'); ?>

        <?php ad_slot('home_below_grid', 'my-10'); ?>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
