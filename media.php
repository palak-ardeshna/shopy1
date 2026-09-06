<?php
/**
 * Serves images uploaded at runtime.
 *
 * On a normal host uploads land in assets/img/ and are served directly by the
 * web server, so this file is never reached. On a serverless host the only
 * writable place is the temp dir, which is outside the document root — uploaded
 * files are streamed back through here instead.
 */
require_once __DIR__ . '/includes/config.php';

$name = basename($_GET['f'] ?? '');

if ($name === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
    http_response_code(400);
    exit('Bad request');
}

$candidates = [
    WRITABLE_ROOT . '/assets/img/' . $name,
    ROOT_PATH . '/assets/img/' . $name,
];

foreach ($candidates as $file) {
    if (!is_file($file)) continue;

    $types = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif',  'webp' => 'image/webp',
    ];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: public, max-age=86400');
    readfile($file);
    exit;
}

http_response_code(404);
exit('Not found');
