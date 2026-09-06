<?php
require_once __DIR__ . '/config.php';

/** All products, cached per request. */
function all_products() {
    static $cache = null;
    if ($cache === null) {
        $cache = load_json('products/products.json', []);
    }
    return $cache;
}

function find_product($id) {
    foreach (all_products() as $p) {
        if ((string)$p['id'] === (string)$id) return $p;
    }
    return null;
}

/** Distinct categories in the order they first appear. */
function all_categories() {
    $seen = [];
    foreach (all_products() as $p) {
        $cat = $p['category'] ?? '';
        if ($cat !== '' && !isset($seen[$cat])) $seen[$cat] = 0;
        if ($cat !== '') $seen[$cat]++;
    }
    return $seen;
}

function products_in_category($category) {
    $out = [];
    foreach (all_products() as $p) {
        if (strcasecmp($p['category'] ?? '', $category) === 0) $out[] = $p;
    }
    return $out;
}

/** Gallery images for a product, skipping blanks. */
function product_images($p) {
    $imgs = [];
    foreach (['img1', 'img2', 'img3', 'img4', 'img5'] as $k) {
        if (!empty($p[$k])) $imgs[] = $p[$k];
    }
    return $imgs ?: ['/assets/img/placeholder.png'];
}

/** URL-safe slug that still reads like the title. */
function slugify($title) {
    $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', (string)$title);
    return trim($slug, '-');
}

function product_url($p) {
    return '/products/' . $p['id'] . '/' . slugify($p['title']);
}

function category_url($cat, $page = 1) {
    $url = '/category/' . rawurlencode($cat);
    return $page > 1 ? $url . '/' . $page : $url;
}

/** Human label for an ALL_CAPS category key. */
function category_label($cat) {
    return ucwords(strtolower(str_replace('_', ' ', $cat)));
}

function paginate(array $items, $page, $perPage) {
    $total  = count($items);
    $pages  = max(1, (int)ceil($total / $perPage));
    $page   = min(max(1, (int)$page), $pages);
    return [
        'items'       => array_slice($items, ($page - 1) * $perPage, $perPage),
        'page'        => $page,
        'total_pages' => $pages,
        'total_items' => $total,
    ];
}

/** Related products: same category first, then anything else. */
function related_products($product, $limit) {
    $same = $other = [];
    foreach (all_products() as $p) {
        if ((string)$p['id'] === (string)$product['id']) continue;
        if (strcasecmp($p['category'] ?? '', $product['category'] ?? '') === 0) {
            $same[] = $p;
        } else {
            $other[] = $p;
        }
    }
    shuffle($other);
    return array_slice(array_merge($same, $other), 0, $limit);
}
