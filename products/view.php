<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/components.php';

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = find_product($id);

if (!$product) {
    http_response_code(404);
    $page_title = 'Product not found — ' . site('site_name');
    require dirname(__DIR__) . '/includes/header.php';
    ?>
    <main class="container mx-auto px-4 py-24 text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Product not found</h1>
        <p class="text-gray-600 mb-8">This item may have sold out or been removed.</p>
        <a href="/" class="inline-block bg-indigo-600 text-white py-3 px-8 rounded-full font-semibold hover:bg-indigo-700 transition-colors">Back to Shop</a>
    </main>
    <?php
    require dirname(__DIR__) . '/includes/footer.php';
    exit;
}

// Canonicalise /products/<id>/<slug> so every product has exactly one URL.
$canonical = product_url($product);
$path      = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
if ($path !== $canonical && $path !== '/products/view.php') {
    header('Location: ' . $canonical, true, 301);
    exit;
}

$images  = product_images($product);
$label   = category_label($product['category'] ?? '');
$related = related_products($product, (int)site('related_products_count'));

$page_title       = $product['title'] . ' — ' . site('site_name');
$page_description = mb_substr(trim(preg_replace('/\s+/', ' ', $product['description'] ?? '')), 0, 160);
$page_image       = $images[0];

require dirname(__DIR__) . '/includes/header.php';
?>

