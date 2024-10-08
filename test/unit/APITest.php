<?php
namespace Wovnio\Wovnphp\Tests\Unit;

require_once 'test/helpers/StoreAndHeadersFactory.php';
require_once 'test/helpers/RequestHandlerMock.php';

require_once 'src/wovnio/wovnphp/API.php';
require_once 'src/wovnio/wovnphp/Utils.php';
require_once 'src/wovnio/wovnphp/Lang.php';
require_once 'src/wovnio/wovnphp/Url.php';
require_once 'src/wovnio/wovnphp/Store.php';
require_once 'src/wovnio/wovnphp/Headers.php';
require_once 'src/wovnio/wovnphp/Lang.php';
require_once 'src/wovnio/wovnphp/Url.php';
require_once 'src/wovnio/wovnphp/RequestOptions.php';
require_once 'src/wovnio/html/HtmlConverter.php';
require_once 'src/wovnio/html/HtmlReplaceMarker.php';
require_once 'src/wovnio/utils/request_handlers/RequestHandlerFactory.php';
require_once 'src/wovnio/utils/request_handlers/CurlRequestHandler.php';

require_once 'src/wovnio/modified_vendor/SimpleHtmlDom.php';

use Wovnio\Test\Helpers\StoreAndHeadersFactory;

use Wovnio\Wovnphp\API;
use Wovnio\Wovnphp\Utils;
use Wovnio\Wovnphp\RequestOptions;
use Wovnio\Html\HtmlConverter;
use Wovnio\Utils\RequestHandlers\RequestHandlerFactory;
use PHPUnit\Framework\TestCase;

class APITest extends TestCase
{
    protected function setUp()
    {
        RequestHandlerFactory::setInstance(null);
    }

    protected function tearDown()
    {
        RequestHandlerFactory::setInstance(null);
    }

    private function mockTranslationApi($response, $header = null, $error = null)
    {
        $mock = new RequestHandlerMock($response, $header, $error);
        RequestHandlerFactory::setInstance($mock);
        return $mock;
    }

    private function getExpectedApiUrl($store, $headers, $converted_html, $request_options)
    {
        $token = $store->settings['project_token'];
        $path = $headers->pathnameKeepTrailingSlash;
        $lang = $headers->requestLang();
        $body_hash = md5($converted_html);
        ksort($store->settings);
        $settings_hash = md5(serialize($store->settings));
        $cache_key_string = "(token=$token&settings_hash=$settings_hash&body_hash=$body_hash&path=$path&lang=$lang)";
        if ($request_options->getCacheDisableMode() || $request_options->getDebugMode()) {
            $cache_key_string = $cache_key_string . "&timestamp=" . time();
        }
        $cache_key = rawurlencode($cache_key_string);

        return $store->settings['api_url'] . '/v0/translation?cache_key=' . $cache_key;
    }

    private function getExpectedHtmlHeadContent($store, $headers, $lang_code_aliases_string = '[]')
    {
        $url = $headers->urlKeepTrailingSlash;
        $token = $store->settings['project_token'];
        $pattern = $store->settings['url_pattern_name'];
        $lang_param_name = $store->settings['lang_param_name'];
        $default_lang = $store->settings['default_lang'];
        $current_lang = $headers->requestLang();
        $version = WOVN_PHP_VERSION;
        $site_prefix_path = empty($store->settings['site_prefix_path']) ? '' : '&amp;sitePrefixPath=' . $store->settings['site_prefix_path'];

        return "<link rel=\"alternate\" hreflang=\"en\" href=\"$url\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=$token&amp;backend=true&amp;currentLang=$current_lang&amp;defaultLang=$default_lang&amp;urlPattern=$pattern&amp;langCodeAliases=$lang_code_aliases_string&amp;langParamName=$lang_param_name$site_prefix_path\" data-wovnio-info=\"version=WOVN.php_$version\" data-wovnio-type=\"fallback_snippet\" async></script>";
    }

