<?php
if (!defined('WOVN_PHP_NAME')) {
    define('WOVN_PHP_NAME', 'WOVN.php');
}
if (!defined('WOVN_PHP_VERSION')) {
    $version = isset($_ENV['WOVN_ENV']) && $_ENV['WOVN_ENV'] === 'development' ? 'VERSION' : '1.8.1';
    define('WOVN_PHP_VERSION', $version);
}
