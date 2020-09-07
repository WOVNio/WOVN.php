<?php
namespace Wovnio\Wovnphp\Tests\Unit;

require_once 'test/helpers/StoreAndHeadersFactory.php';
require_once 'test/helpers/HeadersMock.php';

require_once 'src/wovnio/wovnphp/Headers.php';
require_once 'src/wovnio/wovnphp/Lang.php';
require_once 'src/wovnio/wovnphp/Store.php';
require_once 'src/wovnio/wovnphp/Url.php';

use Wovnio\Test\Helpers\StoreAndHeadersFactory;

use Wovnio\Wovnphp\Url;
use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Headers;

class HeadersTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        parent::tearDown();

        \Wovnio\Wovnphp\restoreHeadersSent();
        \Wovnio\Wovnphp\restoreApacheResponseHeaders();
        \Wovnio\Wovnphp\restoreHeader();
    }

    public function testHeadersExists()
    {
        $this->assertTrue(class_exists('Wovnio\Wovnphp\Headers'));
    }

    public function testHeadersMatchQueryEmptyQueryString()
    {
        $settings = array('query' => array('page='));
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

        $this->assertEquals('my-site.com', $headers->redisUrl);
    }

    public function testHeadersMatchQuery()
    {
        $settings = array('query' => array('page='));
        $env = array('REQUEST_URI' => '/?page=1');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('my-site.com?page=1', $headers->redisUrl);
    }

    public function testHeadersMatchQueryEmptyQuerySettings()
    {
        $settings = array('query' => array());
        $env = array('REQUEST_URI' => '/?page=1');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('my-site.com', $headers->redisUrl);
    }

    public function testHeadersMatchQueryWrongQueryParams()
    {
        $settings = array('query' => array('page='));
        $env = array('REQUEST_URI' => '/?top=yes');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('my-site.com', $headers->redisUrl);
    }

    public function testHeadersMatchQueryTwoQueryParams()
    {
        $settings = array('query' => array('page=', 'top='));
        $env = array('REQUEST_URI' => '/?page=1&top=yes');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('my-site.com?page=1&top=yes', $headers->redisUrl);
    }

    public function testHeadersMatchQueryTwoQueryParamsSorting()
    {
        $settings = array('query' => array('a=', 'b='));
        $env = array('REQUEST_URI' => '/?b=2&a=1');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('my-site.com?a=1&b=2', $headers->redisUrl);
    }

    public function testHeadersMatchQueryTwoQueryParamsSortingOneWrong()
    {
        $settings = array('query' => array('a=', 'c='));
        $env = array(
            'REQUEST_URI' => '/?c=3&b=2&a=1'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('my-site.com?a=1&c=3', $headers->redisUrl);
    }

    public function testHeadersMatchQueryLongQueryString()
    {
        $settings = array('query' => array('a=', 'b=', 'c=', 'd=', 'e=', 'f=', 'g=', 'h='));
        $env = array('REQUEST_URI' => '/?e=5&d=4&c=3&b=2&a=1&f=6&g=7&h=8&z=10');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('my-site.com?a=1&b=2&c=3&d=4&e=5&f=6&g=7&h=8', $headers->redisUrl);
    }

    // setQueryParam

    public function testHeadersSetQueryParamRequestUri()
    {
        $env = array(
            'REQUEST_URI' => '/',
            'SERVER_PROTOCOL' => 'http'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('/?param=val', $he['REQUEST_URI']);
    }

    public function testHeadersSetQueryParamRequestUriAdd()
    {
        $env = array(
            'REQUEST_URI' => '/?a=b',
            'QUERY_STRING' => 'a=b',
            'SERVER_PROTOCOL' => 'http'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('/?a=b&param=val', $he['REQUEST_URI']);
    }

    public function testHeadersSetQueryParamRedirectQueryString()
    {
        $env = array(
            'REDIRECT_REQUEST_URI' => '_',
            'SERVER_PROTOCOL' => 'http'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('param=val', $he['REDIRECT_QUERY_STRING']);
    }

    public function testHeadersSetQueryParamQueryString()
    {
        $env = array('QUERY_STRING' => '');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('param=val', $he['QUERY_STRING']);
    }

    public function testHeadersSetQueryParamUnsetQueryString()
    {
        $env = array('REQUEST_URI' => '/');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('param=val', $he['QUERY_STRING']);
    }

    public function testHeadersSetQueryParamQueryStringOverwrite()
    {
        $env = array('QUERY_STRING' => 'param=what');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('param=val', $he['QUERY_STRING']);
    }

    public function testHeadersSetQueryParamQueryStringAdd()
    {
        $env = array('QUERY_STRING' => 'param1=what');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('param1=what&param=val', $he['QUERY_STRING']);
    }

    public function testHeadersSetQueryParamQueryStringNoVal()
    {
        $env = array('QUERY_STRING' => 'param');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('param=val', $he['QUERY_STRING']);
    }

    public function testHeadersSetQueryParamQueryStringMulti()
    {
        $env = array('QUERY_STRING' => 'p=v&a=b');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('p=v&a=b&param=val', $he['QUERY_STRING']);
    }

    public function testHeadersSetQueryParamQueryStringOverwriteMultiBegin()
    {
        $env = array('QUERY_STRING' => 'param=what&p=v');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('param=val&p=v', $he['QUERY_STRING']);
    }

    public function testHeadersSetQueryParamQueryStringOverwriteMultiMiddle()
    {
        $env = array('QUERY_STRING' => 'a=b&param=what&p=v');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('a=b&param=val&p=v', $he['QUERY_STRING']);
    }

    public function testHeadersSetQueryParamQueryStringOverwriteMultiEnd()
    {
        $env = array('QUERY_STRING' => 'a=b&param=what');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('a=b&param=val', $he['QUERY_STRING']);
    }

    public function testHeadersSetQueryParamQueryStringAddMulti()
    {
        $env = array('QUERY_STRING' => 'param1=what');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('param1=what&param=val', $he['QUERY_STRING']);
    }

    public function testHeadersSetQueryParamQueryStringNoValMulti()
    {
        $env = array('QUERY_STRING' => 'param');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('param=val', $he['QUERY_STRING']);
    }

    public function testHeadersSetQueryParamWithPath()
    {
        $env = array(
            'REQUEST_URI' => '/path/here?param=val',
            'QUERY_STRING' => 'param=val',
            'REDIRECT_QUERY_STRING' => 'param=val'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('hey', 'yo');
        $headersEnv = $headers->getEnv();
        $this->assertEquals('/path/here?param=val&hey=yo', $headersEnv['REQUEST_URI']);
        $this->assertEquals('param=val&hey=yo', $headersEnv['QUERY_STRING']);
        $this->assertEquals('param=val&hey=yo', $headersEnv['REDIRECT_QUERY_STRING']);
    }

    public function testHeadersSetQueryParamWithFilePath()
    {
        $env = array(
            'REQUEST_URI' => '/path/here.php?param=val',
            'QUERY_STRING' => 'param=val',
            'REDIRECT_QUERY_STRING' => 'param=val'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('hey', 'yo');
        $headersEnv = $headers->getEnv();
        $this->assertEquals('/path/here.php?param=val&hey=yo', $headersEnv['REQUEST_URI']);
        $this->assertEquals('param=val&hey=yo', $headersEnv['QUERY_STRING']);
        $this->assertEquals('param=val&hey=yo', $headersEnv['REDIRECT_QUERY_STRING']);
    }

    public function testHeadersSetQueryParamGET()
    {
        global $_GET;

        $_GET = array();
        $env = array('REQUEST_URI' => '/');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('val', $_GET['param']);
    }

    public function testHeadersSetQueryParamOverwriteGET()
    {
        global $_GET;

        $_GET = array('param' => 'there');
        $env = array('REQUEST_URI' => '/');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $he = $headers->getEnv();
        $this->assertEquals('val', $_GET['param']);
    }

    public function testHeadersSetQueryParamRequest()
    {
        global $_REQUEST;

        $_REQUEST = array();
        $env = array('REQUEST_URI' => '/');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->setQueryParam('param', 'val');
        $this->assertEquals('val', $_REQUEST['param']);
    }

    // setQueryParams

    public function testSetQueryParamsRequestUri()
    {
        $env = array('REQUEST_URI' => '/');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $qa = array();
        array_push($qa, "param2=val2");

        $headers->setQueryParams($qa);
        $he = $headers->getEnv();
        $this->assertEquals('/?param2=val2', $he['REQUEST_URI']);
    }

    public function testSetQueryParamsQueryString()
    {
        $env = array('QUERY_STRING' => '');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $qa = array();
        array_push($qa, "param2=val2");

        $headers->setQueryParams($qa);
        $he = $headers->getEnv();
        $this->assertEquals('param2=val2', $he['QUERY_STRING']);
    }

    public function testSetQueryParamsQueryStringMulti()
    {
        $env = array('QUERY_STRING' => '');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $qa = array();
        array_push($qa, "param1=val1");
        array_push($qa, "param2=val2");

        $headers->setQueryParams($qa);
        $he = $headers->getEnv();
        $this->assertEquals('param1=val1&param2=val2', $he['QUERY_STRING']);
    }

    public function testSetQueryParamsQueryStringEmpty()
    {
        $env = array('QUERY_STRING' => '');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $qa = array();

        $headers->setQueryParams($qa);
        $he = $headers->getEnv();
        $this->assertEquals('', $he['QUERY_STRING']);
    }

    public function testSetQueryParamsQueryStringMultiReplace()
    {
        $env = array('QUERY_STRING' => 'param2=what&param1=oh');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $qa = array();
        array_push($qa, "param1=val1");
        array_push($qa, "param2=val2");

        $headers->setQueryParams($qa);
        $he = $headers->getEnv();
        $this->assertEquals('param2=val2&param1=val1', $he['QUERY_STRING']);
    }

    public function testSetQueryParamsQueryStringMultiPartialReplace()
    {
        $env = array('QUERY_STRING' => 'param2=what&oh=yeah&param1=oh');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $qa = array();
        array_push($qa, "param1=val1");
        array_push($qa, "param2=val2");

        $headers->setQueryParams($qa);
        $he = $headers->getEnv();
        $this->assertEquals('param2=val2&oh=yeah&param1=val1', $he['QUERY_STRING']);
    }

    public function testHeadersClearQueryParamsRequestUri()
    {
        $env = array('REQUEST_URI' => '/?hey=yeah');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->clearQueryParams();
        $he = $headers->getEnv();
        $this->assertEquals('/', $he['REQUEST_URI']);
    }

    public function testHeadersClearQueryParamsEmptyRequestUri()
    {
        $env = array('REQUEST_URI' => '/');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->clearQueryParams();
        $he = $headers->getEnv();
        $this->assertEquals('/', $he['REQUEST_URI']);
    }

    public function testHeadersClearQueryParamsEmptyRequestUriHangingHatena()
    {
        $env = array('REQUEST_URI' => '/?');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->clearQueryParams();
        $he = $headers->getEnv();
        $this->assertEquals('/', $he['REQUEST_URI']);
    }

    public function testHeadersClearQueryParamsQueryString()
    {
        $env = array('QUERY_STRING' => 'heythere');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->clearQueryParams();
        $he = $headers->getEnv();
        $this->assertEquals('', $he['QUERY_STRING']);
    }

    public function testHeadersClearQueryParamsQueryStringEmpty()
    {
        $env = array('QUERY_STRING' => '');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->clearQueryParams();
        $he = $headers->getEnv();
        $this->assertEquals('', $he['QUERY_STRING']);
    }

    public function testHeadersClearQueryParamsQueryStringMulti()
    {
        $env = array('QUERY_STRING' => 'hey=there&oh=ok');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->clearQueryParams();
        $he = $headers->getEnv();
        $this->assertEquals('', $he['QUERY_STRING']);
    }

    public function testHeadersClearQueryParamsGET()
    {
        global $_GET;

        $_GET = array('hey' => 'there');
        $env = array('QUERY_STRING' => 'hey=there');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->clearQueryParams();
        $he = $headers->getEnv();
        $this->assertEquals(0, count($_GET));
    }

    public function testHeadersClearQueryParamsEmptyGET()
    {
        global $_GET;

        $_GET = array();
        $env = array('QUERY_STRING' => '');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

        $headers->clearQueryParams();
        $he = $headers->getEnv();
        $this->assertEquals(0, count($_GET));
    }

    public function testHeadersRedirectLocationWithQueryPatternAndNoQuery()
    {
        $settings = array(
            'url_pattern_name' => 'query',
            'lang_param_name' => 'wovn'
        );
        $env = array('QUERY_STRING' => '');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->url = 'google.com/test';
        $lang = 'ja';
        $expected = 'http://google.com/test?wovn=ja';
        $out = $headers->redirectLocation($lang);
        $this->assertEquals($expected, $out);
    }

    public function testHeadersRedirectLocationWithQueryPatternAndExistingQuery()
    {
        $settings = array(
            'url_pattern_name' => 'query',
            'lang_param_name' => 'wovn'
        );
        $env = array('QUERY_STRING' => '?page=1');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->protocol = 'http';
        $headers->url = 'google.com/test?page=1';
        $lang = 'ja';
        $expected = 'http://google.com/test?page=1&wovn=ja';
        $out = $headers->redirectLocation($lang);
        $this->assertEquals($expected, $out);
    }

    public function testHeadersWithUseProxyTrue()
    {
        $settings = array('use_proxy' => 1);
        $env = array(
            'HTTP_X_FORWARDED_HOST' => 'ja.wovn.io',
            'HTTP_X_FORWARDED_PROTO' => 'https'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('ja.wovn.io', $headers->unmaskedHost);
        $this->assertEquals('ja.wovn.io', $headers->host);
        $this->assertEquals('https', $headers->protocol);
    }

    public function testHeadersWithUseProxyFalse()
    {
        $settings = array('use_proxy' => false);
        $env = array(
            'HTTP_X_FORWARDED_HOST' => 'ja.wovn.io',
            'HTTP_X_FORWARDED_PROTO' => 'https'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('my-site.com', $headers->unmaskedHost);
        $this->assertEquals('my-site.com', $headers->host);
        $this->assertEquals('http', $headers->protocol);
    }

    public function testHeadersWithUseProxyTrueButNoForwardedInfo()
    {
        $settings = array('use_proxy' => 1);
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

        $this->assertEquals('my-site.com', $headers->unmaskedHost);
        $this->assertEquals('my-site.com', $headers->host);
        $this->assertEquals('http', $headers->protocol);
    }

    public function testRemoveLangWithPathPattern()
    {
        $settings = array('url_pattern_name' => 'path');
        $testCases = array(
            array('wovn.io/ja', 'wovn.io/', 'ja'),
            array('http://wovn.io/en', 'http://wovn.io/', 'en'),
            array('https://wovn.io/en', 'https://wovn.io/', 'en'),
            array('wovn.io/zh-cht', 'wovn.io/', 'zh-CHT'),
            array('https://wovn.io/zh-cht', 'https://wovn.io/', 'zh-CHT'),
            array('wovn.io/en-US', 'wovn.io/', 'en-US'),
            array('https://wovn.io/en-US', 'https://wovn.io/', 'en-US'),
            array('wovn.io/zh-Hant-TW', 'wovn.io/', 'zh-Hant-TW'),
            array('https://wovn.io/zh-Hant-TW', 'https://wovn.io/', 'zh-Hant-TW')
        );

        foreach ($testCases as $case) {
            list($beforeRemoveUrl, $afterRemoveUrl, $removeLang) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

            $this->assertEquals('path', $store->settings['url_pattern_name']);
            $this->assertEquals($afterRemoveUrl, $headers->removeLang($beforeRemoveUrl, $removeLang));
        };
    }

    public function testRemoveLangWithQueryPattern()
    {
        $settings = array('url_pattern_name' => 'query');
        $testCases = array(
            array('wovn.io/?wovn=ja', 'wovn.io/', 'ja'),
            array('http://wovn.io?wovn=en', 'http://wovn.io', 'en'),
            array('https://wovn.io?wovn=en', 'https://wovn.io', 'en'),
            array('wovn.io?wovn=zh-cht', 'wovn.io', 'zh-CHT'),
            array('https://wovn.io?wovn=zh-cht', 'https://wovn.io', 'zh-CHT'),
            array('wovn.io?wovn=en-US', 'wovn.io', 'en-US'),
            array('https://wovn.io?wovn=en-US', 'https://wovn.io', 'en-US'),
            array('wovn.io?wovn=zh-Hant-TW', 'wovn.io', 'zh-Hant-TW'),
            array('https://wovn.io?wovn=zh-Hant-TW', 'https://wovn.io', 'zh-Hant-TW')
        );

        foreach ($testCases as $case) {
            list($beforeRemoveUrl, $afterRemoveUrl, $removeLang) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

            $this->assertEquals('query', $store->settings['url_pattern_name']);
            $this->assertEquals($afterRemoveUrl, $headers->removeLang($beforeRemoveUrl, $removeLang));
        };
    }

    public function testRemoveLangWithSubdomainPattern()
    {
        $settings = array('url_pattern_name' => 'subdomain');
        $testCases = array(
            array('ja.wovn.io/', 'wovn.io/', 'ja'),
            array('http://en.wovn.io', 'http://wovn.io', 'en'),
            array('https://en.wovn.io', 'https://wovn.io', 'en'),
            array('zh-cht.wovn.io', 'wovn.io', 'zh-CHT'),
            array('https://zh-cht.wovn.io', 'https://wovn.io', 'zh-CHT'),
            array('en-US.wovn.io', 'wovn.io', 'en-US'),
            array('https://en-US.wovn.io', 'https://wovn.io', 'en-US'),
            array('zh-Hant-TW.wovn.io', 'wovn.io', 'zh-Hant-TW'),
            array('https://zh-Hant-TW.wovn.io', 'https://wovn.io', 'zh-Hant-TW')
        );

        foreach ($testCases as $case) {
            list($beforeRemoveUrl, $afterRemoveUrl, $removeLang) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

            $this->assertEquals('subdomain', $store->settings['url_pattern_name']);
            $this->assertEquals($afterRemoveUrl, $headers->removeLang($beforeRemoveUrl, $removeLang));
        };
    }

    public function testRemoveLangWithCustomLangAliases()
    {
        $settings = array(
            'custom_lang_aliases' => array('ja' => 'ja-test','en-US' => 'us', 'zh-Hant-TW' => 'cn'),
            'url_pattern_name' => 'path'
        );
        $testCases = array(
            array('wovn.io/ja-test', 'wovn.io/', 'ja'),
            array('https://wovn.io/fr/', 'https://wovn.io/', 'fr'),
            array('https://wovn.io/us/', 'https://wovn.io/', 'en-US'),
            array('https://wovn.io/cn/', 'https://wovn.io/', 'zh-Hant-TW')
        );

        foreach ($testCases as $case) {
            list($beforeRemoveUrl, $afterRemoveUrl, $removeLang) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

            $this->assertEquals('path', $store->settings['url_pattern_name']);
            $this->assertEquals($afterRemoveUrl, $headers->removeLang($beforeRemoveUrl, $removeLang));
        };
    }

    public function testRemoveLangWithCustomDefaultLangAliasesPath()
    {
        $settings = array(
            'default_lang' => 'en',
            'custom_lang_aliases' => array('en' => 'english', 'ja' => 'japanese'),
            'url_pattern_name' => 'path'
        );
        $testCases = array(
            array('https://my-site.com/japanese/', 'https://my-site.com/english/', 'ja'),
            array('https://my-site.com/english/', 'https://my-site.com/english/', 'en'),
            array('/japanese/pages.html', '/english/pages.html', 'ja'),
            array('/english/pages.html', '/english/pages.html', 'en')
        );

        foreach ($testCases as $case) {
            list($beforeRemoveUrl, $afterRemoveUrl, $removeLang) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

            $this->assertEquals('path', $store->settings['url_pattern_name']);
            $this->assertEquals($afterRemoveUrl, $headers->removeLang($beforeRemoveUrl, $removeLang));
        };
    }

    public function testRemoveLangWithCustomDefaultLangAliasesSubDomain()
    {
        $settings = array(
            'default_lang' => 'en',
            'custom_lang_aliases' => array('en' => 'english'),
            'supported_langs' => array('en', 'jp'),
            'url_pattern_name' => 'subdomain'
        );
        $testCases = array(
            array('https://english.my-site.com/index.html', 'https://english.my-site.com/index.html', 'en'),
        );

        foreach ($testCases as $case) {
            list($beforeRemoveUrl, $afterRemoveUrl, $removeLang) = $case;
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

            $this->assertEquals($afterRemoveUrl, $headers->removeLang($beforeRemoveUrl, $removeLang));
        };
    }

    public function testPathLangWithPathPattern()
    {
        $settings = array('url_pattern_name' => 'path');
        $env = array('SERVER_NAME' => 'wovn.io');
        $testCases = array(
            array('/en/test', 'en'),
            array('/zh-CHT/test', 'zh-CHT'),
            array('/en-US/test', 'en-US'),
            array('/zh-Hant-TW/test', 'zh-Hant-TW'),
            array('/thi/en/test', ''), // lang code is not at the begining
            array('/thai/en/test', '') // has lang name instead of lang code
        );

        foreach ($testCases as $case) {
            list($requestUrl, $expectedLangCode) = $case;
            $mergedEnv = array_merge($env, array('REQUEST_URI' => $requestUrl));
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $mergedEnv);

            $this->assertEquals('path', $store->settings['url_pattern_name']);
            $this->assertEquals($expectedLangCode, $headers->computePathLang());
        };
    }

    public function testPathLangWithQueryPattern()
    {
        $settings = array('url_pattern_name' => 'query');
        $env = array('SERVER_NAME' => 'wovn.io');
        $testCases = array(
            array('/test?wovn=zh-CHS', 'zh-CHS'),
            array('/test?wovn=en-US', 'en-US'),
            array('/test?wovn=zh-Hant-TW', 'zh-Hant-TW')
        );

        foreach ($testCases as $case) {
            list($requestUrl, $expectedLangCode) = $case;
            $mergedEnv = array_merge($env, array('REQUEST_URI' => $requestUrl));
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $mergedEnv);

            $this->assertEquals('query', $store->settings['url_pattern_name']);
            $this->assertEquals($expectedLangCode, $headers->computePathLang());
        };
    }

    public function testPathLangWithSubdomainPattern()
    {

        $settings = array('url_pattern_name' => 'subdomain');
        $env = array('REQUEST_URI' => '/test');
        $testCases = array(
            array('zh-chs.wovn.io', 'zh-CHS'),
            array('en-US.wovn.io', 'en-US'),
            array('zh-Hant-TW.wovn.io', 'zh-Hant-TW'),
            array('thai.wovn.io.wovn.io', '') // has lang name instead of lang code
        );

        foreach ($testCases as $case) {
            list($serverName, $expectedLangCode) = $case;
            $mergedEnv = array_merge($env, array('SERVER_NAME' => $serverName));
            list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $mergedEnv);

            $this->assertEquals('subdomain', $store->settings['url_pattern_name']);
            $this->assertEquals($expectedLangCode, $headers->computePathLang());
        };
    }

    public function testPathLangWithSubdomainAndUseProxyTrue()
    {
        $settings = array(
            'url_pattern_name' => 'subdomain',
            'use_proxy' => 1
        );
        $env = array(
            'SERVER_NAME' => 'ja.wovn.io',
            'REQUEST_URI' => '/ko/path/index.html',
            'HTTP_X_FORWARDED_HOST' => 'en.minimaltech.co',
            'HTTP_X_FORWARDED_REQUEST_URI' => '/sv/path/index.html'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $pathlang = $headers->computePathLang();
        $this->assertEquals('en', $pathlang);
    }

    public function testPathLangWithPathAndUseProxyTrue()
    {
        $settings = array(
            'url_pattern_name' => 'path',
            'use_proxy' => 1
        );
        $env = array(
            'SERVER_NAME' => 'ja.wovn.io',
            'REQUEST_URI' => '/ko/path/index.html',
            'HTTP_X_FORWARDED_HOST' => 'en.minimaltech.co',
            'HTTP_X_FORWARDED_REQUEST_URI' => '/sv/path/index.html'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $pathlang = $headers->computePathLang();
        $this->assertEquals('sv', $pathlang);
    }

    public function testPathLangWithSubdomainAndUseProxyFalse()
    {
        $settings = array(
            'url_pattern_name' => 'subdomain',
            'use_proxy' => false
        );
        $env = array(
            'SERVER_NAME' => 'ja.wovn.io',
            'REQUEST_URI' => '/ko/path/index.html',
            'HTTP_X_FORWARDED_HOST' => 'en.minimaltech.co',
            'HTTP_X_FORWARDED_REQUEST_URI' => '/sv/path/index.html'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $pathlang = $headers->computePathLang();
        $this->assertEquals('ja', $pathlang);
    }

    public function testPathLangWithPathAndUseProxyFalse()
    {
        $settings = array(
            'url_pattern_name' => 'path',
            'use_proxy' => false
        );
        $env = array(
            'SERVER_NAME' => 'ja.wovn.io',
            'REQUEST_URI' => '/ko/path/index.html',
            'HTTP_X_FORWARDED_HOST' => 'en.minimaltech.co',
            'HTTP_X_FORWARDED_REQUEST_URI' => '/sv/path/index.html'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $pathlang = $headers->computePathLang();
        $this->assertEquals('ko', $pathlang);
    }

    public function testPathLangWithUseProxyTrueButNoForwardedHost()
    {
        $settings = array(
            'url_pattern_name' => 'subdomain',
            'use_proxy' => 1
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('japanese_subdomain_request', $settings);

        $pathlang = $headers->computePathLang();
        $this->assertEquals('ja', $pathlang);
    }

    public function testRequestOutWithUseProxyTrue()
    {
        $settings = array(
            'url_pattern_name' => 'subdomain',
            'use_proxy' => 1
        );
        $env = array('HTTP_X_FORWARDED_HOST' => 'en.minimaltech.co');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->requestOut();

        $he = $headers->getEnv();
        $this->assertEquals('minimaltech.co', $he['HTTP_X_FORWARDED_HOST']);
        $this->assertEquals('my-site.com', $he['SERVER_NAME']);
    }

    public function testRequestOutWithUseProxyFalse()
    {
        $settings = array(
            'url_pattern_name' => 'subdomain',
            'use_proxy' => false
        );
        $env = array('HTTP_X_FORWARDED_HOST' => 'en.minimaltech.co');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->requestOut();

        $he = $headers->getEnv();
        $this->assertEquals('en.minimaltech.co', $he['HTTP_X_FORWARDED_HOST']);
    }

    public function testRequestOutUrlPatternPath()
    {
        $settings = array('url_pattern_name' => 'path');
        $env = array('HTTP_X_FORWARDED_REQUEST_URI' => '/ja/forwarded/path/');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('japanese_path_request', $settings, $env);

        $he = $headers->getEnv();
        $this->assertEquals('/ja/mypage.php', $he['REQUEST_URI']);
        $this->assertEquals('/mypage.php', $he['REDIRECT_URL']);
        $this->assertEquals('/ja/index.php', $he['HTTP_REFERER']);
        $this->assertEquals('/ja/forwarded/path/', $he['HTTP_X_FORWARDED_REQUEST_URI']);

        $headers->requestOut();

        $he = $headers->getEnv();
        $this->assertEquals('/mypage.php', $he['REQUEST_URI']);
        $this->assertEquals('/mypage.php', $he['REDIRECT_URL']);
        $this->assertEquals('/index.php', $he['HTTP_REFERER']);
        $this->assertEquals('/forwarded/path/', $he['HTTP_X_FORWARDED_REQUEST_URI']);
    }

    public function testRequestOutUrlPatternQuery()
    {
        $settings = array('url_pattern_name' => 'query');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('japanese_query_request', $settings);

        $he = $headers->getEnv();
        $this->assertEquals('?wovn=ja', $he['QUERY_STRING']);
        $this->assertEquals('/mypage.php?wovn=ja', $he['REQUEST_URI']);
        $this->assertEquals('/index.php?login=no&wovn=ja', $he['HTTP_REFERER']);

        $headers->requestOut();

        $he = $headers->getEnv();
        $this->assertEquals('', $he['QUERY_STRING']);
        $this->assertEquals('/mypage.php', $he['REQUEST_URI']);
        $this->assertEquals('/index.php?login=no', $he['HTTP_REFERER']);
    }

    public function testHttpsProtocolOn()
    {
        $settings = array(
            'url_pattern_name' => 'subdomain',
            'use_proxy' => false
        );
        $env = array('HTTPS' => 'on');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('https', $headers->protocol);
    }

    public function testHttpsProtocol()
    {
        $settings = array(
            'url_pattern_name' => 'subdomain',
            'use_proxy' => false
        );
        $env = array('HTTPS' => 'random');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('https', $headers->protocol);
    }

    public function testHttpProtocol()
    {
        $settings = array(
            'url_pattern_name' => 'subdomain',
            'use_proxy' => false
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

        $this->assertEquals('http', $headers->protocol);
    }

    public function testHttpProtocolEmpty()
    {
        $settings = array(
            'url_pattern_name' => 'subdomain',
            'use_proxy' => false
        );
        $env = array('HTTPS' => '');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('http', $headers->protocol);
    }

    public function testHttpProtocolHttpsOff()
    {
        $settings = array(
            'url_pattern_name' => 'subdomain',
            'use_proxy' => false
        );
        $env = array('HTTPS' => 'off');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('http', $headers->protocol);
    }

    public function testRequestOutSubdomainPatternWithHttpReferer()
    {
        $settings = array('url_pattern_name' => 'subdomain');
        $env = array(
            'HTTP_REFERER' => 'ja.minimaltech.co',
            'REQUEST_URI' => '/dummy'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('japanese_subdomain_request', $settings, $env);

        $this->assertEquals('ja', $headers->computePathLang());

        $headers->requestOut();

        $he = $headers->getEnv();
        $this->assertEquals('minimaltech.co', $he['HTTP_REFERER']);
    }

    public function testRequestOutPathPatternWithHttpReferer()
    {
        $settings = array('url_pattern_name' => 'path');
        $env = array(
            'HTTP_REFERER' => 'minimaltech.co/ja',
            'REQUEST_URI' => '/ja/dummy'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('ja', $headers->computePathLang());

        $headers->requestOut();

        $he = $headers->getEnv();
        $this->assertEquals('minimaltech.co/', $he['HTTP_REFERER']);
    }

    public function testRequestOutQueryPatternWithHttpReferer()
    {
        $settings = array('url_pattern_name' => 'query');
        $env = array(
            'HTTP_REFERER' => 'minimaltech.co/?wovn=ja',
            'REQUEST_URI' => '/dummy?wovn=ja'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('ja', $headers->computePathLang());

        $headers->requestOut();

        $he = $headers->getEnv();
        $this->assertEquals('minimaltech.co/', $he['HTTP_REFERER']);
    }

    public function testResponseOutWithDefaultLangAndSubdomainPattern()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => '/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'subdomain');
        $env = array(
            'HTTP_HOST' => 'my-site.com',
            'SERVER_NAME' => 'my-site.com',
            'REQUEST_URI' => 'http://my-site.com/test'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(0, count($receivedHeaders));
    }

    public function testResponseOutWithNotDefaultLangAndSubdomainPatternWhenApacheNotUsed()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(false);
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'subdomain');
        $env = array(
            'HTTP_HOST' => 'fr.my-site.com',
            'SERVER_NAME' => 'fr.my-site.com',
            'REQUEST_URI' => 'http://fr.my-site.com/test'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(0, count($receivedHeaders));
    }

    public function testResponseOutWithNotDefaultLangAndSubdomainPatternWhenHeadersSent()
    {
        \Wovnio\Wovnphp\mockHeadersSent(true);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => '/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'subdomain');
        $env = array(
            'HTTP_HOST' => 'fr.my-site.com',
            'SERVER_NAME' => 'fr.my-site.com',
            'REQUEST_URI' => 'http://fr.my-site.com/test'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(0, count($receivedHeaders));
    }

    public function testResponseOutAbsoluteUrlWithNotDefaultLangAndSubdomainPattern()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => 'http://my-site.com/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'subdomain');
        $env = array(
            'HTTP_HOST' => 'fr.my-site.com',
            'SERVER_NAME' => 'fr.my-site.com',
            'REQUEST_URI' => 'http://fr.my-site.com/test'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(1, count($receivedHeaders));
        $this->assertEquals('Location: http://fr.my-site.com/index.php', $receivedHeaders[0]);
    }

    public function testResponseOutWithNotDefaultLangAndSubdomainPattern()
    {
        $this->markTestSkipped('This test fails with Wovn PHP Core integration.');

        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => '/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'subdomain', 'supported_langs' => array('en'));
        $env = array(
            'HTTP_HOST' => 'fr.my-site.com',
            'SERVER_NAME' => 'fr.my-site.com',
            'REQUEST_URI' => 'http://fr.my-site.com/test'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(1, count($receivedHeaders));
        $this->assertEquals('Location: http://fr.my-site.com/index.php', $receivedHeaders[0]);
    }

    public function testResponseOutWithNotDefaultAlreadyInRedirectLocationLangAndSubdomainPattern()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => 'http://fr.my-site.com/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'subdomain');
        $env = array(
            'HTTP_HOST' => 'fr.my-site.com',
            'SERVER_NAME' => 'fr.my-site.com',
            'REQUEST_URI' => 'http://fr.my-site.com/test'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(1, count($receivedHeaders));
        $this->assertEquals('Location: http://fr.my-site.com/index.php', $receivedHeaders[0]);
    }

    public function testResponseOutOutsideRedirectionWithNotDefaultLangAndSubdomainPattern()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => 'http://google.com/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'subdomain');
        $env = array(
            'HTTP_HOST' => 'fr.my-site.com',
            'SERVER_NAME' => 'fr.my-site.com',
            'REQUEST_URI' => 'http://fr.my-site.com/test'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(1, count($receivedHeaders));
        $this->assertEquals('Location: http://google.com/index.php', $receivedHeaders[0]);
    }

    public function testResponseOutWithNotDefaultAlreadyInRedirectLocationCustomLangAndSubdomainPattern()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => 'http://fr-test.my-site.com/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array(
            'url_pattern_name' => 'subdomain',
            'custom_lang_aliases' => array('fr' => 'fr-test'),
            'supported_langs' => array('en')
        );
        $env = array(
            'HTTP_HOST' => 'fr-test.my-site.com',
            'SERVER_NAME' => 'fr-test.my-site.com',
            'REQUEST_URI' => 'http://fr-test.my-site.com/test'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(1, count($receivedHeaders));
        $this->assertEquals('Location: http://fr-test.my-site.com/index.php', $receivedHeaders[0]);
    }

    public function testResponseOutWithDefaultLangAndPathPattern()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => '/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'path');
        $env = array( 'REQUEST_URI' => '/test');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(0, count($receivedHeaders));
    }

    public function testResponseOutWithNotDefaultLangAndPathPatternWhenApacheNotUsed()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(false);
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'path');
        $env = array( 'REQUEST_URI' => '/fr/test');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(0, count($receivedHeaders));
    }

    public function testResponseOutWithNotDefaultLangAndPathPatternWhenHeadersSent()
    {
        \Wovnio\Wovnphp\mockHeadersSent(true);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => '/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'path');
        $env = array( 'REQUEST_URI' => '/fr/test');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(0, count($receivedHeaders));
    }

    public function testResponseOutWithNotDefaultLangAndPathPattern()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => '/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'path');
        $env = array( 'REQUEST_URI' => '/fr/test');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(1, count($receivedHeaders));
        $this->assertEquals('Location: /fr/index.php', $receivedHeaders[0]);
    }

    public function testResponseOutWithSitePrefixPathAndRedirectLocationAndPathPattern()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => '/dir/page.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array(
            'url_pattern_name' => 'path',
            'site_prefix_path' => 'dir'
        );
        $env = array( 'REQUEST_URI' => '/dir/fr/requested');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(1, count($receivedHeaders));
        $this->assertEquals('Location: /dir/fr/page.php', $receivedHeaders[0]);
    }

    public function testResponseOutWithNotDefaultLangAlreadyInRedirectLocationAndPathPattern()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => '/fr/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'path');
        $env = array( 'REQUEST_URI' => '/fr/test');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(1, count($receivedHeaders));
        $this->assertEquals('Location: /fr/index.php', $receivedHeaders[0]);
    }

    public function testResponseOutWithDefaultLangAndQueryPattern()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => '/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'query');
        $env = array( 'REQUEST_URI' => '/test');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(0, count($receivedHeaders));
    }

    public function testResponseOutWithNotDefaultLangAndQueryPatternWhenApacheNotUsed()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(false);
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'query');
        $env = array( 'REQUEST_URI' => '/test?wovn=fr');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(0, count($receivedHeaders));
    }

    public function testResponseOutWithNotDefaultLangAndQueryPatternWhenHeadersSent()
    {
        \Wovnio\Wovnphp\mockHeadersSent(true);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => '/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'query');
        $env = array( 'REQUEST_URI' => '/test?wovn=fr');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(0, count($receivedHeaders));
    }

    public function testResponseOutWithNotDefaultLangAndQueryPattern()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => '/index.php'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'query');
        $env = array( 'REQUEST_URI' => '/test?wovn=fr');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(1, count($receivedHeaders));
        $this->assertEquals('Location: /index.php?wovn=fr', $receivedHeaders[0]);
    }

    public function testResponseOutWithNotDefaultLangAlreadyInRedirectLocationAndQueryPattern()
    {
        \Wovnio\Wovnphp\mockHeadersSent(false);
        \Wovnio\Wovnphp\mockApacheResponseHeaders(true, array(
            'Location' => '/index.php?wovn=fr'
        ));
        \Wovnio\Wovnphp\mockHeader();

        $settings = array('url_pattern_name' => 'query');
        $env = array( 'REQUEST_URI' => '/test?wovn=fr');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $headers->responseOut();
        $receivedHeaders = \Wovnio\Wovnphp\getHeadersReceivedByHeaderMock();

        $this->assertEquals(1, count($receivedHeaders));
        $this->assertEquals('Location: /index.php?wovn=fr', $receivedHeaders[0]);
    }

    public function testGetDocumentURIWithQueryPattern()
    {
        $settings = array(
            'url_pattern_name' => 'query',
            'query' => array('page=')
        );
        $env = array( 'REQUEST_URI' => '/en/path?page=1&wovn=vi');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('/en/path', $headers->getDocumentURI());
    }

    public function testGetDocumentURIWithPathPattern()
    {
        $settings = array('url_pattern_name' => 'path');
        $env = array( 'REQUEST_URI' => '/en/path?page=1');
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('/path', $headers->getDocumentURI());
    }

    public function testUrlKeepTrailingSlashWithoutProxy()
    {
        $settings = array(
            'url_pattern_name' => 'path',
            'use_proxy' => 0
        );
        $env = array(
            'HTTP_HOST' => 'sub.domain.com',
            'HTTP_X_FORWARDED_HOST' => 'main.com',
            'REQUEST_URI' => '/en/path',
            'HTTP_X_FORWARDED_REQUEST_URI' => '/forwarded/other/path'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('http://sub.domain.com/path', $headers->urlKeepTrailingSlash);
    }

    public function testUrlKeepTrailingSlashWithUseProxy()
    {
        $settings = array(
            'url_pattern_name' => 'path',
            'use_proxy' => 1
        );
        $env = array(
            'HTTP_HOST' => 'sub.domain.com',
            'HTTP_X_FORWARDED_HOST' => 'main.com',
            'REQUEST_URI' => '/en/path',
            'HTTP_X_FORWARDED_REQUEST_URI' => '/en/forwarded/other/path'
        );
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

        $this->assertEquals('http://main.com/forwarded/other/path', $headers->urlKeepTrailingSlash);
    }
}
