<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

$products = load_json('products/products.json', []);
$action   = $_GET['action'] ?? 'list';
$editId   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/**
 * Save an uploaded image and return the web path to reference it by.
 *
 * On a normal host it lands in assets/img/ and is served statically. On a
 * serverless host assets/ is read-only, so it goes to the writable area and is
 * served back through media.php.
 */
function save_upload($field) {
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime    = mime_content_type($_FILES[$field]['tmp_name']);
    if (!isset($allowed[$mime])) return null;

    $name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $dest = IS_SERVERLESS
        ? writable_path('assets/img/' . $name)
        : ROOT_PATH . '/assets/img/' . $name;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) return null;

    return IS_SERVERLESS
        ? '/media.php?f=' . rawurlencode($name)
        : '/assets/img/' . $name;
}

// ---------------------------------------------------------------- actions ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_csrf();
    $op = $_POST['op'] ?? '';

    if ($op === 'delete') {
        $id = (int)$_POST['id'];
        $products = array_values(array_filter($products, fn($p) => (int)$p['id'] !== $id));
        admin_save_json('products/products.json', $products);
        admin_flash('Product #' . $id . ' deleted.');
        header('Location: /admin/products.php', true, 302);
        exit;
    }

    if ($op === 'save') {
        $id = (int)($_POST['id'] ?? 0);

        // Keep whichever images already existed unless a new file replaces them.
        $existing = $id ? (find_product($id) ?? []) : [];
        $images   = [];
        for ($i = 1; $i <= 5; $i++) {
            $uploaded = save_upload('img' . $i);
            $typed    = trim($_POST['img' . $i . '_url'] ?? '');
            $images['img' . $i] = $uploaded ?? ($typed !== '' ? $typed : ($existing['img' . $i] ?? ''));
        }

        $record = array_merge([
            'id'          => $id ?: (max(array_map(fn($p) => (int)$p['id'], $products) ?: [0]) + 1),
            'title'       => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price'       => (float)($_POST['price'] ?? 0),
            'category'    => trim($_POST['category'] ?? 'UNCATEGORIZED'),
        ], $images);

        if ($record['title'] === '') {
            admin_flash('A product needs a title.', 'error');
            header('Location: /admin/products.php?action=edit&id=' . $id, true, 302);
            exit;
        }

        $found = false;
        foreach ($products as $k => $p) {
            if ((int)$p['id'] === (int)$record['id']) {
                $products[$k] = $record;
                $found = true;
                break;
            }
        }
        if (!$found) $products[] = $record;

        admin_save_json('products/products.json', $products);
        admin_flash('Saved "' . $record['title'] . '".');
        header('Location: /admin/products.php', true, 302);
        exit;
    }
}

// ------------------------------------------------------------------ views ---

