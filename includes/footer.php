<footer class="bg-white py-8 rounded-t-lg shadow-inner mt-8">
    <div class="container mx-auto px-4 sm:px-6 md:px-12 text-center md:text-left">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h4 class="text-xl font-bold text-gray-800 mb-4"><?= e(site('site_name')) ?></h4>
                <p class="text-gray-600 leading-relaxed"><?= e(site('description')) ?></p>
            </div>

            <div class="md:text-center">
                <h4 class="text-xl font-bold text-gray-800 mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li><a href="/" class="text-gray-600 hover:text-gray-900 transition-colors duration-300">Shop</a></li>
                    <li><a href="/pages/about-us" class="text-gray-600 hover:text-gray-900 transition-colors duration-300">About Us</a></li>
                    <li><a href="/pages/FAQ" class="text-gray-600 hover:text-gray-900 transition-colors duration-300">FAQ</a></li>
                    <li><a href="/pages/contact-us" class="text-gray-600 hover:text-gray-900 transition-colors duration-300">Contact</a></li>
                    <li><a href="/pages/policies" class="text-gray-600 hover:text-gray-900 transition-colors duration-300">Policy</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-xl font-bold text-gray-800 mb-4">Get in Touch</h4>
                <ul class="space-y-2 text-gray-600">
                    <li><a href="mailto:<?= e(site('email')) ?>" class="hover:text-gray-900"><?= e(site('email')) ?></a></li>
                    <li><a href="tel:<?= e(preg_replace('/\s+/', '', site('phone'))) ?>" class="hover:text-gray-900"><?= e(site('phone')) ?></a></li>
                    <li><?= e(site('address')) ?></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-200 mt-8 pt-6 text-center">
            <p class="text-gray-500 text-sm">&copy; <?= date('Y') ?> <?= e(site('site_name')) ?>. All rights reserved</p>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var openBtn  = document.getElementById('mobile-menu-button');
    var closeBtn = document.getElementById('close-sidebar');
    var sidebar  = document.getElementById('sidebar-menu');
    var overlay  = document.getElementById('overlay');

    function open()  { sidebar.classList.add('open');    overlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
    function close() { sidebar.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow = ''; }

    if (openBtn)  openBtn.addEventListener('click', open);
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (overlay)  overlay.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
});

// Strip click-tracking params so the visible URL stays clean and shareable.
(function () {
    var junk = ['fbclid', 'gclid', 'ScCid', 'msclkid', 'ttclid'];
    var url  = new URL(window.location.href);
    var hit  = false;
    junk.forEach(function (k) { if (url.searchParams.has(k)) { url.searchParams.delete(k); hit = true; } });
    if (hit) window.history.replaceState({}, document.title, url.pathname + (url.search || '') + url.hash);
})();
</script>

<?php ads_flush_gpt(); ?>

</body>
</html>
