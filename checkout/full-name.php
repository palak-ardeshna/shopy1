<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/components.php';
app_session_start();

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$qty     = isset($_GET['qty']) ? max(1, min(10, (int)$_GET['qty'])) : 1;
$product = find_product($id);

if (!$product) {
    header('Location: /', true, 302);
    exit;
}

// One-time token so the confirmation page only accepts posts that started here.
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$price      = (float)$product['price'];
$total      = $price * $qty;
$cart_count = $qty;

$page_title = 'Checkout — ' . site('site_name');

require dirname(__DIR__) . '/includes/header.php';
?>

<main>
    <section class="container mx-auto px-4 sm:px-6 md:px-12 py-8 md:py-12">

        <!-- Step indicator -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="flex items-center mb-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-600 text-white font-bold">1</div>
                <div class="flex-1 h-1 bg-indigo-600 mx-2"></div>
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-300 text-gray-600 font-bold">2</div>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-indigo-600 font-semibold">Delivery Details</span>
                <span class="text-gray-500">Confirmation</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Order summary -->
            <div class="lg:col-span-1 order-2 lg:order-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 lg:sticky lg:top-24">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Order Summary</h3>

                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 mb-4">
                        <div class="flex items-center space-x-3">
                            <img src="<?= e(product_images($product)[0]) ?>" alt="<?= e($product['title']) ?>"
                                 class="w-16 h-16 rounded-lg object-cover border border-gray-200">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900"><?= e($product['title']) ?></p>
                                <p class="text-xs text-gray-600 mt-1">Quantity: <?= (int)$qty ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 border-t border-gray-200 pt-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium text-gray-900"><?= e(money($total)) ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Shipping</span>
                            <span class="font-medium text-green-600">Free</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg p-3 mt-4">
                            <span>Total</span>
                            <span class="text-indigo-600"><?= e(money($total)) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="lg:col-span-2 order-1 lg:order-2">

                <?php ad_slot('checkout_above_form', 'my-4'); ?>

                <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Delivery Details</h2>
                    <p class="text-gray-600 text-sm mb-6">Enter your details to complete your order</p>

                    <form id="checkout-form" method="post" action="/confirm/thanks.php" novalidate>
                        <input type="hidden" name="csrf"       value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <input type="hidden" name="qty"        value="<?= (int)$qty ?>">

                        <!-- Personal information -->
                        <div class="mb-8">
                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-4 flex items-center">
                                <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Personal Information
                            </h3>

                            <div class="mb-4">
                                <label for="fullName" class="block text-sm font-semibold text-gray-800 mb-2">Full Name</label>
                                <input type="text" id="fullName" name="fullName" placeholder="John Doe" required autocomplete="name"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200">
                            </div>

                            <div class="mb-4">
                                <label for="email" class="block text-sm font-semibold text-gray-800 mb-2">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="john@example.com" required autocomplete="email"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200">
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-semibold text-gray-800 mb-2">Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="+91 98765 43210" required autocomplete="tel"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200">
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="mb-8">
                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-4 flex items-center">
                                <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Address Information
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="address" class="block text-sm font-semibold text-gray-800 mb-2">Street Address</label>
                                    <input type="text" id="address" name="address" placeholder="123 Main Street" required autocomplete="address-line1"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200">
                                </div>
                                <div>
                                    <label for="address1" class="block text-sm font-semibold text-gray-800 mb-2">Apt, Suite, etc. (Optional)</label>
                                    <input type="text" id="address1" name="address1" placeholder="Apartment 4B" autocomplete="address-line2"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label for="city" class="block text-sm font-semibold text-gray-800 mb-2">City</label>
                                    <input type="text" id="city" name="city" placeholder="Mumbai" required autocomplete="address-level2"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200">
                                </div>
                                <div>
                                    <label for="pincode" class="block text-sm font-semibold text-gray-800 mb-2">Pincode</label>
                                    <input type="text" id="pincode" name="pincode" placeholder="400001" required inputmode="numeric"
                                           pattern="[0-9]{6}" maxlength="6" autocomplete="postal-code"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200">
                                </div>
                                <div>
                                    <label for="state" class="block text-sm font-semibold text-gray-800 mb-2">State</label>
                                    <input type="text" id="state" name="state" placeholder="Maharashtra" required autocomplete="address-level1"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200">
                                </div>
                            </div>
                        </div>

                        <!-- Payment method -->
                        <div class="mb-8">
                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-4">Payment Method</h3>
                            <label class="flex items-center gap-3 border border-indigo-200 bg-indigo-50 rounded-lg p-4 cursor-pointer">
                                <input type="radio" name="payment" value="cod" checked class="text-indigo-600 focus:ring-indigo-500">
                                <span>
                                    <span class="block font-semibold text-gray-900">Cash on Delivery</span>
                                    <span class="block text-sm text-gray-600">Pay in cash when your order arrives</span>
                                </span>
                            </label>
                        </div>

                        <?php ad_slot('checkout_above_button', 'my-6'); ?>

                        <button type="submit"
                                class="w-full bg-gradient-to-r from-indigo-600 to-indigo-700 text-white py-4 px-4 rounded-lg font-bold text-lg
                                       hover:from-indigo-700 hover:to-indigo-800 transition-all duration-300 shadow-lg hover:shadow-xl
                                       transform hover:-translate-y-0.5 mb-4 flex items-center justify-center space-x-2">
                            <span>Place Order</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </button>

                        <?php ad_slot('checkout_below_button', 'my-4'); ?>
                    </form>

                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-indigo-500 rounded-lg p-4 shadow-sm mt-4">
                        <div class="flex items-start space-x-3">
                            <svg class="w-6 h-6 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Important Information</h4>
                                <p class="text-sm text-gray-700 leading-relaxed">
                                    Please verify that all information is accurate and complete. Your name, email and phone number
                                    are used for order communication and delivery confirmation. The address provided determines your
                                    delivery location &mdash; any errors may cause delays or failed deliveries.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('checkout-form');

    form.addEventListener('submit', function (ev) {
        var invalid = null;
        Array.prototype.forEach.call(form.querySelectorAll('[required]'), function (field) {
            var bad = !field.value.trim() || (field.type === 'email' && !/^\S+@\S+\.\S+$/.test(field.value));
            if (field.id === 'pincode' && !/^\d{6}$/.test(field.value.trim())) bad = true;
            field.classList.toggle('border-red-500', bad);
            if (bad && !invalid) invalid = field;
        });

        if (invalid) {
            ev.preventDefault();
            invalid.focus();
            invalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});
</script>

<?php
pixel_event('InitiateCheckout', [
    'content_ids' => [(int)$product['id']],
    'num_items'   => $qty,
    'value'       => $total,
    'currency'    => 'INR',
]);

require dirname(__DIR__) . '/includes/footer.php';
?>
