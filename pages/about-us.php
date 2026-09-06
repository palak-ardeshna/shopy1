<?php
require_once dirname(__DIR__) . '/includes/functions.php';

$page_title       = 'About Us — ' . site('site_name');
$page_description = 'Learn about ' . site('site_name') . ', what we sell and how we ship.';
$heading          = 'About Us';
$subheading       = 'Everyday fashion for men and women, refreshed daily.';

$content_body = function () {
    $count = count(all_products());
    $cats  = count(all_categories());
    ?>
    <h2>Who we are</h2>
    <p>
        <?= e(site('site_name')) ?> is an online marketplace for affordable, everyday fashion. We work directly with
        manufacturers and wholesale partners so we can offer current styles at prices that make sense — without the
        markup you would pay in a mall.
    </p>
    <p>
        Our catalogue currently holds <strong><?= (int)$count ?> products</strong> across
        <strong><?= (int)$cats ?> categories</strong>, and we add new arrivals regularly.
    </p>

    <h2>What we sell</h2>
    <ul>
        <li>Women&rsquo;s ethnic wear — kurtis, kurta sets, palazzo sets and dupatta sets</li>
        <li>Women&rsquo;s western wear — dresses, tops and co-ord sets</li>
        <li>Men&rsquo;s wear — kurta sets, casual shirts, t-shirts and loungewear</li>
        <li>Nightwear and comfort essentials for the whole family</li>
    </ul>

    <h2>How we work</h2>
    <p>
        Every order is packed and dispatched from our partner warehouses. We offer <strong>Cash on Delivery</strong>
        so you only pay once your parcel is in your hands, and delivery typically takes 3&ndash;5 business days
        depending on your pincode.
    </p>

    <h2>Get in touch</h2>
    <p>
        Questions about an order, a size or a return? Email us at
        <a href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a>, call
        <a href="tel:<?= e(preg_replace('/\s+/', '', site('phone'))) ?>"><?= e(site('phone')) ?></a>, or use our
        <a href="/pages/contact-us">contact form</a>. We reply within one business day.
    </p>
    <?php
};

require __DIR__ . '/_layout.php';
