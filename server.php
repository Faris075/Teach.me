<?php

/**
 * Local development router for `php artisan serve`.
 *
 * The PHP built-in server is started with this file's directory as its
 * document root working directory (public_path()), so paths are resolved
 * relative to this file's own location plus "/public".
 */

$publicPath = __DIR__.'/public';

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Serve existing static files directly (css, js, images, etc.).
if ($uri !== '/' && file_exists($publicPath.$uri) && is_file($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