if ($action === 'edit' || $action === 'new') {
    $p = $action === 'edit' ? (find_product($editId) ?? []) : [];
    $existingCats = array_keys(all_categories());

    admin_header($action === 'edit' ? 'Edit product #' . $editId : 'New product');
    admin_flash();
    ?>
    <form method="post" enctype="multipart/form-data" class="bg-white rounded-xl shadow p-6 max-w-3xl space-y-5">
        <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
        <input type="hidden" name="op"   value="save">
        <input type="hidden" name="id"   value="<?= (int)($p['id'] ?? 0) ?>">

        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Title</label>
            <input type="text" name="title" required value="<?= e($p['title'] ?? '') ?>"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-800 mb-2">Price (<?= e(site('currency')) ?>)</label>
                <input type="number" name="price" step="0.01" min="0" required value="<?= e($p['price'] ?? '') ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-800 mb-2">Category</label>
                <input type="text" name="category" list="cats" required value="<?= e($p['category'] ?? '') ?>"
                       placeholder="ZAVIRA_VOGUE"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <datalist id="cats">
                    <?php foreach ($existingCats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?>
                </datalist>
                <p class="text-xs text-gray-500 mt-1">Used in the URL, so stick to letters, digits and underscores.</p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Description</label>
            <textarea name="description" rows="10"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"><?= e($p['description'] ?? '') ?></textarea>
            <p class="text-xs text-gray-500 mt-1">Blank lines become separate paragraphs on the product page.</p>
        </div>

        <div>
            <p class="block text-sm font-semibold text-gray-800 mb-3">Images</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php for ($i = 1; $i <= 5; $i++): $cur = $p['img' . $i] ?? ''; ?>
                <div class="border border-gray-200 rounded-lg p-3">
                    <p class="text-xs font-medium text-gray-500 mb-2">
                        Image <?= $i ?><?= $i === 1 ? ' (main)' : '' ?>
                    </p>
                    <?php if ($cur): ?>
                    <img src="<?= e($cur) ?>" alt="" class="w-full h-28 object-cover rounded mb-2">
                    <?php endif; ?>
                    <input type="file" name="img<?= $i ?>" accept="image/*" class="w-full text-xs mb-2">
                    <input type="text" name="img<?= $i ?>_url" value="<?= e($cur) ?>" placeholder="/assets/img/…"
                           class="w-full px-2 py-1 text-xs border border-gray-300 rounded">
                </div>
                <?php endfor; ?>
            </div>
            <p class="text-xs text-gray-500 mt-2">Upload a file or paste a path. Uploading replaces the current image.</p>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-indigo-600 text-white py-3 px-8 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">Save product</button>
            <a href="/admin/products.php" class="py-3 px-8 rounded-lg font-semibold border border-gray-300 text-gray-700 hover:bg-gray-100 transition-colors">Cancel</a>
        </div>
    </form>
    <?php
    admin_footer();
    exit;
}

// ------------------------------------------------------------------- list ---

$q = trim($_GET['q'] ?? '');
$visible = $q === '' ? $products : array_filter($products, function ($p) use ($q) {
    return stripos($p['title'] ?? '', $q) !== false || stripos($p['category'] ?? '', $q) !== false;
});

admin_header('Products (' . count($products) . ')');
admin_flash();
?>

<div class="flex flex-wrap items-center gap-3 mb-6">
    <a href="/admin/products.php?action=new"
       class="bg-indigo-600 text-white py-2 px-5 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">+ New product</a>

    <form method="get" class="flex gap-2 ml-auto">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search title or category"
               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">Search</button>
        <?php if ($q): ?><a href="/admin/products.php" class="px-4 py-2 text-gray-500 hover:text-gray-800">Clear</a><?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600 text-left">
            <tr>
                <th class="p-3 w-16">Image</th>
                <th class="p-3 w-16">ID</th>
                <th class="p-3">Title</th>
                <th class="p-3">Category</th>
                <th class="p-3 text-right">Price</th>
                <th class="p-3 text-right w-40">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($visible as $p): ?>
            <tr class="border-t border-gray-100 hover:bg-gray-50">
                <td class="p-3">
                    <img src="<?= e(product_images($p)[0]) ?>" alt="" class="w-12 h-12 object-cover rounded">
                </td>
                <td class="p-3 text-gray-500"><?= (int)$p['id'] ?></td>
                <td class="p-3">
                    <a href="<?= e(product_url($p)) ?>" target="_blank" class="font-medium text-gray-900 hover:text-indigo-600"><?= e($p['title']) ?></a>
                </td>
                <td class="p-3">
                    <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded"><?= e($p['category'] ?? '') ?></span>
                </td>
                <td class="p-3 text-right font-semibold"><?= e(money($p['price'] ?? 0)) ?></td>
                <td class="p-3 text-right whitespace-nowrap">
                    <a href="/admin/products.php?action=edit&id=<?= (int)$p['id'] ?>" class="text-indigo-600 hover:underline">Edit</a>
                    <form method="post" class="inline ml-3"
                          onsubmit="return confirm('Delete <?= e(addslashes($p['title'])) ?>? This cannot be undone.');">
                        <input type="hidden" name="csrf" value="<?= e(admin_csrf()) ?>">
                        <input type="hidden" name="op" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$visible): ?>
            <tr><td colspan="6" class="p-8 text-center text-gray-500">No products match &ldquo;<?= e($q) ?>&rdquo;.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_footer(); ?>
