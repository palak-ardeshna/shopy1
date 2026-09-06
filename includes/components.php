<?php
require_once __DIR__ . '/functions.php';

/** One product tile, used by the home grid, category grid and related rail. */
function product_card($p, $imgClass = 'w-full h-52 object-cover') {
    $url = product_url($p);
    $img = product_images($p)[0];
    ?>
    <div class="product-card bg-white rounded-xl shadow-md overflow-hidden fade-in">
        <a href="<?= e($url) ?>" class="block group">
            <div class="overflow-hidden">
                <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy"
                     class="<?= e($imgClass) ?> transition-transform duration-500 group-hover:scale-110">
            </div>
        </a>
        <div class="p-4 sm:p-6 text-center">
            <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-2 line-clamp-2"><?= e($p['title']) ?></h3>
            <span class="block text-lg sm:text-xl font-bold text-gray-900 mb-4"><?= e(money($p['price'])) ?></span>
            <a href="<?= e($url) ?>"
               class="inline-block w-full bg-indigo-600 text-white py-2 px-4 rounded-full font-medium hover:bg-indigo-700 transition-colors duration-300">View</a>
        </div>
    </div>
    <?php
}

/**
 * Path-based pagination: Previous, a sliding window of at most 3 numbers, Next.
 * Page N becomes "$baseUrl/N". Page 1 uses $firstUrl when the paginated base is
 * not itself a real page — the home grid pages under /page/N but page 1 is "/".
 */
function pagination_nav($currentPage, $totalPages, $baseUrl, $firstUrl = null) {
    if ($totalPages <= 1) return;

    $firstUrl = $firstUrl ?? ($baseUrl ?: '/');

    $href = function ($n) use ($baseUrl, $firstUrl) {
        return $n <= 1 ? $firstUrl : rtrim($baseUrl, '/') . '/' . $n;
    };

    $btn = function ($label, $n, $active = false) use ($href) {
        $classes = 'px-3 py-2 rounded-md border text-sm font-medium transition-colors '
                 . ($active ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-200');
        printf('<a href="%s" class="%s">%s</a>' . "\n", e($href($n)), $classes, e($label));
    };

    if ($totalPages <= 3) {
        $start = 1;
        $end   = $totalPages;
    } elseif ($currentPage <= 2) {
        $start = 1;
        $end   = 3;
    } elseif ($currentPage >= $totalPages - 1) {
        $start = $totalPages - 2;
        $end   = $totalPages;
    } else {
        $start = $currentPage - 1;
        $end   = $currentPage + 1;
    }

    echo '<nav class="flex justify-center flex-wrap gap-2 mt-10" aria-label="Pagination">' . "\n";
    if ($currentPage > 1)          $btn('Previous', $currentPage - 1);
    for ($i = $start; $i <= $end; $i++) $btn((string)$i, $i, $i === $currentPage);
    if ($currentPage < $totalPages) $btn('Next', $currentPage + 1);
    echo "</nav>\n";
}

/** Breadcrumb trail. $trail is [label => url], last entry rendered as plain text. */
function breadcrumbs(array $trail) {
    $labels = array_keys($trail);
    $last   = end($labels);
    echo '<nav class="text-sm text-gray-500 mb-6" aria-label="Breadcrumb"><ol class="flex flex-wrap items-center gap-2">';
    foreach ($trail as $label => $url) {
        echo '<li class="flex items-center gap-2">';
        if ($label === $last || $url === null) {
            echo '<span class="text-gray-700 font-medium">' . e($label) . '</span>';
        } else {
            echo '<a href="' . e($url) . '" class="hover:text-indigo-600">' . e($label) . '</a>';
            echo '<span class="text-gray-300">/</span>';
        }
        echo '</li>';
    }
    echo '</ol></nav>' . "\n";
}
