<?php
require_once dirname(__DIR__) . '/includes/functions.php';

$page_title       = 'FAQ — ' . site('site_name');
$page_description = 'Answers to common questions about ordering, delivery, payment and returns at ' . site('site_name') . '.';
$heading          = 'Frequently Asked Questions';
$subheading       = 'Ordering, delivery, payment and returns — answered.';

$FAQS = [
    'How do I place an order?' =>
        'Open the product you want, choose a quantity, tap Add to Cart, then Proceed to Checkout. Fill in your delivery details and place the order. You will see an order number on the confirmation screen — keep it for reference.',

    'What payment methods do you accept?' =>
        'We currently offer Cash on Delivery (COD) on every order. You pay the delivery agent in cash when your parcel arrives, so nothing leaves your pocket before you have the item.',

    'How long does delivery take?' =>
        'Most orders are dispatched within 24–48 hours and reach you in 3–5 business days. Remote pincodes can take up to 7 business days. You will get an update by email or SMS once your parcel ships.',

    'Do you charge for shipping?' =>
        'No. Shipping is free on all orders, with no minimum order value.',

    'Can I change or cancel my order?' =>
        'Yes, as long as the order has not shipped. Email us at %EMAIL% with your order number as soon as possible and we will cancel or amend it. Once a parcel is with the courier it cannot be recalled.',

    'What is your return policy?' =>
        'You can request a return within 7 days of delivery if the item is unused, unwashed and still has its original tags and packaging. Email %EMAIL% with your order number and a photo of the item to start a return.',

    'How long do refunds take?' =>
        'Once your returned item reaches us and passes a quick quality check, refunds are processed within 5–7 business days to your original payment method or bank account.',

    'What if I receive a damaged or wrong item?' =>
        'We are sorry — that should not happen. Email %EMAIL% within 48 hours of delivery with your order number and photos of the item and packaging. We will arrange a free replacement or a full refund.',

    'How do I choose the right size?' =>
        'Each product description lists the fabric and fit. If you are between sizes we generally suggest going one size up for ethnic wear. Still unsure? Message us before ordering and we will help.',

    'How can I contact customer support?' =>
        'Email %EMAIL%, call %PHONE% during business hours, or use the form on our contact page. We reply within one business day.',
];

$content_body = function () use ($FAQS) {
    foreach ($FAQS as $q => $a) {
        $a = str_replace(['%EMAIL%', '%PHONE%'], [site('email'), site('phone')], $a);
        ?>
        <details class="border-b border-gray-200 py-4 group" <?= $q === array_key_first($FAQS) ? 'open' : '' ?>>
            <summary class="flex items-center justify-between cursor-pointer list-none font-semibold text-gray-900">
                <span><?= e($q) ?></span>
                <svg class="w-5 h-5 text-indigo-600 flex-shrink-0 ml-4 transition-transform group-open:rotate-180"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </summary>
            <p class="mt-3"><?= e($a) ?></p>
        </details>
        <?php
    }
    ?>
    <p class="mt-8 text-sm text-gray-500">
        Still stuck? <a href="/pages/contact-us">Send us a message</a> and a human will get back to you.
    </p>

    <script type="application/ld+json">
    <?= json_encode([
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(function ($q, $a) {
            return [
                '@type'          => 'Question',
                'name'           => $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => str_replace(['%EMAIL%', '%PHONE%'], [site('email'), site('phone')], $a),
                ],
            ];
        }, array_keys($FAQS), array_values($FAQS)),
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
    <?php
};

require __DIR__ . '/_layout.php';
