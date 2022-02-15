<?php
namespace Wovnio\Test\Helpers;

require_once 'test/helpers/EnvFactory.php';
require_once 'src/wovnio/wovnphp/CookieLang.php';

use Wovnio\Wovnphp\CookieLang;
use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Headers;

class StoreAndHeadersFactory
{
    public static function fromFixture($fixture = 'default', $settingsOverwrite = array(), $envOverwrite = array(), $cookiesOverwrite = array())
    {
        $storeSettings = self::buildStoreOptions($settingsOverwrite);
        $env = EnvFactory::fromFixture($fixture, $envOverwrite);

        return self::get($env, $storeSettings, $cookiesOverwrite);
    }

    public static function get($env, $settings = array(), $cookiesOverwrite = array())
    {
        $store = new Store($settings);
        $cookieLang = new CookieLang($cookiesOverwrite);
        $headers = new Headers($env, $store, $cookieLang);

        return array($store, $headers);
    }

    private static function buildStoreOptions($options)
    {
        $defaultOptions = array(
            'default_lang' => 'en',
            'supported_langs' => array('en'),
            'url_pattern_name' => 'query',
            'lang_param_name' => 'wovn',
            'project_token' => '123456',
            'translate_canonical_tag' => true
        );

        return array_merge($defaultOptions, $options);
    }
}
