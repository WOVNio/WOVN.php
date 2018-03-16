<?php
  require_once 'wovnio/wovnphp/Headers.php';
  require_once 'wovnio/wovnphp/Lang.php';
  require_once 'wovnio/wovnphp/Store.php';
  require_once 'wovnio/wovnphp/Utils.php';
  require_once 'wovnio/wovnphp/API.php';
  require_once 'wovnio/wovnphp/Url.php';
  require_once 'wovnio/html/HtmlConverter.php';
  require_once 'wovnio/html/HtmlReplaceMarker.php';
  require_once 'wovnio/utils/request_handlers/RequestHandlerFactory.php';
  require_once 'wovnio/utils/request_handlers/FileGetContentsRequestHandler.php';
  require_once 'wovnio/modified_vendor/SimpleHtmlDom.php';
  require_once 'wovnio/modified_vendor/SimpleHtmlDomNode.php';

  use Wovnio\Wovnphp\Utils;
  use Wovnio\Wovnphp\API;
  use Wovnio\Utils\RequestHandlers\RequestHandlerFactory;
  use Wovnio\Utils\RequestHandlers\FileGetContentsRequestHandler;

  // FIXME should not force the factory, it should use cURL when possible but
  // some of us currently have problem with cURL
  $request_handler = new FileGetContentsRequestHandler();
  RequestHandlerFactory::set_instance($request_handler);

  // GET STORE AND HEADERS
  list($store, $headers) = Utils::getStoreAndHeaders($_SERVER);

  $_ENV['WOVN_TARGET_LANG'] = $headers->lang();
  $headers->requestOut();

  if (!Utils::isFilePathURI($headers->getDocumentURI())) {
    // use the callback of ob_start to modify the content and return
    ob_start(function($buffer) use ($headers, $store) {
      $headers->responseOut();

      if(!empty($buffer) && $buffer != strip_tags($buffer)) {
        $translated_buffer = API::translate($store, $headers, $buffer);

        if ($translated_buffer !== NULL && !empty($translated_buffer)) {
          Utils::changeHeaders($translated_buffer, $store);
          return $translated_buffer;
        }
      }

      return $buffer;
    });
  }

