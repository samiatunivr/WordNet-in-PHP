<?php
declare(strict_types=1);

/*
 * Asl bootstrap: loads configuration, registers the autoloader and sets
 * hardened PHP defaults. Everything in codebase/ lives outside the web root;
 * only public/ is served by the web server.
 */

define('ASL_ROOT', __DIR__);
define('ASL_PUBLIC', dirname(__DIR__) . '/public');

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'Asl\\', 4) !== 0) {
        return;
    }
    $file = ASL_ROOT . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require ASL_ROOT . '/src/helpers.php';

Asl\Config::load(ASL_ROOT . '/config/config.php');

error_reporting(E_ALL);
ini_set('display_errors', Asl\Config::isProduction() ? '0' : '1');
ini_set('log_errors', '1');
ini_set('error_log', ASL_ROOT . '/storage/logs/php-error.log');
ini_set('expose_php', '0');
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');
