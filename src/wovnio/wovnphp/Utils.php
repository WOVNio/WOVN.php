<?php
namespace Wovnio\Wovnphp;

class Utils {

  // will return the store and headers objects
  public static function getStoreAndHeaders(&$env) {
    $file = DIRNAME(__FILE__) . '/../../../../wovn.ini';
    $store = Store::createFromFile($file);
    $headers = new Headers($env, $store);
    return array($store, $headers);
  }

  /**
   * @param $parentPath [String]
   * @param $childPath [String]
   * @return [String]
   * @example
   *  joinPath('/hello/', '/world/') #=> '/hello/world/'
   */
  static function joinPath($parentPath, $childPath) {
    return preg_replace('/\/+/', '/', $parentPath . '/'. $childPath);
  }

  public static function changeHeaders($buffer, $store) {
    if($store->settings['override_content_length']) {
      $buffer_length = strlen($buffer);
      //header cannot get at phpunit, so this code doesn't have any test..
      header('Content-Length: '.$buffer_length);
    }
  }

  private static function getEnv($env, $keys) {
    foreach ($keys as $key) {
      if (array_key_exists($key, $env)) {
        return $env[$key];
      }
    }
    return '';
  }
}
