<?php
namespace Wovnio\Test\Helpers;

require_once 'test/helpers/EnvFactory.php';

use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Headers;

class StoreAndHeadersFactory
{
    public static function fromFixture($fixture = 'default', $settingsOverwrite = array(), $envOverwrite = array())
    {
        $storeSettings = self::buildStoreOptions($settingsOverwrite);
        $env = EnvFactory::fromFixture($fixture, $envOverwrite);

        return StoreAndHeadersFactory::get($env, $storeSettings);
    }

    public static function get($env, $settings = array())
    {
        $store = new Store($settings);
        $headers = new Headers($env, $store);

        return array($store, $headers);
    }

    private static function buildStoreOptions($options)
    {
        $defaultOptions = array(
            'default_lang' => 'en',
            'supported_langs' => array('en'),
            'url_pattern_name' => 'query',
            'project_token' => '123456'
        );

        return array_merge($defaultOptions, $options);
    }
}
