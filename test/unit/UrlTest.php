<?php
namespace Wovnio\Wovnphp\Tests\Unit;

require_once 'test/helpers/StoreAndHeadersFactory.php';

require_once 'src/wovnio/wovnphp/Url.php';
require_once 'src/wovnio/wovnphp/Store.php';
require_once 'src/wovnio/wovnphp/Headers.php';
require_once 'src/wovnio/wovnphp/Lang.php';

use Wovnio\test\Helpers\StoreAndHeadersFactory;
use Wovnio\Test\Helpers\TestUtils;

use Wovnio\Wovnphp\Url;
use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Headers;
use Wovnio\Wovnphp\Lang;

class UrlTest extends \PHPUnit_Framework_TestCase
{
    private function getStarted($pattern = 'path', $additional_env = array(), $additional_settings = array())
    {
        $settings = array(
            'default_lang' => 'ja',
            'supported_langs' => array('en'),
            'url_pattern_name' => $pattern,
            'lang_param_name' => 'wovn'
        );

        $merged_setting = array_merge($settings, $additional_settings);

        return StoreAndHeadersFactory::fromFixture('default', $merged_setting, $additional_env);
    }

    public function testAddPathLangCode()
    {
        $testCases = array(
            // no_lang_url, lang, path_prefix, expected_url
            array('https://example.com', 'en', '', 'https://example.com/en'),
            array('https://example.com/index.php', 'en', '', 'https://example.com/en/index.php'),
            array('https://example.com/a/b/index.html', 'en', '', 'https://example.com/en/a/b/index.html'),
            array('https://example.com/a/b/index.html', 'en', 'a/b', 'https://example.com/a/b/en/index.html'),
            array('/', 'en', '', '/en/'),
            array('/index.php', 'en', '', '/en/index.php'),
            array('/a/b/index.html', 'en', '', '/en/a/b/index.html'),
            array('/a/b/index.html', 'en', 'a/b', '/a/b/en/index.html'),
        );

        $url = new Url;
        foreach ($testCases as $case) {
            list($no_lang_url, $lang, $path_prefix, $expected_url) = $case;
            $this->assertEquals($expected_url, TestUtils::invokeMethod($url, 'addPathLangCode', array($no_lang_url, $lang, $path_prefix)));
        }
    }

    public function testAddQueryLangCode()
    {
        $testCases = array(
            // no_lang_url, lang, lang_param, expected_url
            array('https://example.com', 'en', 'wovn', 'https://example.com?wovn=en'),
            array('https://example.com/index.php', 'en', 'wovn', 'https://example.com/index.php?wovn=en'),
            array('https://example.com/a/b/index.html', 'en', 'wovn', 'https://example.com/a/b/index.html?wovn=en'),
            array('/', 'en', 'wovn', '/?wovn=en'),
            array('/index.php', 'en', 'wovn', '/index.php?wovn=en'),
            array('/a/b/index.html', 'en', 'wovn', '/a/b/index.html?wovn=en')
        );

        $url = new Url;
        foreach ($testCases as $case) {
            list($no_lang_url, $lang, $lang_param, $expected_url) = $case;
            $this->assertEquals($expected_url, TestUtils::invokeMethod($url, 'addQueryLangCode', array($no_lang_url, $lang, $lang_param)));
        }
    }

    public function testAddSubdomainLangCode()
    {
        $testCases = array(
            // no_lang_url, lang, lang_param, expected_url
            array('https://example.com', 'en', 'wovn', 'https://en.example.com'),
            array('https://example.com/index.php', 'en', 'wovn', 'https://en.example.com/index.php'),
            array('https://example.com/a/b/index.html', 'en', 'wovn', 'https://en.example.com/a/b/index.html'),
        );

        $url = new Url;
        foreach ($testCases as $case) {
            list($no_lang_url, $lang, $lang_param, $expected_url) = $case;
            $parsed_url = parse_url($no_lang_url);
            $this->assertEquals($expected_url, TestUtils::invokeMethod($url, 'addSubdomainLangCode', array($parsed_url, $lang, $no_lang_url)));
        }
    }

