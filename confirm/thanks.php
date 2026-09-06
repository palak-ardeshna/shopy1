<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/components.php';
app_session_start();

/**
 * Order confirmation. Accepts only a POST that carries the token issued by the
 * checkout page, then appends the order to data/orders.json.
 */

$order = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        $errors[] = 'Your session expired. Please start the checkout again.';
    }

    $product = find_product((int)($_POST['product_id'] ?? 0));
    if (!$product) {
        $errors[] = 'The product in your order is no longer available.';
    }

    $fields = [
        'fullName' => trim($_POST['fullName'] ?? ''),
        'email'    => trim($_POST['email'] ?? ''),
        'phone'    => trim($_POST['phone'] ?? ''),
        'address'  => trim($_POST['address'] ?? ''),
        'address1' => trim($_POST['address1'] ?? ''),
        'city'     => trim($_POST['city'] ?? ''),
        'pincode'  => trim($_POST['pincode'] ?? ''),
        'state'    => trim($_POST['state'] ?? ''),
    ];

    foreach (['fullName', 'email', 'phone', 'address', 'city', 'pincode', 'state'] as $required) {
        if ($fields[$required] === '') {
            $errors[] = 'Please fill in every required field.';
            break;
        }
    }
    if ($fields['email'] !== '' && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'That email address does not look valid.';
    }

    if (!$errors) {
        $qty   = max(1, min(10, (int)($_POST['qty'] ?? 1)));
        $total = (float)$product['price'] * $qty;

        $order = [
            'order_id'   => 'ORD' . date('ymd') . strtoupper(bin2hex(random_bytes(3))),
            'created_at' => date('c'),
            'product_id' => (int)$product['id'],
            'title'      => $product['title'],
            'qty'        => $qty,
            'unit_price' => (float)$product['price'],
            'total'      => $total,
            'payment'    => ($_POST['payment'] ?? 'cod') === 'cod' ? 'Cash on Delivery' : 'Online',
            'customer'   => $fields,
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
        ];

        append_json_record('data/orders.json', $order);

        // Burn the token so a refresh cannot record the same order twice.
        unset($_SESSION['csrf']);
        $_SESSION['last_order'] = $order;
    }
} elseif (!empty($_SESSION['last_order'])) {
    // Refresh or back-navigation after a successful order: show it again, don't re-save.
    $order = $_SESSION['last_order'];
}

if (!$order && !$errors) {
    header('Location: /', true, 302);
    exit;
}

$page_title = $order ? 'Order Confirmed — ' . site('site_name') : 'Checkout Problem — ' . site('site_name');

require dirname(__DIR__) . '/includes/header.php';
?>

<main>
    <section class="container mx-auto px-4 sm:px-6 md:px-12 py-16 md:py-24 text-center">
        <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-lg p-8 md:p-12">

        <?php if ($errors): ?>
            <div class="mx-auto mb-6 w-20 h-20 rounded-full bg-red-50 flex items-center justify-center">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4">We could not place your order</h1>
            <ul class="text-gray-600 mb-8 space-y-1">
                <?php foreach (array_unique($errors) as $err): ?>
                <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="/" class="inline-block bg-indigo-600 text-white py-3 px-8 rounded-full font-semibold hover:bg-indigo-700 transition-colors duration-300 shadow-md">Back to Shop</a>

        <?php else: ?>
            <div class="mx-auto mb-6 md:mb-8" style="width:100px;height:100px;">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                    <circle class="checkmark-circle" cx="65.1" cy="65.1" r="62.1" fill="none"/>
                    <polyline class="checkmark-tick" points="100.2,40.2 51.5,88.8 29.8,67.5" fill="none"/>
                </svg>
            </div>

            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Thank You for Your Order!</h1>
            <p class="text-lg md:text-xl text-gray-600 leading-relaxed mb-6">
                Your purchase has been confirmed. We&rsquo;ve received your order and are preparing it for shipment.
            </p>

            <div class="bg-gray-50 rounded-xl p-6 text-left mb-6">
                <div class="flex justify-between items-center pb-3 mb-3 border-b border-gray-200">
                    <span class="text-sm text-gray-500">Order number</span>
                    <span class="font-mono font-semibold text-gray-900"><?= e($order['order_id']) ?></span>
                </div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600"><?= e($order['title']) ?> &times; <?= (int)$order['qty'] ?></span>
                    <span class="font-medium text-gray-900"><?= e(money($order['total'])) ?></span>
                </div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600">Payment</span>
                    <span class="font-medium text-gray-900"><?= e($order['payment']) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Delivering to</span>
                    <span class="font-medium text-gray-900 text-right">
                        <?= e($order['customer']['fullName']) ?><br>
                        <span class="font-normal text-gray-600"><?= e($order['customer']['city']) ?>, <?= e($order['customer']['state']) ?> <?= e($order['customer']['pincode']) ?></span>
                    </span>
                </div>
            </div>

            <p class="text-md text-gray-500">
                We&rsquo;ll let you know when it&rsquo;s on its way. Expect delivery within <b>3&ndash;5 business days</b>.
            </p>

            <a href="/" class="inline-block mt-8 bg-indigo-600 text-white py-3 px-8 rounded-full font-semibold hover:bg-indigo-700 transition-colors duration-300 shadow-md">Continue Shopping</a>

            <?php ad_slot('thanks_below_continue', 'mt-8'); ?>
        <?php endif; ?>

        </div>
    </section>
</main>

<?php
if ($order && !$errors) {
    pixel_event('Purchase', [
        'content_ids' => [(int)$order['product_id']],
        'num_items'   => (int)$order['qty'],
        'value'       => (float)$order['total'],
        'currency'    => 'INR',
        'order_id'    => $order['order_id'],
    ]);
}

require dirname(__DIR__) . '/includes/footer.php';
?>
