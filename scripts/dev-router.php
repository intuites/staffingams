<?php
/**
 * Router for PHP's built-in dev server (emulates the production .htaccess):
 *   php -S localhost:8080 -t public scripts/dev-router.php
 * Serves real files directly; everything else goes through index.php.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/../public' . $path;
if ($path !== '/' && is_file($file)) {
    return false; // let the built-in server serve the static file
}
require __DIR__ . '/../public/index.php';