    public function testAddLangCodeRelativePathWithPathPattern()
    {
        $uri = '/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "/$lang/test"
        ));

        $this->assertEquals("/$lang$uri", Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomDefaultLangAlias()
    {
        $settings = array(
            'default_lang' => 'en',
            'custom_lang_aliases' => array('en' => 'english', 'ja' => 'japanese'),
            'url_pattern_name' => 'path',
            'supported_langs' => array('jp', 'fr')
        );

        $testCases = array(
            //uri, expected_uri, $lang, pattern
            array('https://my-site.com/', 'https://my-site.com/fr/', 'fr', 'path'),
            array('https://my-site.com/', 'https://my-site.com/english/', 'en', 'path'),
            array('https://my-site.com/', 'https://my-site.com/japanese/', 'ja', 'path'),
        );

        foreach ($testCases as $case) {
            list($uri, $expected_uri, $lang, $pattern) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
            $this->assertTrue($store->hasDefaultLangAlias());
            $this->assertEquals($expected_uri, Url::addLangCode($uri, $store, $lang, $headers));
        }
    }

    public function testAddLangCodeRelativePathWithLangCodeInsideAndPathPattern()
    {
        $uri = '/fr/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "/$lang/test"
        ));

        $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteUrWithPathPattern()
    {
        $req_uri = "http://my-site.com?lang=zh-CHS";
        $expected_uri = 'http://my-site.com/en?lang=zh-CHS';
        $lang = 'en';
        $pattern = 'path';

        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => $req_uri
        ));

        $this->assertEquals($expected_uri, Url::addLangCode($req_uri, $store, $lang, $headers));
    }

    public function testAddLangCodeRelativePathWithQueryPattern()
    {
        $uri = '/index.php';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "/test?wovn=$lang"
        ));

        $this->assertEquals("$uri?wovn=$lang", Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeRelativePathAndAnchorWithQueryPattern()
    {
        $uri = '/index.php#test';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "/test?wovn=$lang"
        ));

        $this->assertEquals("/index.php?wovn=$lang#test", Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeRelativePathWithLangCodeInsideAndQueryPattern()
    {
        $uri = '/index.php?wovn=fr';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "/test?wovn=$lang"
        ));

        $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPURLOfDifferentHostWithSubdomainPattern()
    {
        $uri = 'http://google.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://$lang.my-site.com/test"
        ));

        $this->assertEquals('http://google.com/index.php', Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPURLOfDifferentHostWithPseudoLangCodeAndSubdomainPattern()
    {
        $uri = 'http://fr.google.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://$lang.my-site.com/test"
        ));

        $this->assertEquals('http://fr.google.com/index.php', Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPURLWithSubdomainPattern()
    {
        $uri = 'http://my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://$lang.my-site.com/test"
        ));

        $this->assertEquals('http://fr.my-site.com/index.php', Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPURLWithLangCodeInsideAndSubdomainPattern()
    {
        $uri = 'http://fr.my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://$lang.my-site.com/test"
        ));

        $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPURLWithPathPattern()
    {
        $uri = 'http://my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/$lang/test"
        ));

        $this->assertEquals('http://my-site.com/fr/index.php', Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPURLWithLangCodeInsideAndPathPattern()
    {
        $uri = 'http://my-site.com/fr/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/$lang/test"
        ));

        $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPURLWithQueryPattern()
    {
        $uri = 'http://my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
        ));

        $this->assertEquals("$uri?wovn=$lang", Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPURLAndAnchorWithQueryPattern()
    {
        $uri = 'http://my-site.com/index.php#test';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
        ));

        $this->assertEquals("http://my-site.com/index.php?wovn=$lang#test", Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPURLWithLangCodeInsideAndQueryPattern()
    {
        $uri = 'http://my-site.com/index.php?wovn=fr';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
        ));

        $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPSURLOfDifferentHostWithSubdomainPattern()
    {
        $uri = 'https://google.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://$lang.my-site.com/test"
        ));

        $this->assertEquals('https://google.com/index.php', Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPSURLOfDifferentHostWithPseudoLangCodeAndSubdomainPattern()
    {
        $uri = 'https://fr.google.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://$lang.my-site.com/test"
        ));

        $this->assertEquals('https://fr.google.com/index.php', Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPSURLWithSubdomainPattern()
    {
        $uri = 'https://my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://$lang.my-site.com/test"
        ));

        $this->assertEquals('https://fr.my-site.com/index.php', Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndSubdomainPattern()
    {
        $uri = 'https://fr.my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://$lang.my-site.com/test"
        ));

        $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPSURLWithPathPattern()
    {
        $uri = 'https://my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/$lang/test"
        ));

        $this->assertEquals('https://my-site.com/fr/index.php', Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndPathPattern()
    {
        $uri = 'https://my-site.com/fr/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/$lang/test"
        ));

        $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPSURLWithQueryPattern()
    {
        $uri = 'https://my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/test?wovn=$lang"
        ));

        $this->assertEquals("$uri?wovn=$lang", Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPSURLAndAnchorWithQueryPattern()
    {
        $uri = 'https://my-site.com/index.php#test';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/test?wovn=$lang"
        ));

        $this->assertEquals("https://my-site.com/index.php?wovn=$lang#test", Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndQueryPattern()
    {
        $uri = 'https://my-site.com/index.php?wovn=fr';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/test?wovn=$lang"
        ));

        $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteURLAndPortWithSubdomainPattern()
    {
        $uri = 'https://my-site.com:3000/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'HTTP_HOST' => "$lang.my-site.com:3000",
            'REQUEST_URI' => "https://$lang.my-site.com:3000/test"
        ));

        $this->assertEquals("https://$lang.my-site.com:3000/index.php", Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteURLAndPortWithPathPattern()
    {
        $uri = 'https://my-site.com:3000/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'HTTP_HOST' => 'my-site.com:3000',
            'REQUEST_URI' => "https://my-site.com:3000/$lang/test"
        ));

        $this->assertEquals("https://my-site.com:3000/$lang/index.php", Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeAbsoluteURLAndPortWithQueryPattern()
    {
        $uri = 'https://my-site.com:3000/index.php';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'HTTP_HOST' => 'my-site.com:3000',
            'REQUEST_URI' => "https://my-site.com:3000/test?wovn=$lang"
        ));

        $this->assertEquals("$uri?wovn=fr", Url::addLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeRelativePathWithLangCodeInsideAndPathPattern()
    {
        $uri = '/fr-test/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "/$lang/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals($uri, Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeRelativePathWithQueryPattern()
    {
        $uri = '/index.php';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "/test?wovn=$lang"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals("$uri?wovn=fr-test", Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeRelativePathAndAnchorWithQueryPattern()
    {
        $uri = '/index.php#test';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "/test?wovn=$lang"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals("/index.php?wovn=fr-test#test", Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeRelativePathWithLangCodeInsideAndQueryPattern()
    {
        $uri = '/index.php?wovn=fr-test';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "/test?wovn=$lang"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals($uri, Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLOfDifferentHostWithSubdomainPattern()
    {
        $uri = 'http://google.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://$lang.my-site.com/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals('http://google.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLOfDifferentHostWithPseudoLangCodeAndSubdomainPattern()
    {
        $uri = 'http://fr.google.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://$lang.my-site.com/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals('http://fr.google.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithSubdomainPattern()
    {
        $uri = 'http://my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://$lang.my-site.com/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals('http://fr-test.my-site.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithLangCodeInsideAndSubdomainPattern()
    {
        $uri = 'http://fr-test.my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://$lang.my-site.com/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals('http://fr-test.my-site.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithDifferentLangCodeInsideAndSubdomainPattern()
    {
        $uri = 'http://fr.my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://$lang.my-site.com/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals('http://fr.my-site.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithPathPattern()
    {
        $uri = 'http://my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/$lang/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals('http://my-site.com/fr-test/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithLangCodeInsideAndPathPattern()
    {
        $uri = 'http://my-site.com/fr-test/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/$lang/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals($uri, Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithDifferentLangCodeInsideAndPathPattern()
    {
        $uri = 'http://my-site.com/fr/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/$lang/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals('http://my-site.com/fr-test/fr/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithQueryPattern()
    {
        $uri = 'http://my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals("$uri?wovn=fr-test", Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLAndAnchorWithQueryPattern()
    {
        $uri = 'http://my-site.com/index.php#test';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals("http://my-site.com/index.php?wovn=fr-test#test", Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithDifferentLangCodeInsideAndQueryPattern()
    {
        $uri = 'http://my-site.com/index.php?wovn=fr';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals('http://my-site.com/index.php?wovn=fr&wovn=fr-test', Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLOfDifferentHostWithSubdomainPattern()
    {
        $uri = 'https://google.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://$lang.my-site.com/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals('https://google.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLOfDifferentHostWithPseudoLangCodeAndSubdomainPattern()
    {
        $uri = 'https://fr-test.google.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://$lang.my-site.com/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals('https://fr-test.google.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLWithSubdomainPattern()
    {
        $uri = 'https://my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://$lang.my-site.com/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals('https://fr-test.my-site.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndSubdomainPattern()
    {
        $uri = 'https://fr-test.my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://$lang.my-site.com/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals($uri, Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLWithPathPattern()
    {
        $uri = 'https://my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/$lang/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals('https://my-site.com/fr-test/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndPathPattern()
    {
        $uri = 'https://my-site.com/fr-test/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/$lang/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals($uri, Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLWithQueryPattern()
    {
        $uri = 'https://my-site.com/index.php';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/test?wovn=fr-test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals("$uri?wovn=fr-test", Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLAndAnchorWithQueryPattern()
    {
        $uri = 'https://my-site.com/index.php#test';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/test?wovn=fr-test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals("https://my-site.com/index.php?wovn=fr-test#test", Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndQueryPattern()
    {
        $uri = 'https://my-site.com/index.php?wovn=fr-test';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/test?wovn=fr-test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals($uri, Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteURLAndPortWithSubdomainPattern()
    {
        $uri = 'https://my-site.com:3000/index.php';
        $lang = 'fr';
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'HTTP_HOST' => "fr-test.my-site.com:3000",
            'REQUEST_URI' => "https://fr-test.my-site.com:3000/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals("https://fr-test.my-site.com:3000/index.php", Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteURLAndPortWithPathPattern()
    {
        $uri = 'https://my-site.com:3000/index.php';
        $lang = 'fr';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'HTTP_HOST' => 'my-site.com:3000',
            'REQUEST_URI' => "https://my-site.com:3000/fr-test/test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals("https://my-site.com:3000/fr-test/index.php", Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeCustomLangCodeAbsoluteURLAndPortWithQueryPattern()
    {
        $uri = 'https://my-site.com:3000/index.php';
        $lang = 'fr';
        $pattern = 'query';
        list($store, $headers) = $this->getStarted($pattern, array(
            'HTTP_HOST' => 'my-site.com:3000',
            'REQUEST_URI' => "https://my-site.com:3000/test?wovn=fr-test"
        ));
        $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

        $this->assertEquals("$uri?wovn=fr-test", Url::AddLangCode($uri, $store, $lang, $headers));
    }

    public function testAddLangCodeWithSubdomainPattern()
    {
        $testCases = array(
            // $request_uri, $target_url, $lang, $expected_uri
            // path
            array('/req_uri/', '/dir/index.php', 'ja', 'http://ja.my-site.com/dir/index.php'),
            array('/req_uri/', '/', 'en', 'http://en.my-site.com/'),
            array('/req_uri/', '/dir', 'en', 'http://en.my-site.com/dir'),
            array('/req_uri/', '/dir/', 'en', 'http://en.my-site.com/dir/'),
            array('/req_uri/', '/index.php', 'en', 'http://en.my-site.com/index.php'),
            array('/req_uri/', '/dir/index.php', 'en', 'http://en.my-site.com/dir/index.php'),
            array('/req_uri/', '/dir/index.php?a=b', 'en', 'http://en.my-site.com/dir/index.php?a=b'),
            array('/req_uri/', '/dir/index.php#hash', 'en', 'http://en.my-site.com/dir/index.php#hash'),

            // schema
            // PHP 5.3 has issue of parse_url which can't parse the following URL.
            // array('/req_uri/', '//my-site.com/dir/index.php', 'en', '//en.my-site.com/dir/index.php'),
            array('/req_uri/', 'http://my-site.com/dir/index.php', 'en', 'http://en.my-site.com/dir/index.php'),
            array('/req_uri/', 'https://my-site.com/dir/index.php', 'en', 'https://en.my-site.com/dir/index.php'),

            // TODO: There are existing issues.
            // // relative url
            // array('/req_uri/', './dir/index.php', 'en', 'http://en.my-site.com/req_uri/dir/index.php'),
            // array('/req_uri/', '../dir/index.php', 'en', 'http://en.my-site.com/dir/index.php'),
            // array('/req_uri/index.php', './dir/index.php', 'en', 'http://en.my-site.com/req_uri/dir/index.php'),
            // array('/req_uri/index.php', '../dir/index.php', 'en', 'http://en.my-site.com/dir/index.php'),
            // array('/req_uri/sub_dir/', './dir/index.php', 'en', 'http://en.my-site.com/req_uri/sub_dir/dir/index.php'),
            // array('/req_uri/sub_dir/', '../dir/index.php', 'en', 'http://en.my-site.com/req_uri/dir/index.php'),
        );

        foreach ($testCases as $case) {
            list($request_uri, $target_url, $lang, $expected_uri) = $case;
            $settings = array(
                'project_token' => 'T0k3N',
                'default_lang' =>  'ja',
                'supported_langs' => array('en'),
                'url_pattern_name' => 'subdomain',
            );
            $additional_env = array(
                'REQUEST_URI' => $request_uri
            );
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $additional_env);
            $this->assertEquals($expected_uri, Url::AddLangCode($target_url, $store, $lang, $headers), "request_uri->[{$request_uri}] target_url->[{$target_url}]");
        };
    }

    public function testAddLangCodeWithPathPattern()
    {
        $testCases = array(
            // path
            array('/req_uri/', '/dir/index.php', 'ja', '/ja/dir/index.php'),
            array('/req_uri/', '/', 'en', '/en/'),
            array('/req_uri/', '/index.php', 'en', '/en/index.php'),
            array('/req_uri/', '/dir/', 'en', '/en/dir/'),
            array('/req_uri/', '/dir/index.php', 'en', '/en/dir/index.php'),
            array('/req_uri/', '/dir/index.php?a=b', 'en', '/en/dir/index.php?a=b'),
            array('/req_uri/', '/dir/index.php#hash', 'en', '/en/dir/index.php#hash'),

            // schema
            // PHP 5.3 has issue of parse_url which can't parse the following URL.
            // array('/req_uri/', '//my-site.com/dir/index.php', 'en', '//my-site.com/en/dir/index.php'),
            array('/req_uri/', 'http://my-site.com/dir/index.php', 'en', 'http://my-site.com/en/dir/index.php'),
            array('/req_uri/', 'https://my-site.com/dir/index.php', 'en', 'https://my-site.com/en/dir/index.php'),

            // TODO: There are existing issues.
            // // relative url
            // array('/req_uri/', './dir/index.php', 'en', '/req_uri/dir/index.php'),
            // array('/req_uri/', '../dir/index.php', 'en', '/dir/index.php'),
            // array('/req_uri/index.php', './dir/index.php', 'en', '/req_uri/dir/index.php'),
            // array('/req_uri/index.php', '../dir/index.php', 'en', '/dir/index.php'),
            // array('/req_uri/sub_dir/', './dir/index.php', 'en', '/req_uri/sub_dir/dir/index.php'),
            // array('/req_uri/sub_dir/', '../dir/index.php', 'en', '/req_uri/dir/index.php'),
        );

        foreach ($testCases as $case) {
            list($request_uri, $target_url, $lang, $expected_uri) = $case;
            $settings = array(
                'project_token' => 'T0k3N',
                'default_lang' =>  'ja',
                'supported_langs' => array('en'),
                'url_pattern_name' => 'path'
            );
            $additional_env = array(
                'REQUEST_URI' => $request_uri
            );
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $additional_env);
            $this->assertEquals($expected_uri, Url::AddLangCode($target_url, $store, $lang, $headers), "request_uri->[{$request_uri}] target_url->[{$target_url}]");
        };
    }

    public function testAddLangCodeWithSitePrefixPathAndPathPattern()
    {
        $testCases = array(
            // path
            array('/req_uri/', '/dir/', 'en', 'dir', '/dir/en/'),
            array('/req_uri/', '/dir/?a=b', 'en', 'dir', '/dir/en/?a=b'),
            array('/req_uri/', '/dir/#hash', 'en', 'dir', '/dir/en/#hash'),
            array('/req_uri/', '/dir/index.php', 'en', 'dir', '/dir/en/index.php'),
            array('/req_uri/', '/dir/index.php', 'ja', 'dir', '/dir/ja/index.php'),
            array('/req_uri/', '/dir/dir2/', 'en', 'dir', '/dir/en/dir2/'),
            array('/req_uri/', '/dir/dir2/index.php', 'en', 'dir', '/dir/en/dir2/index.php'),
            array('/req_uri/', '/dir/dir2/index.php?a=b', 'en', 'dir', '/dir/en/dir2/index.php?a=b'),
            array('/req_uri/', '/dir/dir2/index.php#hash', 'en', 'dir', '/dir/en/dir2/index.php#hash'),
            array('/req_uri/', '/dir/dir2/index.php', 'en', 'dir', '/dir/en/dir2/index.php'),
            array('/req_uri/', '/dir/dir2/index.php', 'en', 'dir', '/dir/en/dir2/index.php'),

            // schema
            // PHP 5.3 has issue of parse_url which can't parse the following URL.
            // array('/req_uri/', '//my-site.com/dir/', 'en', 'dir', '//my-site.com/dir/en/'),
            array('/req_uri/', 'http://my-site.com/dir/', 'en', 'dir', 'http://my-site.com/dir/en/'),
            array('/req_uri/', 'https://my-site.com/dir/', 'en', 'dir', 'https://my-site.com/dir/en/'),

            // site prefix path
            array('/req_uri/', '/dir/dir2/', 'en', 'dir/dir2', '/dir/dir2/en/'),
            array('/req_uri/', '/dir/dir2/', 'en', '/dir/dir2', '/dir/dir2/en/'),
            array('/req_uri/', '/dir/dir2/', 'en', '/dir/dir2/', '/dir/dir2/en/'),
            array('/req_uri/', '/dir/dir2/index.php', 'en', 'dir/dir2', '/dir/dir2/en/index.php'),
            array('/req_uri/', '/dir/dir2/index.php', 'en', '/dir/dir2', '/dir/dir2/en/index.php'),
            array('/req_uri/', '/dir/dir2/index.php', 'en', '/dir/dir2/', '/dir/dir2/en/index.php'),

            // should not add
            array('/req_uri/', '/', 'en', 'dir', '/'),
            array('/req_uri/', '/index.php', 'en', 'dir', '/index.php'),
            array('/req_uri/', '/dir', 'en', 'dir/dir2', '/dir'),
            array('/req_uri/', '/dir/index.php', 'en', 'dir/dir2', '/dir/index.php')
        );

        foreach ($testCases as $case) {
            list($request_uri, $target_url, $lang, $site_prefix_path, $expected_uri) = $case;
            $settings = array(
                'project_token' => 'T0k3N',
                'default_lang' =>  'ja',
                'supported_langs' => array('en'),
                'url_pattern_name' => 'path',
                'site_prefix_path' => $site_prefix_path
            );
            $additional_env = array(
                'REQUEST_URI' => $request_uri
            );
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $additional_env);
            $this->assertEquals($expected_uri, Url::AddLangCode($target_url, $store, $lang, $headers), "target_url->[{$target_url}] expected_uri->[{$expected_uri}]");
        };
    }

    public function testAddLangCodeWithCustomDomainLangs()
    {
        $custom_domain_langs = array(
            'en' => array('url' => 'my-site.com'),
            'en-US' => array('url' => 'en-us.my-site.com'),
            'ja' => array('url' => 'my-site.com/ja'),
            'zh-CHS' => array('url' => 'my-site.com/zh/chs'),
            'zh-Hant-HK' => array('url' => 'zh-hant-hk.com/zh'),
        );
        $testCases = array(
            // no_lang_url, lang_code, expected_url
            // absolute URL
            array('https://my-site.com', 'en', 'https://my-site.com'),
            array('https://my-site.com', 'ja', 'https://my-site.com/ja'),
            array('https://my-site.com/index.php', 'ja', 'https://my-site.com/ja/index.php'),
            array('https://my-site.com/a/b/', 'ja', 'https://my-site.com/ja/a/b/'),
            array('https://my-site.com/a/b/index.php', 'ja', 'https://my-site.com/ja/a/b/index.php'),
            array('https://my-site.com/index.php', 'en-US', 'https://en-us.my-site.com/index.php'),
            array('https://my-site.com/index.php', 'zh-CHS', 'https://my-site.com/zh/chs/index.php'),
            array('https://my-site.com/index.php', 'zh-Hant-HK', 'https://zh-hant-hk.com/zh/index.php'),
            array('https://my-site.com/index.php?a=1&b=2', 'zh-Hant-HK', 'https://zh-hant-hk.com/zh/index.php?a=1&b=2'),
            array('https://my-site.com/index.php#hash', 'zh-Hant-HK', 'https://zh-hant-hk.com/zh/index.php#hash'),
            array('https://my-site.com/index.php?a=1&b=2#hash', 'zh-Hant-HK', 'https://zh-hant-hk.com/zh/index.php?a=1&b=2#hash'),

            // absolute path
            array('/', 'en', '/'),
            array('/', 'ja', '/ja/'),
            array('/index.php', 'ja', '/ja/index.php'),
            array('/a/b/', 'ja', '/ja/a/b/'),
            array('/a/b/index.php', 'ja', '/ja/a/b/index.php'),
            array('/index.php', 'en-US', '/index.php'),
            array('/index.php', 'zh-CHS', '/zh/chs/index.php'),
            array('/index.php', 'zh-Hant-HK', '/zh/index.php'),
            array('/index.php?a=1&b=2', 'zh-Hant-HK', '/zh/index.php?a=1&b=2'),
            array('/index.php#hash', 'zh-Hant-HK', '/zh/index.php#hash'),
            array('/index.php?a=1&b=2#hash', 'zh-Hant-HK', '/zh/index.php?a=1&b=2#hash'),

            // other patterns should be keep original
            array('a=1&b=2', 'zh-Hant-HK', 'a=1&b=2'),
            array('#hash', 'zh-Hant-HK', '#hash')
        );

        $settings = array(
            'project_token' => 'T0k3N',
            'default_lang' =>  'en',
            'supported_langs' => array('en'),
            'url_pattern_name' => 'custom_domain',
            'custom_domain_langs' => $custom_domain_langs
        );
        $additional_env = array(
            'HTTP_HOST' => 'my-site.com',
            'REQUEST_URI' => '/req_uri/'
        );

        foreach ($testCases as $case) {
            list($no_lang_url, $lang, $expected_uri) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $additional_env);

            $this->assertEquals('en', $headers->requestLang());
            $this->assertEquals($expected_uri, Url::addLangCode($no_lang_url, $store, $lang, $headers), "no_lang_url->[{$no_lang_url}] lang->[{$lang}] expected_uri->[{$expected_uri}]");
        }
    }

    public function testRemoveLangCodeWithCustomDomainLangs()
    {
        $custom_domain_langs = array(
            'en' => array('url' => 'my-site.com'),
            'en-US' => array('url' => 'en-us.my-site.com'),
            'ja' => array('url' => 'my-site.com/ja'),
            'zh-CHS' => array('url' => 'my-site.com/zh/chs'),
            'zh-Hant-HK' => array('url' => 'zh-hant-hk.com/zh'),
        );
        $testCases = array(
            // $target_uri, $lang, $expected_uri, $env
            // absolute URL
            array('https://my-site.com', 'en', 'https://my-site.com', array()),
            array('https://my-site.com/ja', 'ja', 'https://my-site.com', array('REQUEST_URI' => '/ja')),
            array('https://my-site.com/ja/index.php', 'ja', 'https://my-site.com/index.php', array('REQUEST_URI' => '/ja/index.php')),
            array('https://my-site.com/ja/a/b/', 'ja', 'https://my-site.com/a/b/', array('REQUEST_URI' => '/ja/a/b/')),
            array('https://my-site.com/ja/a/b/index.php', 'ja', 'https://my-site.com/a/b/index.php', array('REQUEST_URI' => '/ja/a/b/index.php')),
            array('https://en-us.my-site.com/index.php', 'en-US', 'https://my-site.com/index.php', array('HTTP_HOST' => 'en-us.my-site.com', 'SERVER_NAME' => 'en-us.my-site.com', 'REQUEST_URI' => '/index.php')),
            array('https://my-site.com/zh/chs/index.php', 'zh-CHS', 'https://my-site.com/index.php', array('REQUEST_URI' => '/zh/chs/index.php')),
            array('https://zh-hant-hk.com/zh/index.php', 'zh-Hant-HK', 'https://my-site.com/index.php', array('HTTP_HOST' => 'zh-hant-hk.com', 'SERVER_NAME' => 'zh-hant-hk.com', 'REQUEST_URI' => '/zh/index.php')),
            array('https://zh-hant-hk.com/zh/index.php?a=1&b=2', 'zh-Hant-HK', 'https://my-site.com/index.php?a=1&b=2', array('HTTP_HOST' => 'zh-hant-hk.com', 'SERVER_NAME' => 'zh-hant-hk.com', 'REQUEST_URI' => '/zh/index.php')),
            array('https://zh-hant-hk.com/zh/index.php#hash', 'zh-Hant-HK', 'https://my-site.com/index.php#hash', array('HTTP_HOST' => 'zh-hant-hk.com', 'SERVER_NAME' => 'zh-hant-hk.com', 'REQUEST_URI' => '/zh/index.php')),
            array('https://zh-hant-hk.com/zh/index.php?a=1&b=2#hash', 'zh-Hant-HK', 'https://my-site.com/index.php?a=1&b=2#hash', array('HTTP_HOST' => 'zh-hant-hk.com', 'SERVER_NAME' => 'zh-hant-hk.com', 'REQUEST_URI' => '/zh/index.php')),

            // absolute path
            array('/', 'en', '/', array()),
            array('/ja/', 'ja', '/', array('REQUEST_URI' => '/ja')),
            array('/ja/index.php', 'ja', '/index.php', array('REQUEST_URI' => '/ja/index.php')),
            array('/ja/a/b/', 'ja', '/a/b/', array('REQUEST_URI' => '/ja/a/b/')),
            array('/ja/a/b/index.php', 'ja', '/a/b/index.php', array('REQUEST_URI' => '/ja/a/b/index.php')),
            array('/index.php', 'en-US', '/index.php', array('HTTP_HOST' => 'en-us.my-site.com', 'SERVER_NAME' => 'en-us.my-site.com', 'REQUEST_URI' => '/index.php')),
            array('/zh/chs/index.php', 'zh-CHS', '/index.php', array('REQUEST_URI' => '/zh/chs/index.php')),
            array('/zh/index.php', 'zh-Hant-HK', '/index.php', array('HTTP_HOST' => 'zh-hant-hk.com', 'SERVER_NAME' => 'zh-hant-hk.com', 'REQUEST_URI' => '/zh/index.php')),
            array('/zh/index.php?a=1&b=2', 'zh-Hant-HK', '/index.php?a=1&b=2', array('HTTP_HOST' => 'zh-hant-hk.com', 'SERVER_NAME' => 'zh-hant-hk.com', 'REQUEST_URI' => '/zh/index.php')),
            array('/zh/index.php#hash', 'zh-Hant-HK', '/index.php#hash', array('HTTP_HOST' => 'zh-hant-hk.com', 'SERVER_NAME' => 'zh-hant-hk.com', 'REQUEST_URI' => '/zh/index.php')),
            array('/zh/index.php?a=1&b=2#hash', 'zh-Hant-HK', '/index.php?a=1&b=2#hash', array('HTTP_HOST' => 'zh-hant-hk.com', 'SERVER_NAME' => 'zh-hant-hk.com', 'REQUEST_URI' => '/zh/index.php')),

            // other patterns should be keep original
            array('a=1&b=2', 'en-US', 'a=1&b=2', array('HTTP_HOST' => 'en-us.my-site.com', 'SERVER_NAME' => 'en-us.my-site.com', 'REQUEST_URI' => '/')),
            array('#hash', 'en-US', '#hash', array('HTTP_HOST' => 'en-us.my-site.com', 'SERVER_NAME' => 'en-us.my-site.com', 'REQUEST_URI' => '/'))
        );

        $settings = array(
            'project_token' => 'T0k3N',
            'default_lang' =>  'en',
            'supported_langs' => array('en', 'en-US', 'ja', 'zh-CHS', 'zh-Hant-HK'),
            'url_pattern_name' => 'custom_domain',
            'custom_domain_langs' => $custom_domain_langs
        );
        $base_env = array(
            'HTTP_HOST' => 'my-site.com',
            'REQUEST_URI' => '/req_uri/'
        );

        foreach ($testCases as $case) {
            list($target_uri, $lang, $expected_uri, $env) = $case;
            $additional_env = empty($env) ? $base_env : array_merge($base_env, $env);
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $additional_env);

            $this->assertEquals($lang, $headers->requestLang());
            $this->assertEquals($expected_uri, Url::removeLangCode($target_uri, $lang, $store, $headers), "target_url->[{$target_uri}] lang->[{$lang}] expected_uri->[{$expected_uri}]");
        }
    }

    public function testRemoveLangCodeRelativePathWithPathPattern()
    {
        $lang = 'en';
        $expected_uri = '/index.php';
        $uri = "/$lang$expected_uri";
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(
            'default_lang' => 'ja',
            'supported_langs' => array('en'),
            'url_pattern_name' => 'path',
        ), array(
            'REQUEST_URI' => "/$lang/test"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeRelativePathWithLangCodeNotInsideAndPathPattern()
    {
        $lang = 'fr';
        $expected_uri = '/index.php';
        $uri = "$expected_uri";
        $pattern = 'path';
        $lang_param_name = 'wovn';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "/$lang/test"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeRelativePathWithQueryPattern()
    {
        $lang = 'fr';
        $expected_uri = '/index.php';
        $uri = "$expected_uri?wovn=$lang";
        $pattern = 'query';
        $lang_param_name = 'wovn';
        list($store, $headers) = $this->getStarted(
            $pattern,
            array('REQUEST_URI' => "/test?wovn=$lang")
        );

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeRelativePathWithQueryPatternAndCustomLangParamName()
    {
        $lang = 'fr';
        $expected_uri = '/index.php';
        $uri = "$expected_uri?language=$lang";
        $pattern = 'query';
        $lang_param_name = 'language';
        list($store, $headers) = $this->getStarted(
            $pattern,
            array('REQUEST_URI' => "/test?language=$lang"),
            array('lang_param_name' => 'language')
        );

        $this->assertEquals('fr', $headers->requestLang());
        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeRelativePathWithLangCodeNotInsideAndQueryPattern()
    {
        $lang = 'fr';
        $expected_uri = '/index.php';
        $uri = "$expected_uri?wovn=$lang";
        $pattern = 'query';
        $lang_param_name = 'wovn';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "/test?wovn=$lang"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($expected_uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeAbsoluteHTTPURLWithSubdomainPattern()
    {
        $lang = 'fr';
        $expected_uri = 'http://my-site.com/index.php';
        $uri = "http://$lang.my-site.com/index.php";
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://$lang.my-site.com/test"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeAbsoluteHTTPURLWithLangCodeNotInsideAndSubdomainPattern()
    {
        $lang = 'fr';
        $expected_uri = 'http://my-site.com/index.php';
        $uri = $expected_uri;
        $pattern = 'subdomain';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://$lang.my-site.com/test"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeAbsoluteHTTPURLWithPathPattern()
    {
        $lang = 'fr';
        $expected_url = 'http://my-site.com/index.php';
        $uri = 'http://my-site.com/fr/index.php';
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/$lang/test"
        ));

        $this->assertEquals($expected_url, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeAbsoluteHTTPURLWithLangCodeNotInsideAndPathPattern()
    {
        $lang = 'fr';
        $expected_url = 'http://my-site.com/index.php';
        $uri = $expected_url;
        $pattern = 'path';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/$lang/test"
        ));

        $this->assertEquals($expected_url, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeAbsoluteHTTPURLWithQueryPattern()
    {
        $lang = 'fr';
        $expected_uri = 'http://my-site.com/index.php';
        $uri = "$expected_uri?wovn=$lang";
        $pattern = 'query';
        $lang_param_name = 'wovn';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeAbsoluteHTTPURLWithLangCodeNotInsideAndQueryPattern()
    {
        $lang = 'fr';
        $expected_uri = 'http://my-site.com/index.php';
        $uri = $expected_uri;
        $pattern = 'query';
        $lang_param_name = 'wovn';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeAbsoluteHTTPSURLWithSubdomainPattern()
    {
        $lang = 'fr';
        $expected_uri = 'https://my-site.com/index.php';
        $uri = 'https://fr.my-site.com/index.php';
        $pattern = 'subdomain';
        $lang_param_name = 'wovn';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://$lang.my-site.com/test"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeAbsoluteHTTPSURLWithLangCodeNotInsideAndSubdomainPattern()
    {
        $lang = 'fr';
        $expected_uri = 'https://my-site.com/index.php';
        $uri = $expected_uri;
        $pattern = 'subdomain';
        $lang_param_name = 'wovn';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://$lang.my-site.com/test"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeAbsoluteHTTPSURLWithPathPattern()
    {
        $lang = 'fr';
        $expected_uri = 'https://my-site.com/index.php';
        $uri = 'https://my-site.com/fr/index.php';
        $pattern = 'path';
        $lang_param_name = 'wovn';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/$lang/test"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeAbsoluteHTTPSURLWithLangCodeNotInsideAndPathPattern()
    {
        $lang = 'fr';
        $expected_uri = 'https://my-site.com/index.php';
        $uri = $expected_uri;
        $pattern = 'path';
        $lang_param_name = 'wovn';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/$lang/test"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeWithSitePrefixPathAndPathPattern()
    {
        $pattern = 'path';
        $testCases = array(
            // should remove
            array('/dir/ja/index.php', 'ja', 'dir', '/dir/index.php'),
            array('/dir/en/index.php', 'en', 'dir', '/dir/index.php'),
            array('/dir/en/test/index.php', 'en', 'dir', '/dir/test/index.php'),
            array('/dir/en/test/index.php?a=b', 'en', 'dir', '/dir/test/index.php?a=b'),
            array('/dir/en/test/index.php#hash', 'en', 'dir', '/dir/test/index.php#hash'),
            array('http://testsite.com/dir/en/index.php', 'en', 'dir', 'http://testsite.com/dir/index.php'),
            array('https://testsite.com/dir/en/index.php', 'en', 'dir', 'https://testsite.com/dir/index.php'),
            array('https://testsite.com/dir1/dir2/en/index.php', 'en', 'dir1/dir2', 'https://testsite.com/dir1/dir2/index.php'),

            // should not remove
            array('/index.php', 'en', 'dir', '/index.php'),
            array('/dir/index.php', 'en', 'dir', '/dir/index.php'),
            array('/ja/index.php', 'ja', 'dir', '/ja/index.php'),
            array('/ja/dir/index.php', 'ja', 'dir', '/ja/dir/index.php'),
        );

        foreach ($testCases as $case) {
            list($target_url, $remove_lang, $site_prefix_path, $expected_uri) = $case;
            $settings = array(
                'project_token' => 'T0k3N',
                'default_lang' =>  'en',
                'url_pattern_name' => $pattern,
                'site_prefix_path' => $site_prefix_path
            );
            $additional_env = array(
                'REQUEST_URI' => "https://my-site.com/$site_prefix_path/en"
            );
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $additional_env);
            $this->assertEquals($expected_uri, Url::removeLangCode($target_url, $remove_lang, $store, $headers));
        };
    }

    public function testRemoveLangCodeAbsoluteHTTPSURLWithQueryPattern()
    {
        $lang = 'fr';
        $expected_uri = 'https://my-site.com/index.php';
        $uri = "$expected_uri?wovn=$lang";
        $pattern = 'query';
        $lang_param_name = 'wovn';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/test?wovn=$lang"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeAbsoluteHTTPSURLWithLangCodeNotInsideAndQueryPattern()
    {
        $lang = 'fr';
        $expected_uri = 'https://my-site.com/index.php';
        $uri = $expected_uri;
        $pattern = 'query';
        $lang_param_name = 'wovn';
        list($store, $headers) = $this->getStarted($pattern, array(
            'REQUEST_URI' => "https://my-site.com/test?wovn=fr$lang"
        ));

        $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
    }

    public function testRemoveLangCodeCustomDefaultLangAlias()
    {
        $testCases = array(
            // lang, expected_uri, uri, pattern
            array('custom-en', 'https://my-site.com/', 'https://my-site.com/custom-en/', 'path'),
            array('custom-en', 'https://my-site.com/', 'https://my-site.com/', 'path'),
            array('en', 'https://my-site.com/', 'https://my-site.com/', 'path'),
            array('en', 'https://my-site.com/', 'https://my-site.com/', 'query'),
            array('en', 'https://my-site.com/', 'https://my-site.com/', 'subdomain'),
            array('en', 'https://my-site.com', 'https://my-site.com', 'path'),
            array('en', 'https://my-site.com', 'https://my-site.com', 'query'),
            array('en', 'https://my-site.com', 'https://my-site.com', 'subdomain'),
        );
        foreach ($testCases as $case) {
            list($lang, $expected_uri, $uri, $pattern) = $case;
            list($store, $headers) = $this->getStarted($pattern);
            $this->assertEquals($expected_uri, Url::removeLangCode($uri, $lang, $store, $headers));
        }
    }

    public function testshouldIgnoreBySitePrefixPath()
    {
        $testCases = array(
            // should ignore when URL matches SitePrefixPath
            array('path', 'dir', 'https://google.com', true),
            array('path', 'dir', 'https://google.com/', true),

            // should not ignore when URL pattern is not path
            array('', '', 'https://google.com/', false),
            array(null, '', 'https://google.com/', false),
            array('subdomain', '', 'https://google.com/', false),
            array('query', '', 'https://google.com/', false),

            // should not ignore when SitePrefixPath is empty
            array('path', null, 'https://google.com/', false),
            array('path', '', 'https://google.com/', false),

            // should not ignore when URL matches SitePrefixPath
            array('path', 'dir', '//google.com/dir', false),
            array('path', 'dir', '/dir', false),
            array('path', 'dir', 'https://google.com/dir', false),
            array('path', '/dir', 'https://google.com/dir', false),
            array('path', 'dir/', 'https://google.com/dir', false),
            array('path', 'dir1/dir2', 'https://google.com/dir1/dir2', false),
            array('path', '/dir1/dir2', 'https://google.com/dir1/dir2', false),
            array('path', 'dir1/dir2/', 'https://google.com/dir1/dir2', false),
            array('path', '/dir1/dir2/', 'https://google.com/dir1/dir2', false)
        );

        foreach ($testCases as $case) {
            list($url_pattern_name, $site_prefix_path, $uri, $expected) = $case;
            $settings = array(
                'project_token' => 'T0k3N',
                'default_lang' =>  'en',
                'url_pattern_name' => $url_pattern_name,
                'site_prefix_path' => $site_prefix_path
            );
            $additional_env = array(
                'REQUEST_URI' => "https://my-site.com/$site_prefix_path/fr"
            );
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $additional_env);
            $this->assertEquals($expected, Url::shouldIgnoreBySitePrefixPath($uri, $store->settings));
        }
    }
}
