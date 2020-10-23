<?php


namespace Wovnio\Wovnphp\Tests\Unit;

require_once 'src/wovnio/wovnphp/CookieLang.php';

use Wovnio\Test\Helpers\StoreAndHeadersFactory;
use Wovnio\Wovnphp\CookieLang;

class CookieLangTest extends \PHPUnit_Framework_TestCase
{
    private function getHeaderStoreQueryPattern($cookieLang, $requestLang, $useCookieLang = true)
    {
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'ja', 'fr'),
            'url_pattern_name' => 'query',
            'lang_param_name' => 'wovn',
            'project_token' => '123456',
            'use_cookie_lang' => $useCookieLang
        );
        $env = array(
            'REQUEST_URI' => "/example/product.html?wovn={$requestLang}"
        );
        $cookies = array(
            CookieLang::COOKIE_LANG_NAME => $cookieLang
        );
        return StoreAndHeadersFactory::fromFixture('default', $settings, $env, $cookies);
    }

    private function getHeaderStorePathPattern($cookieLang, $requestLang, $useCookieLang = true)
    {
        $settings = array(
            'default_lang' => 'en',
            'supported_langs' => array('en', 'ja', 'fr'),
            'url_pattern_name' => 'path',
            'lang_param_name' => 'wovn',
            'project_token' => '123456',
            'use_cookie_lang' => $useCookieLang
        );
        $env = array(
            'REQUEST_URI' => "/{$requestLang}/example/product.html"
        );
        $cookies = array(
            CookieLang::COOKIE_LANG_NAME => $cookieLang
        );
        return StoreAndHeadersFactory::fromFixture('default', $settings, $env, $cookies);
    }

    public function testRequestToTargetLang_WithTargetCookie_QueryPattern_ShouldNotRedirect()
    {
        list($store, $headers) = $this->getHeaderStoreQueryPattern('ja', 'fr');
        $this->assertEquals(false, $headers->shouldRedirect());
    }

    public function testRequestToSameLang_WithCookie_QueryPattern_ShouldNotRedirect()
    {
        list($store, $headers) = $this->getHeaderStoreQueryPattern('ja', 'ja');
        $this->assertEquals(false, $headers->shouldRedirect());
    }

    public function testFeatureDisabled_ShouldNotRedirect()
    {
        list($store, $headers) = $this->getHeaderStoreQueryPattern('ja', 'ja', false);
        $this->assertEquals(false, $headers->shouldRedirect());
    }

    public function testRequestToDefaultLang_WithTargetCookie_ShouldRedirect()
    {
        list($store, $headers) = $this->getHeaderStoreQueryPattern('ja', 'en');
        $this->assertEquals(true, $headers->shouldRedirect());
    }

    public function testRequestToTargetLang_WithTargetCookie_PathPattern_ShouldNotRedirect()
    {
        list($store, $headers) = $this->getHeaderStorePathPattern('ja', 'fr');
        $this->assertEquals(false, $headers->shouldRedirect());
    }

    public function testRequestToSameLang_WithCookie_PathPattern_ShouldNotRedirect()
    {
        list($store, $headers) = $this->getHeaderStorePathPattern('ja', 'ja');
        $this->assertEquals(false, $headers->shouldRedirect());
    }

    public function testRequestToDefaultLang_WithTargetCookie_PathPattern_ShouldRedirect()
    {
        list($store, $headers) = $this->getHeaderStorePathPattern('ja', 'en');
        $this->assertEquals(true, $headers->shouldRedirect());
    }
}
