<?php
namespace Wovnio\Test\Helpers;

use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Headers;

class StoreAndHeadersFactory {
  public static function fromFixture($fixture = 'default', $settingsOverwrite = array(), $envOverwrite = array()) {
    $storeSettings = self::buildStoreOptions($settingsOverwrite);
    $env = self::buildEnv($fixture, $envOverwrite);

    return StoreAndHeadersFactory::get($env, $storeSettings);
  }

  public static function get($env, $settings = array()) {
    $store = new Store($settings);
    $headers = new Headers($env, $store);

    return array($store, $headers);
  }

  private static function buildStoreOptions($options) {
    $defaultOptions = array(
      'default_lang' => 'en',
      'supported_langs' => array('en'),
      'url_pattern_name' => 'path',
      'project_token' => '123456'
    );

    return array_merge($defaultOptions, $options);
  }

  private static function buildEnv($fixture, $envOverwrite) {
    $iniFilename = self::fixture2Filename($fixture);
    $iniFile = parse_ini_file(dirname(__FILE__) . '/../fixtures/env/' . $iniFilename);

    return array_merge($iniFile['env'], $envOverwrite);
  }

  private static function fixture2Filename($fixture) {
    if ($fixture) {
      return preg_replace('/(.ini)?$/', '.ini', $fixture);
    }

    return 'default.ini';
  }
}
