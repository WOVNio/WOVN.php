<?php
  require_once 'src/wovnio/wovnphp/Headers.php';
  require_once 'src/wovnio/wovnphp/Lang.php';
  require_once 'src/wovnio/wovnphp/Store.php';
  require_once 'src/wovnio/wovnphp/Utils.php';
  require_once 'src/wovnio/wovnphp/API.php';

  use Wovnio\Wovnphp\Utils;
  use Wovnio\Wovnphp\API;

  // GET STORE AND HEADERS
  list($store, $headers) = Utils::getStoreAndHeaders($_SERVER);

  $_ENV['WOVN_TARGET_LANG'] = $headers->lang();

  // HEADERS REQUEST OUT
  $headers->requestOut($includePath);

  // use the callback of ob_start to modify the content and return
  ob_start(function($buffer) use ($headers, $store, $includeDir, $includePath) {
    if(!empty($buffer) && $buffer != strip_tags($buffer)) {
      $translated_buffer = API::translate($store, $headers, $buffer);

      if ($translated_buffer !== NULL && !empty($translated_buffer)) {
        Utils::changeHeaders($translated_buffer, $store);
        return $translated_buffer;
      }
    }

    return $buffer;
  });
