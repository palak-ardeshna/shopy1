<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/ads.php';

/**
 * Pages set $page_title / $page_description / $page_image before including this.
 */
$page_title       = $page_title       ?? site('site_name');
$page_description = $page_description ?? (site('site_name') . '  ' . site('tagline'));
$page_image       = $page_image       ?? '';
$categories       = all_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <meta name="description" content="<?= e($page_description) ?>">
    <meta name="keywords" content="<?= e(site('keywords')) ?>">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(site('site_name')) ?>">
    <meta property="og:title" content="<?= e($page_title) ?>">
    <meta property="og:description" content="<?= e($page_description) ?>">
    <?php if ($page_image): ?>
    <meta property="og:image" content="<?= e($page_image) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>

<?php if (IS_SERVERLESS): ?>
<div class="bg-amber-400 text-amber-950 text-center text-xs sm:text-sm px-4 py-2 font-medium">
    Demo store — orders are not real and saved data resets when the server restarts.
</div>
<?php endif; ?>

<?php ad_slot('global_top', 'my-4'); ?>

<header class="bg-white shadow-lg sticky top-0 z-50 rounded-b-lg">
    <div class="container mx-auto px-4 sm:px-6 md:px-12 py-4 flex justify-between items-center">
        <a href="/" class="text-2xl md:text-3xl font-bold text-gray-800 tracking-tight">
            <?= e(site('site_name')) ?>
        </a>

        <nav class="hidden md:flex space-x-8 items-center">
            <a href="/" class="text-gray-600 hover:text-gray-900 transition-colors duration-300 font-medium">Home</a>

            <div class="relative group">
                <button class="flex items-center text-gray-600 hover:text-gray-900 transition-colors duration-300 font-medium focus:outline-none">
                    Categories
                    <svg class="w-4 h-4 ml-1 transform group-hover:rotate-180 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="absolute left-0 mt-2 w-56 bg-white rounded-md shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-100 transform origin-top scale-95 group-hover:scale-100">
                    <div class="max-h-64 overflow-y-auto py-2 custom-scroll">
                        <?php foreach ($categories as $cat => $count): ?>
                        <a href="<?= e(category_url($cat)) ?>"
                           class="flex items-center justify-between py-2 px-4 text-gray-600 hover:bg-gray-100 hover:text-indigo-600 rounded-md font-medium text-sm transition-colors">
                            <span><?= e(category_label($cat)) ?></span>
                            <span class="text-xs text-gray-400"><?= (int)$count ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <a href="/pages/FAQ" class="text-gray-600 hover:text-gray-900 transition-colors duration-300 font-medium">FAQ</a>
            <a href="/pages/about-us" class="text-gray-600 hover:text-gray-900 transition-colors duration-300 font-medium">About</a>
            <a href="/pages/contact-us" class="text-gray-600 hover:text-gray-900 transition-colors duration-300 font-medium">Contact</a>
            <a href="/pages/policies" class="text-gray-600 hover:text-gray-900 transition-colors duration-300 font-medium">Policy</a>
        </nav>

        <div class="flex items-center space-x-4">
            <a href="/cart/view.php" class="relative" aria-label="Cart">
                <svg class="w-6 h-6 text-gray-600 hover:text-gray-900 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.19.982.707.982H19.5a1 1 0 00.993-.883L21 11H7m-2 4h14M7 19a2 2 0 100 4 2 2 0 000-4zm10 0a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                <span id="cart-count"
                      class="absolute top-0 right-0 -mt-2 -mr-2 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center"><?= isset($cart_count) ? (int)$cart_count : 0 ?></span>
            </a>
            <button id="mobile-menu-button" class="md:hidden p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500" aria-label="Open menu">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
            </button>
        </div>
    </div>
</header>

<aside id="sidebar-menu" class="sidebar">
    <div class="p-6 flex justify-between items-center border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-800">Menu</h3>
        <button id="close-sidebar" class="text-gray-600 hover:text-gray-900" aria-label="Close menu">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <nav class="p-6 pb-2 space-y-2">
        <a href="/" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-md font-medium">Home</a>
        <a href="/pages/FAQ" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-md font-medium">FAQ</a>
        <a href="/pages/about-us" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-md font-medium">About Us</a>
        <a href="/pages/contact-us" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-md font-medium">Contact Us</a>
        <a href="/pages/policies" class="block py-2 px-4 text-gray-600 hover:bg-gray-100 rounded-md font-medium">Policy</a>
    </nav>

    <h2 class="text-lg md:text-xl font-bold text-gray-800 px-6 pt-4 pb-2">Shop by Category</h2>

    <nav class="flex-grow px-6 pb-6 space-y-2 overflow-y-auto custom-scroll">
        <?php foreach ($categories as $cat => $count): ?>
        <a href="<?= e(category_url($cat)) ?>"
           class="flex items-center justify-between py-2 px-4 text-gray-600 hover:bg-gray-100 hover:text-indigo-600 rounded-md font-medium text-sm transition-colors">
            <span><?= e(category_label($cat)) ?></span>
            <span class="text-xs text-gray-400"><?= (int)$count ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
</aside>

<div id="overlay" class="overlay"></div>

<?php render_pixels(); ?>
