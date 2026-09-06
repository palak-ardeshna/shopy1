<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/components.php';

$category = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($category === '') {
    header('Location: /', true, 302);
    exit;
}

$products = products_in_category($category);

if (!$products) {
    http_response_code(404);
    $page_title = 'Category not found — ' . site('site_name');
    require dirname(__DIR__) . '/includes/header.php';
    ?>
    <main class="container mx-auto px-4 py-24 text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Category not found</h1>
        <p class="text-gray-600 mb-8">We could not find any products in &ldquo;<?= e($category) ?>&rdquo;.</p>
        <a href="/" class="inline-block bg-indigo-600 text-white py-3 px-8 rounded-full font-semibold hover:bg-indigo-700 transition-colors">Back to Shop</a>
    </main>
    <?php
    require dirname(__DIR__) . '/includes/footer.php';
    exit;
}

// Normalise to the stored spelling so the heading and links stay consistent.
$category = $products[0]['category'];
$label    = category_label($category);
$result   = paginate($products, $page, (int)site('products_per_page_category'));

$page_title       = $label . ' — ' . site('site_name');
$page_description = 'Shop ' . $label . ' at ' . site('site_name') . '. ' . count($products) . ' styles, new arrivals daily.';
$page_image       = product_images($products[0])[0];

require dirname(__DIR__) . '/includes/header.php';
?>

<main>
    <section class="container mx-auto px-4 sm:px-6 md:px-12 py-10">
        <?php breadcrumbs([
            'Home'  => '/',
            $label  => null,
        ]); ?>

        <div class="text-center mb-10">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2"><?= e($label) ?></h1>
            <p class="text-gray-500">
                <?= (int)$result['total_items'] ?> products &middot;
                page <?= (int)$result['page'] ?> of <?= (int)$result['total_pages'] ?>
            </p>
        </div>

        <?php if ($result['items']): ?>
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            <?php foreach ($result['items'] as $p) product_card($p); ?>
        </div>
        <?php else: ?>
        <p class="text-center text-gray-500 py-10">No products found on this page.</p>
        <?php endif; ?>

        <?php pagination_nav($result['page'], $result['total_pages'], category_url($category)); ?>

        <?php ad_slot('category_below_grid', 'my-10'); ?>
    </section>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
