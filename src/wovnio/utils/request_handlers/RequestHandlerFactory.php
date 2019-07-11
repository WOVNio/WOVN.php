<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'FileGetContentsRequestHandler.php';
require_once 'CurlRequestHandler.php';

use Wovnio\Utils\RequestHandlers\FileGetContentsRequestHandler;
use Wovnio\Utils\RequestHandlers\CurlRequestHandler;

class RequestHandlerFactory
{
    private static $instance = null;

    public static function setInstance($instance)
    {
        self::$instance = $instance;
    }

    public static function getBestAvailableRequestHandler()
    {
        if (self::$instance === null) {
            if (CurlRequestHandler::available()) {
                self::$instance = new CurlRequestHandler();
            } elseif (FileGetContentsRequestHandler::available()) {
                self::$instance = new FileGetContentsRequestHandler();
            }
        }

        return self::$instance;
    }
}
