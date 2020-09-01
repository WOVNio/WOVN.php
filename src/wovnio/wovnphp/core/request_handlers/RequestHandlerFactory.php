<?php
namespace Wovnio\Wovnphp\Core\RequestHandlers;

require_once 'FileGetContentsRequestHandler.php';
require_once 'CurlRequestHandler.php';


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
