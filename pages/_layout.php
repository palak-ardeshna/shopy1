<?php
/**
 * Shared shell for the static content pages.
 * A page sets $page_title, $heading, $subheading and $content_body (a callable).
 */
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/components.php';

require dirname(__DIR__) . '/includes/header.php';
?>
<main>
    <section class="container mx-auto px-4 sm:px-6 md:px-12 py-10 md:py-16">
        <div class="max-w-3xl mx-auto">
            <?php breadcrumbs(['Home' => '/', $heading => null]); ?>

            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3"><?= e($heading) ?></h1>
            <?php if (!empty($subheading)): ?>
            <p class="text-gray-600 text-lg mb-8"><?= e($subheading) ?></p>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-lg p-6 md:p-10 prose-page">
                <?php $content_body(); ?>
            </div>
        </div>
    </section>
</main>

<style>
.prose-page h2 { font-size:1.25rem; font-weight:700; color:#1f2937; margin:1.75rem 0 .5rem; }
.prose-page h2:first-child { margin-top:0; }
.prose-page p  { color:#4b5563; line-height:1.75; margin-bottom:1rem; }
.prose-page ul { list-style:disc; padding-left:1.5rem; color:#4b5563; margin-bottom:1rem; }
.prose-page li { margin-bottom:.35rem; }
.prose-page a  { color:#4f46e5; text-decoration:underline; }
</style>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
