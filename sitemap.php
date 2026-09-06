<?php
require_once __DIR__ . '/includes/functions.php';

/** Dynamic XML sitemap covering the home, category and product pages. */
header('Content-Type: application/xml; charset=utf-8');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base   = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$today  = date('Y-m-d');

$urls = [['loc' => '/', 'priority' => '1.0', 'freq' => 'daily']];

foreach (['about-us', 'FAQ', 'contact-us', 'policies'] as $slug) {
    $urls[] = ['loc' => '/pages/' . $slug, 'priority' => '0.4', 'freq' => 'monthly'];
}

$perPage = (int)site('products_per_page_category');
foreach (all_categories() as $cat => $count) {
    $pages = max(1, (int)ceil($count / $perPage));
    for ($p = 1; $p <= $pages; $p++) {
        $urls[] = ['loc' => category_url($cat, $p), 'priority' => '0.8', 'freq' => 'weekly'];
    }
}

foreach (all_products() as $product) {
    $urls[] = ['loc' => product_url($product), 'priority' => '0.9', 'freq' => 'weekly'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    printf("  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n    <changefreq>%s</changefreq>\n    <priority>%s</priority>\n  </url>\n",
        htmlspecialchars($base . $u['loc'], ENT_XML1), $today, $u['freq'], $u['priority']);
}
echo '</urlset>';
