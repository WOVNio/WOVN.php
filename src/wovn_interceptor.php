<?php
  require_once 'wovnio/wovnphp/Headers.php';
  require_once 'wovnio/wovnphp/Lang.php';
  require_once 'wovnio/wovnphp/Store.php';
  require_once 'wovnio/wovnphp/Utils.php';
  require_once 'wovnio/wovnphp/API.php';
  require_once 'wovnio/wovnphp/Url.php';

  use Wovnio\Wovnphp\Utils;
  use Wovnio\Wovnphp\API;

  // GET STORE AND HEADERS
  list($store, $headers) = Utils::getStoreAndHeaders($_SERVER);

  $_ENV['WOVN_TARGET_LANG'] = $headers->lang();

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
