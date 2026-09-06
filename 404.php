<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/components.php';

http_response_code(404);
$page_title = 'Page not found — ' . site('site_name');

require __DIR__ . '/includes/header.php';

$suggestions = array_slice(all_products(), 0, 4);
?>
<main>
    <section class="container mx-auto px-4 sm:px-6 md:px-12 py-16 md:py-24 text-center">
        <p class="text-7xl font-bold text-indigo-600 mb-4">404</p>
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">This page has wandered off</h1>
        <p class="text-gray-600 mb-8 max-w-md mx-auto">
            The link may be out of date or the item may have sold out. Try one of these instead.
        </p>
        <a href="/" class="inline-block bg-indigo-600 text-white py-3 px-8 rounded-full font-semibold hover:bg-indigo-700 transition-colors duration-300 shadow-md">Back to Shop</a>
    </section>

    <?php if ($suggestions): ?>
    <section class="container mx-auto px-4 sm:px-6 md:px-12 pb-16">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-8">Popular right now</h2>
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
            <?php foreach ($suggestions as $p) product_card($p, 'w-full h-48 object-cover'); ?>
        </div>
    </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