<main>
    <section class="container mx-auto px-4 sm:px-6 md:px-12 py-8 md:py-12">
        <?php breadcrumbs([
            'Home'            => '/',
            $label            => category_url($product['category']),
            $product['title'] => null,
        ]); ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-16">

            <!-- Gallery -->
            <div>
                <div id="product-slider" class="rounded-xl overflow-hidden shadow-lg relative bg-white">
                    <?php foreach ($images as $i => $img): ?>
                    <img src="<?= e($img) ?>" alt="<?= e($product['title']) ?>"
                         <?= $i === 0 ? '' : 'loading="lazy"' ?>
                         class="product-slide w-full h-auto object-cover<?= $i === 0 ? '' : ' hidden' ?>">
                    <?php endforeach; ?>

                    <?php if (count($images) > 1): ?>
                    <button id="prev-slide" aria-label="Previous image"
                            class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/80 text-gray-800 w-10 h-10 rounded-full hover:bg-white shadow transition text-2xl leading-none">&lsaquo;</button>
                    <button id="next-slide" aria-label="Next image"
                            class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/80 text-gray-800 w-10 h-10 rounded-full hover:bg-white shadow transition text-2xl leading-none">&rsaquo;</button>
                    <?php endif; ?>
                </div>

                <?php if (count($images) > 1): ?>
                <div class="flex gap-2 mt-4 overflow-x-auto custom-scroll pb-1">
                    <?php foreach ($images as $i => $img): ?>
                    <button type="button" data-thumb="<?= $i ?>"
                            class="thumb flex-shrink-0 rounded-lg overflow-hidden border-2 <?= $i === 0 ? 'border-indigo-600' : 'border-transparent' ?>">
                        <img src="<?= e($img) ?>" alt="" loading="lazy" class="w-16 h-16 object-cover">
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Details -->
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
                <h1 class="text-2xl md:text-4xl font-bold text-gray-800 mb-2"><?= e($product['title']) ?></h1>
                <p class="text-2xl font-semibold text-indigo-600 mb-1"><?= e(money($product['price'])) ?></p>
                <p class="text-sm text-green-600 font-medium mb-6">Free delivery &middot; Cash on delivery available</p>

                <div class="flex flex-col items-center space-y-4 mb-8">
                    <div class="flex items-center space-x-2">
                        <button id="decrease-quantity" aria-label="Decrease quantity"
                                class="bg-gray-200 text-gray-700 py-2 px-4 rounded-full hover:bg-gray-300 transition-colors duration-300">&minus;</button>
                        <input type="text" id="quantity" value="1" readonly
                               class="w-12 text-center text-lg font-semibold bg-white border border-gray-300 rounded-md py-2">
                        <button id="increase-quantity" aria-label="Increase quantity"
                                class="bg-gray-200 text-gray-700 py-2 px-4 rounded-full hover:bg-gray-300 transition-colors duration-300">+</button>
                    </div>

                    <?php ad_slot('product_above_add_to_cart', 'my-4 w-full'); ?>

                    <a id="add-to-cart" href="/cart/view.php?id=<?= (int)$product['id'] ?>&qty=1"
                       class="w-full bg-indigo-600 text-white py-3 px-8 rounded-full font-semibold hover:bg-indigo-700 transition-colors duration-300 shadow-md inline-block text-center">
                        Add to Cart
                    </a>
                </div>

                <div class="text-gray-700 border-t border-gray-200 pt-6">
                    <p class="font-bold mb-2">Product Information:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li><span class="font-medium">Category:</span>
                            <a href="<?= e(category_url($product['category'])) ?>" class="text-indigo-600 hover:underline"><?= e($label) ?></a></li>
                        <li><span class="font-medium">Price:</span> <?= e(money($product['price'])) ?></li>
                        <li><span class="font-medium">Product ID:</span> #<?= str_pad((string)$product['id'], 3, '0', STR_PAD_LEFT) ?></li>
                    </ul>
                </div>

                <div class="text-gray-600 leading-relaxed mt-6 space-y-4">
                    <?php foreach (preg_split('/\R{2,}/', trim($product['description'] ?? '')) as $para): ?>
                        <?php if (trim($para) !== ''): ?>
                        <p><?= nl2br(e(trim($para))) ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <?php ad_slot('product_below_description', 'my-6'); ?>
            </div>
        </div>
    </section>

    <?php if ($related): ?>
    <section class="container mx-auto px-4 sm:px-6 md:px-12 py-10">
        <h2 class="text-2xl md:text-4xl font-bold text-center text-gray-800 mb-10">You Might Also Like</h2>
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            <?php foreach ($related as $r) product_card($r, 'w-full h-48 object-cover'); ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Gallery ---
    var slides = Array.prototype.slice.call(document.querySelectorAll('.product-slide'));
    var thumbs = Array.prototype.slice.call(document.querySelectorAll('.thumb'));
    var index  = 0;

    function show(i) {
        if (!slides.length) return;
        index = (i + slides.length) % slides.length;
        slides.forEach(function (s, n) { s.classList.toggle('hidden', n !== index); });
        thumbs.forEach(function (t, n) {
            t.classList.toggle('border-indigo-600', n === index);
            t.classList.toggle('border-transparent', n !== index);
        });
    }

    var prev = document.getElementById('prev-slide');
    var next = document.getElementById('next-slide');
    if (prev) prev.addEventListener('click', function () { show(index - 1); });
    if (next) next.addEventListener('click', function () { show(index + 1); });
    thumbs.forEach(function (t) {
        t.addEventListener('click', function () { show(parseInt(t.dataset.thumb, 10)); });
    });

    // --- Quantity, kept in sync with the Add to Cart link ---
    var qtyInput = document.getElementById('quantity');
    var addLink  = document.getElementById('add-to-cart');
    var productId = <?= (int)$product['id'] ?>;

    function setQty(q) {
        q = Math.min(Math.max(1, q), 10);
        qtyInput.value = q;
        addLink.href = '/cart/view.php?id=' + productId + '&qty=' + q;
    }

    document.getElementById('increase-quantity').addEventListener('click', function () {
        setQty(parseInt(qtyInput.value, 10) + 1);
    });
    document.getElementById('decrease-quantity').addEventListener('click', function () {
        setQty(parseInt(qtyInput.value, 10) - 1);
    });

    addLink.addEventListener('click', function () {
        if (window.fbq)    fbq('track', 'AddToCart', { content_ids: [productId], value: <?= (float)$product['price'] ?>, currency: 'INR' });
        if (window.snaptr) snaptr('track', 'ADD_CART', { item_ids: [String(productId)], price: <?= (float)$product['price'] ?>, currency: 'INR' });
    });
});
</script>

<?php
pixel_event('ViewContent', [
    'content_ids'  => [(int)$product['id']],
    'content_name' => $product['title'],
    'content_type' => 'product',
    'value'        => (float)$product['price'],
    'currency'     => 'INR',
]);
?>

<script type="application/ld+json">
<?= json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'Product',
    'name'        => $product['title'],
    'image'       => array_values($images),
    'description' => $page_description,
    'sku'         => (string)$product['id'],
    'category'    => $label,
    'offers'      => [
        '@type'         => 'Offer',
        'price'         => (string)$product['price'],
        'priceCurrency' => 'INR',
        'availability'  => 'https://schema.org/InStock',
    ],
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
