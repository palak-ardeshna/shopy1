<?php
require_once dirname(__DIR__) . '/includes/functions.php';
app_session_start();

$sent   = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $errors[] = 'Please fill in your name, email and message.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'That email address does not look valid.';
    }
    // Honeypot: bots fill hidden fields, people never see them.
    if (trim($_POST['website'] ?? '') !== '') {
        $errors[] = 'Your message could not be sent.';
    }

    if (!$errors) {
        append_json_record('data/messages.json', [
            'received_at' => date('c'),
            'name'        => $name,
            'email'       => $email,
            'subject'     => $subject,
            'message'     => $message,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
        $sent = true;
    }
}

$page_title       = 'Contact Us — ' . site('site_name');
$page_description = 'Get in touch with ' . site('site_name') . ' about an order, a return or anything else.';
$heading          = 'Contact Us';
$subheading       = 'We reply to every message within one business day.';

$content_body = function () use ($sent, $errors) {
    ?>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8 not-prose">
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Email</p>
            <a href="mailto:<?= e(site('email')) ?>" class="text-sm font-medium break-all"><?= e(site('email')) ?></a>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Phone</p>
            <a href="tel:<?= e(preg_replace('/\s+/', '', site('phone'))) ?>" class="text-sm font-medium"><?= e(site('phone')) ?></a>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Address</p>
            <p class="text-sm font-medium text-gray-700"><?= e(site('address')) ?></p>
        </div>
    </div>

    <?php if ($sent): ?>
    <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4 mb-6">
        <p class="font-semibold text-green-900 mb-1">Message received</p>
        <p class="text-sm text-green-800">Thanks for getting in touch — we&rsquo;ll reply within one business day.</p>
    </div>
    <?php endif; ?>

    <?php if ($errors): ?>
    <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-6">
        <?php foreach (array_unique($errors) as $err): ?>
        <p class="text-sm text-red-800"><?= e($err) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <h2>Send us a message</h2>
    <form method="post" class="not-prose space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-800 mb-2">Your Name</label>
                <input type="text" id="name" name="name" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-800 mb-2">Email Address</label>
                <input type="email" id="email" name="email" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <label for="subject" class="block text-sm font-semibold text-gray-800 mb-2">Subject (Optional)</label>
            <input type="text" id="subject" name="subject" placeholder="Order number, return request…"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label for="message" class="block text-sm font-semibold text-gray-800 mb-2">Message</label>
            <textarea id="message" name="message" rows="6" required
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
        </div>

        <!-- Honeypot: hidden from people, irresistible to bots. -->
        <div style="position:absolute;left:-9999px;" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <button type="submit"
                class="bg-indigo-600 text-white py-3 px-8 rounded-full font-semibold hover:bg-indigo-700 transition-colors duration-300 shadow-md">
            Send Message
        </button>
    </form>
    <?php
};

require __DIR__ . '/_layout.php';
