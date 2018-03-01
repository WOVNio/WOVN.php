<?php
namespace Wovnio\Test\Helpers;

use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Headers;

class StoreAndHeaderHelper {
  public static function create($env) {
    $store = new Store(array());
    $headers = new Headers($env, $store);
    return array($store, $headers);
  }
}
