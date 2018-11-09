<?php
  require_once 'wovnio/wovnphp/Headers.php';
  require_once 'wovnio/wovnphp/Lang.php';
  require_once 'wovnio/wovnphp/Logger.php';
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

  // FIXME: should not force the factory, it should use cURL when possible but
  // some of us currently have problem with cURL
  $requestHandler = new FileGetContentsRequestHandler();
  RequestHandlerFactory::setInstance($requestHandler);

  // GET STORE AND HEADERS
  list($store, $headers) = Utils::getStoreAndHeaders($_SERVER);

  $_ENV['WOVN_TARGET_LANG'] = $headers->lang();
  $headers->requestOut();

  if (!Utils::isFilePathURI($headers->getDocumentURI(), $store)) {
    // use the callback of ob_start to modify the content and return
    ob_start(function ($buffer) use ($headers, $store) {
      $headers->responseOut();

      if (empty($buffer) || !Utils::isHtml(headers_list(), $buffer)) {
        return $buffer;
      }

      if ($store->settings['check_amp'] && Utils::isAmp($buffer)) {
        return $buffer;
      }

      $translatedBuffer = API::translate($store, $headers, $buffer);
      if ($translatedBuffer !== null && !empty($translatedBuffer)) {
        Utils::changeHeaders($translatedBuffer, $store);
        return $translatedBuffer;
      }

      return $buffer;
    });
  }
