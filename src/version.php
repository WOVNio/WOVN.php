<?php
if (isset($_ENV['WOVN_ENV']) && $_ENV['WOVN_ENV'] === 'development') {
    define('WOVN_DEVELOPMENT', true);
}
if (!defined('WOVN_PHP_NAME')) {
    define('WOVN_PHP_NAME', 'WOVN.php');
}
if (!defined('WOVN_PHP_VERSION')) {
    $version = defined('WOVN_DEVELOPMENT') ? 'VERSION' : '0.1.22';
    define('WOVN_PHP_VERSION', $version);
}
