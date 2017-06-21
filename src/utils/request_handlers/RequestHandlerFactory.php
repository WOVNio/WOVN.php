<?php
  namespace Wovnio\Utils\RequestHandlers;

  require_once 'src/utils/request_handlers/FileGetContentsRequestHandler.php';
  require_once 'src/utils/request_handlers/CurlRequestHandler.php';

  use Wovnio\Utils\RequestHandlers\FileGetContentsRequestHandler;
  use Wovnio\Utils\RequestHandlers\CurlRequestHandler;

  class RequestHandlerFactory {
    const REQUEST_HANDLER_CURL = 'curl';
    const REQUEST_HANDLER_FILE_GET_CONTENTS = 'file_get_contents';
    const REQUEST_HANDLER_DEFAULT = self::REQUEST_HANDLER_CURL;

    public static function get($request_handler = self::REQUEST_HANDLER_DEFAULT) {
      switch ($request_handler) {
        case self::REQUEST_HANDLER_CURL:
          if (function_exists('curl_version')) {
            return new CurlRequestHandler();
          } else {
            error_log('Request handler "' . $request_handler . '" not available. Using "' . self::REQUEST_HANDLER_DEFAULT . '" instead...');
            return self::get(self::REQUEST_HANDLER_DEFAULT);
          }
          break;
        case self::REQUEST_HANDLER_FILE_GET_CONTENTS:
          return new FileGetContentsRequestHandler();
          break;
      }

      throw new \Exception('Unsupport request handler: ' . $request_handler);
    }
  }
