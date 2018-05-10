<?php
namespace Wovnio\Test\Helpers;

use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Headers;

class StoreAndHeaderHelper {
  public static function create($env, $options = array()) {
    $store = new Store($options);
    $headers = new Headers($env, $store);
    return array($store, $headers);
  }
}