    private function getExpectedData($store, $headers, $converted_body, $extra = array())
    {
        $data = array(
            'url' => $headers->urlKeepTrailingSlash,
            'browser_url' => $headers->originalUrl,
            'token' => $store->settings['project_token'],
            'lang_code' => $headers->requestLang(),
            'url_pattern' => $store->settings['url_pattern_name'],
            'lang_param_name' => $store->settings['lang_param_name'],
            'product' => WOVN_PHP_NAME,
            'version' => WOVN_PHP_VERSION,
            'body' => $converted_body,
            'insert_hreflangs' => json_encode($store->settings['insert_hreflangs']),
            'translate_canonical_tag' => $store->settings['translate_canonical_tag'],
            'preserve_relative_urls' => $store->settings['preserve_relative_urls'],
        );
        if (phpversion() >= '5.4') {
            // Should be int like 200 or 404, but from cli it will be false
            $data['page_status_code'] = false;
        }


        return array_merge($data, $extra);
    }

    public function testTranslationURL()
    {
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('japanese_path_request');
        $original_html = '<html></html>';
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers);
        $expected_html_before_send = "<html lang=\"en\">$expected_head_content</html>";
        $mock = $this->mockTranslationApi(null);
        $request_options = new RequestOptions(array(), false);
        $result = API::translate($store, $headers, $original_html, $request_options);

        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
    }

    public function testTranslationURLWithCacheInvalidation()
    {
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('japanese_path_request');
        $original_html = '<html></html>';
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers);
        $expected_html_before_send = "<html lang=\"en\">$expected_head_content</html>";
        $mock = $this->mockTranslationApi(null);
        $request_options = new RequestOptions(array('wovnCacheDisable' => ''), true);
        $result = API::translate($store, $headers, $original_html, $request_options);

        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
    }

    public function testTranslate()
    {
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');

        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $responsed_html = '<html><head></head><body><h1>response from html-swapper</h1></body></html>';
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers);
        $expected_html_before_send = "<html lang=\"en\"><head>$expected_head_content</head><body><h1>en</h1></body></html>";
        $response = json_encode(array("body" => $responsed_html));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals($responsed_html, $result);
        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals('POST', $method);
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send), $data);
        $this->assertEquals(1.0, $timeout, 'Should use 1.0 for non-search engine bot.');
    }

    public function testTranslateWithDebugMode()
    {
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');

        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $responsed_html = '<html><head></head><body><h1>response from html-swapper</h1></body></html>';
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers);
        $expected_html_before_send = "<html lang=\"en\"><head>$expected_head_content</head><body><h1>en</h1></body></html>";
        $response = json_encode(array("body" => $responsed_html));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array('wovnDebugMode' => ''), true);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send, array('debug_mode' => 'true')), $data);
    }

    public function testTranslateWithNoindexLangs()
    {
        $settings = array('no_index_langs' => array('en'));
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $responsed_html = '<html><head></head><body><h1>response from html-swapper</h1></body></html>';
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers);
        $expected_html_before_send = '<html lang="en">'.
        '<head>'.
        '<meta name="robots" content="noindex">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" data-wovnio-type="fallback_snippet" async></script>'.
        '</head>'.
        '<body><h1>en</h1>'.
        '</body></html>';
        $response = json_encode(array("body" => $responsed_html));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send, array('no_index_langs' => json_encode(array('en')))), $data, "should contain extra setting");
    }

    public function testTranslateWithNoHreflangLangs()
    {
        $settings = array('no_hreflang_langs' => array('en'));
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $responsed_html = '<html><head></head><body><h1>response from html-swapper</h1></body></html>';
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers);
        $expected_html_before_send = '<html lang="en">'.
        '<head>'.
        '<script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" data-wovnio-type="fallback_snippet" async></script>'.
        '</head>'.
        '<body><h1>en</h1>'.
        '</body></html>';
        $response = json_encode(array("body" => $responsed_html));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send, array('no_hreflang_langs' => json_encode(array('en')))), $data, "should contain extra setting");
    }

    public function testTranslateWithCustomLangAliases()
    {
        $settings = array('custom_lang_aliases' => array('ja' => 'ja-test'));
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $responsed_html = "<html><head></head><body><h1>response from html-swapper</h1></body></html>";
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers, '{&quot;ja&quot;:&quot;ja-test&quot;}');
        $expected_html_before_send = "<html lang=\"en\"><head>$expected_head_content</head><body><h1>en</h1></body></html>";
        $response = json_encode(array("body" => $responsed_html));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send, array('custom_lang_aliases' => '{"ja":"ja-test"}')), $data, "should contain snippet which include extra options");
        $this->assertEquals($responsed_html, $result);
    }

    public function testTranslateWithWovnIgnore()
    {
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $original_html = '<html><head></head><body><h1 wovn-ignore>en</h1>hello</body></html>';
        $responsed_html = '<html><head></head><body><h1 wovn-ignore><!-- __wovn-backend-ignored-key-0 --></h1>Bonjour</body></html>';
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers);
        $expected_html_before_send = "<html lang=\"en\"><head>$expected_head_content</head><body><h1 wovn-ignore><!-- __wovn-backend-ignored-key-0 --></h1>hello</body></html>";
        $response = json_encode(array("body" => $responsed_html));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals('<html><head></head><body><h1 wovn-ignore>en</h1>Bonjour</body></html>', $result);
        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send), $data, "should replace and replace back ignored contents");
    }

    public function testTranslateWithDataWovnIgnore()
    {
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $original_html = '<html><head></head><body><h1 data-wovn-ignore>en</h1>hello</body></html>';
        $responsed_html = '<html><head></head><body><h1 data-wovn-ignore><!-- __wovn-backend-ignored-key-0 --></h1>Bonjour</body></html>';
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers);
        $expected_html_before_send = "<html lang=\"en\"><head>$expected_head_content</head><body><h1 data-wovn-ignore><!-- __wovn-backend-ignored-key-0 --></h1>hello</body></html>";
        $response = json_encode(array("body" => $responsed_html));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals('<html><head></head><body><h1 data-wovn-ignore>en</h1>Bonjour</body></html>', $result);
        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send), $data, "should replace and replace back ignored contents");
    }

    public function testTranslateWithScriptTag()
    {
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $original_html = '<html><head><script>console.log("test");</script></head><body><h1>en</h1>hello</body></html>';
        $responsed_html = '<html><head><script><!-- __wovn-backend-ignored-key-0 --></script></head><body><h1>fr</h1>Bonjour</body></html>';
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers);
        $expected_html_before_send = '<html lang="en">'.
        '<head>'. $expected_head_content. '<script><!-- __wovn-backend-ignored-key-0 --></script></head>'.
        '<body><h1>en</h1>hello</body></html>';
        $response = json_encode(array("body" => $responsed_html));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send), $data);
        $this->assertEquals('<html><head><script>console.log("test");</script></head><body><h1>fr</h1>Bonjour</body></html>', $result, "should replace and replace back script tags");
    }

    public function testTranslateWithSchema()
    {
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');

        $original_html = '<html>' .
        '<head><script type="application/ld+json">{ "text": "Hello" }</script></head>' .
        '<body><h1>en</h1></body>' .
        '</html>';
        $responsed_html = '<html><head></head><body><h1>response from html-swapper</h1></body></html>';
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers);
        $expected_html_before_send = '<html lang="en">' .
        '<head>' . $expected_head_content . '<script type="application/ld+json">{ "text": "Hello" }</script></head>' .
        '<body><h1>en</h1></body>' .
        '</html>';
        $response = json_encode(array("body" => $responsed_html));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send), $data, "should not replace script tag which is defained as ld json");
    }

    public function testTranslateWithSaveMemoryBySendingWovnIgnoreContent()
    {
        $settings = array('save_memory_by_sending_wovn_ignore_content' => true);
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

        $original_html = '<html>' .
        '<head><script>console.log("test");</script></head>' .
        '<body><h1 wovn-ignore>en</h1></body>' .
        '</html>';
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers);
        $expected_html_before_send = '<html lang="en">' .
        '<head>' . $expected_head_content . '<script>console.log("test");</script></head>' .
        '<body><h1 wovn-ignore>en</h1></body>' .
        '</html>';
        $response = json_encode(array("body" => '<html><head></head><body><h1>response from html-swapper</h1></body></html>'));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send), $data, 'should not replace script anything if save_memory_by_sending_wovn_ignore_content is on');
    }

    public function testTranslateWithErrorHandled()
    {
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $expected_head_content = $this->getExpectedHtmlHeadContent($store, $headers);
        $expected_html_before_send = "<html lang=\"en\"><head>$expected_head_content</head><body><h1>en</h1></body></html>";
        $response = json_encode(array('missingBodyError' => '<html><head></head><body><h1>fr</h1></body></html>'));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];

        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send), $data);
        $this->assertEquals($expected_html_before_send, $result, "should return contents with fallback");
    }

    public function testTranslateWithEmptyResponse()
    {
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $response = null;
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals(1, count($mock->arguments));
        $expected_result = '<html lang="en"><head><link rel="alternate" hreflang="en" href="http://my-site.com/"><script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" data-wovnio-type="fallback_snippet" async></script></head><body><h1>en</h1></body></html>';
        $this->assertEquals($expected_result, $result, "should return contents with fallback");
    }

    public function testTranslateWithSearchEngineBot()
    {
        $envOverride = array('HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $envOverride);
        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $response = null;
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);
        $result = API::translate($store, $headers, $original_html, $request_options);
        list($method, $url, $data, $timeout) = $mock->arguments[0];

        $this->assertEquals(5.0, $timeout, 'should use higher timeout of 5.0');
    }

    public function testTranslateWithSearchEngineBotAlternativeBot()
    {
        $envOverride = array('HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $envOverride);
        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $response = null;
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);
        $result = API::translate($store, $headers, $original_html, $request_options);
        list($method, $url, $data, $timeout) = $mock->arguments[0];

        $this->assertEquals(5.0, $timeout, 'should use higher timeout of 5.0');
    }

    public function testTranslateWhenDefaultLangAndMakingAPICallBySettingIsOn()
    {
        $settings = array(
            'disable_api_request_for_default_lang' => true,
            'default_lang' => 'en'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $response = json_encode(array("body" => '<html><head></head><body><h1>response from html-swapper</h1></body></html>'));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);
        $this->assertEquals(0, count($mock->arguments), 'dont request to translation');
        $expected_result = '<html lang="en"><head><link rel="alternate" hreflang="en" href="http://my-site.com/"><script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script></head><body><h1>en</h1></body></html>';
        $this->assertEquals($expected_result, $result, "should return contents without fallback");
    }

    public function testTranslateWhenDefaultLangAndMakingAPICallBySettingIsOff()
    {
        $settings = array(
            'disable_api_request_for_default_lang' => false,
            'default_lang' => 'en'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $response = json_encode(array("body" => '<html><head></head><body><h1>response from html-swapper</h1></body></html>'));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);
        $this->assertEquals(1, count($mock->arguments));
        $expected_result = '<html><head></head><body><h1>response from html-swapper</h1></body></html>';
        $this->assertEquals($expected_result, $result, 'should return contents from html-swapper even if target language is same as default language');
    }

    public function testTranslateWithSitePrefixPath()
    {
        $settings = array('site_prefix_path' => 'dir1/dir2');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $expected_head_content = '<link rel="alternate" hreflang="en" href="http://my-site.com/"><script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn&amp;sitePrefixPath=dir1/dir2" data-wovnio-info="version=WOVN.php_VERSION" data-wovnio-type="fallback_snippet" async></script>';
        $expected_html_before_send = "<html lang=\"en\"><head>$expected_head_content</head><body><h1>en</h1></body></html>";
        $response = json_encode(array('missingBodyError' => '<html><head></head><body><h1>fr</h1></body></html>'));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send, $settings), $data);
    }

    public function testTranslateWithTranslateCanonicalTagFalse()
    {
        $settings = array('translate_canonical_tag' => false);
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

        $original_html = '<html><head></head><body><h1>en</h1></body></html>';
        $expected_head_content = '<link rel="alternate" hreflang="en" href="http://my-site.com/"><script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" data-wovnio-type="fallback_snippet" async></script>';
        $expected_html_before_send = "<html lang=\"en\"><head>$expected_head_content</head><body><h1>en</h1></body></html>";
        $response = json_encode(array('missingBodyError' => '<html><head></head><body><h1>fr</h1></body></html>'));
        $mock = $this->mockTranslationApi($response);
        $request_options = new RequestOptions(array(), false);

        $result = API::translate($store, $headers, $original_html, $request_options);

        $this->assertEquals(1, count($mock->arguments));
        list($method, $url, $data, $timeout) = $mock->arguments[0];
        $this->assertEquals($this->getExpectedApiUrl($store, $headers, $expected_html_before_send, $request_options), $url);
        $this->assertEquals($this->getExpectedData($store, $headers, $expected_html_before_send, $settings), $data);
    }
}
