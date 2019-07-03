<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'FileGetContentsRequestHandler.php';
require_once 'CurlRequestHandler.php';

use Wovnio\Utils\RequestHandlers\FileGetContentsRequestHandler;
use Wovnio\Utils\RequestHandlers\CurlRequestHandler;

class RequestHandlerFactory
{
    static public function get_best_available_request_handler() {
		$request_handler = null;

		if (CurlRequestHandler::available()) {
			$request_handler = new CurlRequestHandler();
		} else if (FileGetContentsRequestHandler::available()) {
			$request_handler = new FileGetContentsRequestHandler();
		}

		return $request_handler;
    }
}
