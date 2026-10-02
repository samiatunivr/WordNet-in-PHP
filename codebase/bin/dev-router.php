<?php
// Development only:  php -S localhost:8000 -t public codebase/bin/dev-router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(dirname(__DIR__, 2) . '/public' . $path) && !str_ends_with($path, '.php')) {
    return false;
}
require dirname(__DIR__, 2) . '/public/index.php';
