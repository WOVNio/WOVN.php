<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'FileGetContentsRequestHandler.php';
require_once 'CurlRequestHandler.php';

use Wovnio\Utils\RequestHandlers\FileGetContentsRequestHandler;
use Wovnio\Utils\RequestHandlers\CurlRequestHandler;

class RequestHandlerFactory
{
    public static function getBestAvailableRequestHandler()
    {
        $request_handler = null;

        if (CurlRequestHandler::available()) {
            $request_handler = new CurlRequestHandler();
        } elseif (FileGetContentsRequestHandler::available()) {
            $request_handler = new FileGetContentsRequestHandler();
        }

        return $request_handler;
    }
}
