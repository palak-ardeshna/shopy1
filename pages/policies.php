<?php
require_once dirname(__DIR__) . '/includes/functions.php';

$page_title       = 'Policies — ' . site('site_name');
$page_description = 'Privacy policy, shipping policy, returns and refunds, and terms of service for ' . site('site_name') . '.';
$heading          = 'Our Policies';
$subheading       = 'Privacy, shipping, returns and terms — in plain language.';

$content_body = function () {
    $name  = site('site_name');
    $email = site('email');
    ?>
    <nav class="not-prose flex flex-wrap gap-2 mb-8">
        <a href="#privacy"  class="text-sm bg-gray-100 hover:bg-gray-200 rounded-full px-4 py-2 no-underline text-gray-700">Privacy</a>
        <a href="#shipping" class="text-sm bg-gray-100 hover:bg-gray-200 rounded-full px-4 py-2 no-underline text-gray-700">Shipping</a>
        <a href="#returns"  class="text-sm bg-gray-100 hover:bg-gray-200 rounded-full px-4 py-2 no-underline text-gray-700">Returns &amp; Refunds</a>
        <a href="#terms"    class="text-sm bg-gray-100 hover:bg-gray-200 rounded-full px-4 py-2 no-underline text-gray-700">Terms</a>
    </nav>

    <h2 id="privacy">Privacy Policy</h2>
    <p>
        <?= e($name) ?> collects only what it needs to fulfil your order: your name, email address, phone number and
        delivery address. We use these to process, pack, ship and support your purchase — nothing else.
    </p>
    <p>
        We share your delivery details with the courier handling your parcel. We do not sell your personal data.
    </p>
    <p>
        This site uses cookies and third-party advertising technology (including Google AdSense and Google Ad Manager)
        to serve ads. Third-party vendors, including Google, use cookies to serve ads based on your prior visits to this
        and other websites. You can opt out of personalised advertising at
        <a href="https://www.google.com/settings/ads" rel="nofollow noopener" target="_blank">Google Ads Settings</a>,
        or opt out of third-party vendor cookies at
        <a href="https://www.aboutads.info" rel="nofollow noopener" target="_blank">aboutads.info</a>.
        We may also use analytics and conversion pixels to measure the performance of our advertising.
    </p>
    <p>
        To request a copy of your data or ask us to delete it, email <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>.
    </p>

    <h2 id="shipping">Shipping Policy</h2>
    <ul>
        <li>Shipping is <strong>free</strong> on every order, with no minimum value.</li>
        <li>Orders are dispatched within 24&ndash;48 hours of being placed.</li>
        <li>Standard delivery takes <strong>3&ndash;5 business days</strong>; remote pincodes may take up to 7.</li>
        <li>We ship across India. We do not currently ship internationally.</li>
        <li>Tracking details are sent by email or SMS as soon as your parcel leaves our warehouse.</li>
    </ul>

    <h2 id="returns">Returns &amp; Refunds</h2>
    <ul>
        <li>Returns are accepted within <strong>7 days of delivery</strong>.</li>
        <li>Items must be unused, unwashed, and returned with original tags and packaging.</li>
        <li>Innerwear, nightwear and clearance items cannot be returned for hygiene reasons.</li>
        <li>To start a return, email <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> with your order number and a photo of the item.</li>
        <li>Refunds are processed within <strong>5&ndash;7 business days</strong> of the returned item reaching us.</li>
        <li>Damaged or incorrect items are replaced or fully refunded at no cost to you — report them within 48 hours of delivery.</li>
    </ul>

    <h2 id="terms">Terms of Service</h2>
    <p>
        By placing an order on <?= e($name) ?> you confirm the details you supply are accurate and that you are able to
        receive delivery at the address given. Prices, product availability and offers can change without notice.
    </p>
    <p>
        Product photographs are representative. Minor variation in colour and finish between the photo and the item you
        receive can occur due to screen calibration and production batches.
    </p>
    <p>
        We may cancel and fully refund an order if the item is out of stock, the delivery address is unserviceable, or we
        suspect fraudulent activity.
    </p>
    <p>
        All content on this site — text, images and layout — belongs to <?= e($name) ?> or its suppliers and may not be
        reproduced without permission.
    </p>

    <p class="text-sm text-gray-500 mt-8">Last updated: <?= date('F Y') ?></p>
    <?php
};

require __DIR__ . '/_layout.php';
