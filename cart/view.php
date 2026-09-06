<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/components.php';

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$qty     = isset($_GET['qty']) ? max(1, min(10, (int)$_GET['qty'])) : 1;
$product = find_product($id);

if (!$product) {
    $page_title = 'Your Cart — ' . site('site_name');
    require dirname(__DIR__) . '/includes/header.php';
    ?>
    <main class="container mx-auto px-4 py-24 text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Your cart is empty</h1>
        <p class="text-gray-600 mb-8">Browse our latest arrivals and add something you love.</p>
        <a href="/" class="inline-block bg-indigo-600 text-white py-3 px-8 rounded-full font-semibold hover:bg-indigo-700 transition-colors">Start Shopping</a>
    </main>
    <?php
    require dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$price       = (float)$product['price'];
$discount    = 0.00;
$cart_count  = $qty;
$page_title  = 'Your Cart — ' . site('site_name');

require dirname(__DIR__) . '/includes/header.php';
?>

<main>
    <section class="container mx-auto px-4 sm:px-6 md:px-12 py-10 md:py-16">
        <h1 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-8">Your Cart</h1>

        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 mb-8">
                <div class="flex flex-col md:flex-row items-center justify-between pb-6 border-b border-gray-200 gap-4">
                    <div class="flex items-center space-x-4">
                        <img src="<?= e(product_images($product)[0]) ?>" alt="<?= e($product['title']) ?>"
                             class="w-20 h-20 rounded-md object-cover">
                        <div>
                            <a href="<?= e(product_url($product)) ?>"
                               class="text-lg md:text-xl font-semibold text-gray-800 hover:text-indigo-600"><?= e($product['title']) ?></a>
                            <p class="text-gray-600 text-sm">Product ID: #<?= str_pad((string)$product['id'], 3, '0', STR_PAD_LEFT) ?></p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div class="text-lg font-bold text-gray-900"><?= e(money($price)) ?></div>
                        <div class="flex items-center space-x-2">
                            <button id="decrease-quantity-cart" aria-label="Decrease quantity"
                                    class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full hover:bg-gray-300 transition-colors duration-300">&minus;</button>
                            <span id="quantity-cart" class="text-lg font-semibold w-6 text-center"><?= (int)$qty ?></span>
                            <button id="increase-quantity-cart" aria-label="Increase quantity"
                                    class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full hover:bg-gray-300 transition-colors duration-300">+</button>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600 font-medium">Subtotal</span>
                        <span class="font-semibold text-gray-800" id="subtotal"><?= e(money($price * $qty)) ?></span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600 font-medium">Shipping</span>
                        <span class="font-semibold text-green-600">Free</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600 font-medium">Discount</span>
                        <span class="font-semibold text-red-500" id="discount">-<?= e(money($discount)) ?></span>
                    </div>
                    <div class="flex justify-between items-center border-t border-gray-200 pt-4 mt-4">
                        <span class="text-xl font-bold text-gray-800">Total</span>
                        <span class="text-xl font-bold text-gray-900" id="total"><?= e(money($price * $qty - $discount)) ?></span>
                    </div>
                </div>
            </div>

            <?php ad_slot('cart_above_checkout_btn', 'my-6'); ?>

            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="/" class="w-full sm:w-auto text-center bg-white border border-gray-300 text-gray-700 py-3 px-8 rounded-full font-semibold hover:bg-gray-100 transition-colors duration-300">Continue Shopping</a>
                <a id="checkout-link" href="/checkout/full-name.php?id=<?= (int)$product['id'] ?>&qty=<?= (int)$qty ?>"
                   class="w-full sm:w-auto text-center bg-indigo-600 text-white py-3 px-8 rounded-full font-semibold hover:bg-indigo-700 transition-colors duration-300 shadow-md">Proceed to Checkout</a>
            </div>

            <?php ad_slot('cart_below_checkout_btn', 'my-6'); ?>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var price     = <?= json_encode($price) ?>;
    var discount  = <?= json_encode($discount) ?>;
    var currency  = <?= json_encode(site('currency')) ?>;
    var productId = <?= (int)$product['id'] ?>;

    var qtyEl      = document.getElementById('quantity-cart');
    var subtotalEl = document.getElementById('subtotal');
    var totalEl    = document.getElementById('total');
    var countEl    = document.getElementById('cart-count');
    var linkEl     = document.getElementById('checkout-link');

    function fmt(n) {
        return currency + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function update(qty) {
        qtyEl.textContent   = qty;
        subtotalEl.textContent = fmt(price * qty);
        totalEl.textContent    = fmt(price * qty - discount);
        if (countEl) countEl.textContent = qty;
        linkEl.href = '/checkout/full-name.php?id=' + productId + '&qty=' + qty;
    }

    document.getElementById('increase-quantity-cart').addEventListener('click', function () {
        update(Math.min(10, parseInt(qtyEl.textContent, 10) + 1));
    });
    document.getElementById('decrease-quantity-cart').addEventListener('click', function () {
        update(Math.max(1, parseInt(qtyEl.textContent, 10) - 1));
    });

    linkEl.addEventListener('click', function () {
        var qty = parseInt(qtyEl.textContent, 10);
        if (window.fbq)    fbq('track', 'InitiateCheckout', { content_ids: [productId], value: price * qty, currency: 'INR' });
        if (window.snaptr) snaptr('track', 'START_CHECKOUT', { item_ids: [String(productId)], price: price * qty, currency: 'INR' });
    });
});
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
