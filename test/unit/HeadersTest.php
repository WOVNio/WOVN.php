<?php
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

class HeadersTest extends PHPUnit_Framework_TestCase {
  protected function tearDown() {
    parent::tearDown();

    Wovnio\wovnphp\restore_headers_sent();
    Wovnio\wovnphp\restore_apache_response_headers();
    Wovnio\wovnphp\restore_header();
  }

  public function testHeadersExists() {
    $this->assertTrue(class_exists('Wovnio\Wovnphp\Headers'));
  }

  public function testHeadersMatchQueryEmptyQueryString() {
    $settings = array('query' => array('page='));
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

    $this->assertEquals('localhost', $headers->redisUrl);
  }

  public function testHeadersMatchQuery() {
    $settings = array('query' => array('page='));
    $env = array('REQUEST_URI' => '/?page=1');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('localhost?page=1', $headers->redisUrl);
  }

  public function testHeadersMatchQueryEmptyQuerySettings() {
    $settings = array('query' => array());
    $env = array('REQUEST_URI' => '/?page=1');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('localhost', $headers->redisUrl);
  }

  public function testHeadersMatchQueryWrongQueryParams() {
    $settings = array('query' => array('page='));
    $env = array('REQUEST_URI' => '/?top=yes');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('localhost', $headers->redisUrl);
  }

  public function testHeadersMatchQueryTwoQueryParams() {
    $settings = array('query' => array('page=', 'top='));
    $env = array('REQUEST_URI' => '/?page=1&top=yes');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('localhost?page=1&top=yes', $headers->redisUrl);
  }

  public function testHeadersMatchQueryTwoQueryParamsSorting() {
    $settings = array('query' => array('a=', 'b='));
    $env = array('REQUEST_URI' => '/?b=2&a=1');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('localhost?a=1&b=2', $headers->redisUrl);
  }

  public function testHeadersMatchQueryTwoQueryParamsSortingOneWrong() {
    $settings = array('query' => array('a=', 'c='));
    $env = array(
      'REQUEST_URI' => '/?c=3&b=2&a=1'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('localhost?a=1&c=3', $headers->redisUrl);
  }

  public function testHeadersMatchQueryLongQueryString() {
    $settings = array('query' => array('a=', 'b=', 'c=', 'd=', 'e=', 'f=', 'g=', 'h='));
    $env = array('REQUEST_URI' => '/?e=5&d=4&c=3&b=2&a=1&f=6&g=7&h=8&z=10');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('localhost?a=1&b=2&c=3&d=4&e=5&f=6&g=7&h=8', $headers->redisUrl);
  }

  // setQueryParam

  public function testHeadersSetQueryParamRequestUri() {
    $env = array(
      'REQUEST_URI' => '/',
      'SERVER_PROTOCOL' => 'http'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('/?param=val', $he['REQUEST_URI']);
  }

  public function testHeadersSetQueryParamRequestUriAdd () {
    $env = array(
      'REQUEST_URI' => '/?a=b',
      'QUERY_STRING' => 'a=b',
      'SERVER_PROTOCOL' => 'http'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('/?a=b&param=val', $he['REQUEST_URI']);
  }

  public function testHeadersSetQueryParamRedirectQueryString() {
    $env = array(
      'REDIRECT_REQUEST_URI' => '_',
      'SERVER_PROTOCOL' => 'http'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val', $he['REDIRECT_QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryString() {
    $env = array('QUERY_STRING' => '');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamUnsetQueryString() {
    $env = array('REQUEST_URI' => '/');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringOverwrite () {
    $env = array('QUERY_STRING' => 'param=what');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringAdd () {
    $env = array('QUERY_STRING' => 'param1=what');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param1=what&param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringNoVal () {
    $env = array('QUERY_STRING' => 'param');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringMulti () {
    $env = array('QUERY_STRING' => 'p=v&a=b');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('p=v&a=b&param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringOverwriteMultiBegin () {
    $env = array('QUERY_STRING' => 'param=what&p=v');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val&p=v', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringOverwriteMultiMiddle () {
    $env = array('QUERY_STRING' => 'a=b&param=what&p=v');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('a=b&param=val&p=v', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringOverwriteMultiEnd () {
    $env = array('QUERY_STRING' => 'a=b&param=what');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('a=b&param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringAddMulti () {
    $env = array('QUERY_STRING' => 'param1=what');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param1=what&param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringNoValMulti () {
    $env = array('QUERY_STRING' => 'param');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamWithPath() {
    $env = array(
      'REQUEST_URI' => '/path/here?param=val',
      'QUERY_STRING' => 'param=val',
      'REDIRECT_QUERY_STRING' => 'param=val'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('hey', 'yo');
    $headersEnv = $headers->env();
    $this->assertEquals('/path/here?param=val&hey=yo', $headersEnv['REQUEST_URI']);
    $this->assertEquals('param=val&hey=yo', $headersEnv['QUERY_STRING']);
    $this->assertEquals('param=val&hey=yo', $headersEnv['REDIRECT_QUERY_STRING']);
  }

  public function testHeadersSetQueryParamWithFilePath() {
    $env = array(
      'REQUEST_URI' => '/path/here.php?param=val',
      'QUERY_STRING' => 'param=val',
      'REDIRECT_QUERY_STRING' => 'param=val'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('hey', 'yo');
    $headersEnv = $headers->env();
    $this->assertEquals('/path/here.php?param=val&hey=yo', $headersEnv['REQUEST_URI']);
    $this->assertEquals('param=val&hey=yo', $headersEnv['QUERY_STRING']);
    $this->assertEquals('param=val&hey=yo', $headersEnv['REDIRECT_QUERY_STRING']);
  }

  public function testHeadersSetQueryParamGET () {
    global $_GET;

    $_GET = array();
    $env = array('REQUEST_URI' => '/');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('val', $_GET['param']);
  }

  public function testHeadersSetQueryParamOverwriteGET () {
    global $_GET;

    $_GET = array('param' => 'there');
    $env = array('REQUEST_URI' => '/');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('val', $_GET['param']);
  }

  public function testHeadersSetQueryParamRequest() {
    global $_REQUEST;

    $_REQUEST = array();
    $env = array('REQUEST_URI' => '/');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->setQueryParam('param', 'val');
    $this->assertEquals('val', $_REQUEST['param']);
  }

  // setQueryParams

  public function testSetQueryParamsRequestUri() {
    $env = array('REQUEST_URI' => '/');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $qa = array();
    array_push($qa, "param2=val2");

    $headers->setQueryParams($qa);
    $he = $headers->env();
    $this->assertEquals('/?param2=val2', $he['REQUEST_URI']);
  }

  public function testSetQueryParamsQueryString () {
    $env = array('QUERY_STRING' => '');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $qa = array();
    array_push($qa, "param2=val2");

    $headers->setQueryParams($qa);
    $he = $headers->env();
    $this->assertEquals('param2=val2', $he['QUERY_STRING']);
  }

  public function testSetQueryParamsQueryStringMulti () {
    $env = array('QUERY_STRING' => '');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $qa = array();
    array_push($qa, "param1=val1");
    array_push($qa, "param2=val2");

    $headers->setQueryParams($qa);
    $he = $headers->env();
    $this->assertEquals('param1=val1&param2=val2', $he['QUERY_STRING']);
  }

  public function testSetQueryParamsQueryStringEmpty () {
    $env = array('QUERY_STRING' => '');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $qa = array();

    $headers->setQueryParams($qa);
    $he = $headers->env();
    $this->assertEquals('', $he['QUERY_STRING']);
  }

  public function testSetQueryParamsQueryStringMultiReplace () {
    $env = array('QUERY_STRING' => 'param2=what&param1=oh');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $qa = array();
    array_push($qa, "param1=val1");
    array_push($qa, "param2=val2");

    $headers->setQueryParams($qa);
    $he = $headers->env();
    $this->assertEquals('param2=val2&param1=val1', $he['QUERY_STRING']);
  }

  public function testSetQueryParamsQueryStringMultiPartialReplace () {
    $env = array('QUERY_STRING' => 'param2=what&oh=yeah&param1=oh');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $qa = array();
    array_push($qa, "param1=val1");
    array_push($qa, "param2=val2");

    $headers->setQueryParams($qa);
    $he = $headers->env();
    $this->assertEquals('param2=val2&oh=yeah&param1=val1', $he['QUERY_STRING']);
  }

  public function testHeadersClearQueryParamsRequestUri () {
    $env = array('REQUEST_URI' => '/?hey=yeah');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals('/', $he['REQUEST_URI']);
  }

  public function testHeadersClearQueryParamsEmptyRequestUri () {
    $env = array('REQUEST_URI' => '/');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals('/', $he['REQUEST_URI']);
  }

  public function testHeadersClearQueryParamsEmptyRequestUriHangingHatena () {
    $env = array('REQUEST_URI' => '/?');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals('/', $he['REQUEST_URI']);
  }

  public function testHeadersClearQueryParamsQueryString () {
    $env = array('QUERY_STRING' => 'heythere');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals('', $he['QUERY_STRING']);
  }

  public function testHeadersClearQueryParamsQueryStringEmpty () {
    $env = array('QUERY_STRING' => '');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals('', $he['QUERY_STRING']);
  }

  public function testHeadersClearQueryParamsQueryStringMulti () {
    $env = array('QUERY_STRING' => 'hey=there&oh=ok');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals('', $he['QUERY_STRING']);
  }

  public function testHeadersClearQueryParamsGET () {
    global $_GET;

    $_GET = array('hey' => 'there');
    $env = array('QUERY_STRING' => 'hey=there');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals(0, count($_GET));
  }

  public function testHeadersClearQueryParamsEmptyGET () {
    global $_GET;

    $_GET = array();
    $env = array('QUERY_STRING' => '');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals(0, count($_GET));
  }

  public function testHeadersRedirectLocationWithQueryPatternAndNoQuery () {
    $settings = array('url_pattern_name' => 'query');
    $env = array('QUERY_STRING' => '');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->url = 'google.com/test';
    $lang = 'ja';
    $expected = 'http://google.com/test?wovn=ja';
    $out = $headers->redirectLocation($lang);
    $this->assertEquals($expected, $out);
  }

  public function testHeadersRedirectLocationWithQueryPatternAndExistingQuery () {
    $settings = array('url_pattern_name' => 'query');
    $env = array('QUERY_STRING' => '?page=1');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->protocol = 'http';
    $headers->url = 'google.com/test?page=1';
    $lang = 'ja';
    $expected = 'http://google.com/test?page=1&wovn=ja';
    $out = $headers->redirectLocation($lang);
    $this->assertEquals($expected, $out);
  }

  public function testHeadersWithUseProxyTrue () {
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

  public function testHeadersWithUseProxyFalse () {
    $settings = array('use_proxy' => false);
    $env = array(
      'HTTP_X_FORWARDED_HOST' => 'ja.wovn.io',
      'HTTP_X_FORWARDED_PROTO' => 'https'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('localhost', $headers->unmaskedHost);
    $this->assertEquals('localhost', $headers->host);
    $this->assertEquals('http', $headers->protocol);
  }

  public function testHeadersWithUseProxyTrueButNoForwardedInfo () {
    $settings = array('use_proxy' => 1);
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

    $this->assertEquals('localhost', $headers->unmaskedHost);
    $this->assertEquals('localhost', $headers->host);
    $this->assertEquals('http', $headers->protocol);
  }

  public function testRemoveLangWithPathPattern () {
    list($store, $headers) = StoreAndHeadersFactory::fromFixture();

    $this->assertEquals('path', $store->settings['url_pattern_name']);

    $without_scheme = $headers->removeLang('wovn.io/ja', 'ja');
    $this->assertEquals('wovn.io/', $without_scheme);

    $with_scheme = $headers->removeLang('https://wovn.io/en/', 'en');
    $this->assertEquals('https://wovn.io/', $with_scheme);
  }

  public function testRemoveLangWithPathPatternAndChinese () {
    list($store, $headers) = StoreAndHeadersFactory::fromFixture();

    $this->assertEquals('path', $store->settings['url_pattern_name']);

    $traditional = $headers->removeLang('wovn.io/zh-cht', 'zh-CHT');
    $this->assertEquals('wovn.io/', $traditional);

    $simplified = $headers->removeLang('https://wovn.io/zh-CHS', 'zh-CHS');
    $this->assertEquals('https://wovn.io/', $simplified);
  }

  public function testRemoveLangWithQueryPattern () {
    $settings = array('url_pattern_name' => 'query');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

    $without_scheme = $headers->removeLang('wovn.io/?wovn=ja', 'ja');
    $this->assertEquals('wovn.io/', $without_scheme);

    $with_scheme = $headers->removeLang('http://minimaltech.co?wovn=en', 'en');
    $this->assertEquals('http://minimaltech.co', $with_scheme);
  }

  public function testRemoveLangWithQueryPatternAndChinese () {
    $settings = array('url_pattern_name' => 'query');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

    $traditional = $headers->removeLang('minimaltech.co/?wovn=zh-CHT', 'zh-CHT');
    $this->assertEquals('minimaltech.co/', $traditional);

    $simplified = $headers->removeLang('http://minimaltech.co?wovn=zh-chs', 'zh-CHS');
    $this->assertEquals('http://minimaltech.co', $simplified);
  }

  public function testRemoveLangWithSubdomainPattern () {
    $settings = array('url_pattern_name' => 'subdomain');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

    $without_scheme = $headers->removeLang('ja.minimaltech.co', 'ja');
    $this->assertEquals('minimaltech.co', $without_scheme);

    $with_scheme = $headers->removeLang('http://en.wovn.io/', 'en');
    $this->assertEquals('http://wovn.io/', $with_scheme);
  }

  public function testRemoveLangWithSubdomainPatternAndChinese () {
    $settings = array('url_pattern_name' => 'subdomain');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

    $traditional = $headers->removeLang('zh-cht.wovn.io', 'zh-CHT');
    $this->assertEquals('wovn.io', $traditional);

    $simplified = $headers->removeLang('https://zh-CHS.wovn.io', 'zh-CHS');
    $this->assertEquals('https://wovn.io', $simplified);
  }

  public function testRemoveLangWithCustomLang () {
    $settings = array('custom_lang_aliases' => array('ja' => 'ja-test'));
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

    $this->assertEquals('path', $store->settings['url_pattern_name']);

    $without_scheme = $headers->removeLang('wovn.io/ja-test', 'ja');
    $this->assertEquals('wovn.io/', $without_scheme);

    $with_scheme = $headers->removeLang('https://wovn.io/en/', 'en');
    $this->assertEquals('https://wovn.io/', $with_scheme);
  }

  public function testPathLangWithPathPattern () {
    $env = array(
      'SERVER_NAME' => 'wovn.io',
      'REQUEST_URI' => '/zh-CHT/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $this->assertEquals('path', $store->settings['url_pattern_name']);

    $pathlang = $headers->pathLang();
    $this->assertEquals('zh-CHT', $pathlang);
  }

  public function testPathLangWithPathPatternAndLangCodeNotAtBegining () {
    $env = array(
      'SERVER_NAME' => 'wovn.io',
      'REQUEST_URI' => '/thi/en/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $this->assertEquals('path', $store->settings['url_pattern_name']);

    $pathlang = $headers->pathLang();
    $this->assertEquals('', $pathlang);
  }

  public function testPathLangWithPathPatternAndLangNameInsteadOfLangCode () {
    $env = array(
      'SERVER_NAME' => 'wovn.io',
      'REQUEST_URI' => '/thai/en/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $this->assertEquals('path', $store->settings['url_pattern_name']);

    $pathlang = $headers->pathLang();
    $this->assertEquals('', $pathlang);
  }

  public function testPathLangWithQueryPattern () {
    $settings = array('url_pattern_name' => 'query');
    $env = array(
      'SERVER_NAME' => 'wovn.io',
      'REQUEST_URI' => '/test?wovn=zh-CHS'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $pathlang = $headers->pathLang();
    $this->assertEquals('zh-CHS', $pathlang);
  }

  public function testPathLangWithSubdomainPattern () {
    $settings = array('url_pattern_name' => 'subdomain');
    $env = array(
      'SERVER_NAME' => 'zh-cht.wovn.io',
      'REQUEST_URI' => '/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $pathlang = $headers->pathLang();
    $this->assertEquals('zh-CHT', $pathlang);
  }

  public function testPathLangWithSubdomainPatternAndLangNameInsteadOfLangCode () {
    $settings = array('url_pattern_name' => 'subdomain');
    $env = array(
      'SERVER_NAME' => 'thai.wovn.io',
      'REQUEST_URI' => '/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $pathlang = $headers->pathLang();
    $this->assertEquals('', $pathlang);
  }

  public function testPathLangWithUseProxyTrue () {
    $settings = array(
      'url_pattern_name' => 'subdomain',
      'use_proxy' => 1
    );
    $env = array('HTTP_X_FORWARDED_HOST' => 'en.minimaltech.co');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $pathlang = $headers->pathLang();
    $this->assertEquals('en', $pathlang);
  }

  public function testPathLangWithUseProxyFalse () {
    $settings = array(
      'url_pattern_name' => 'subdomain',
      'use_proxy' => false
    );
    $env = array(
      'SERVER_NAME' => 'ja.wovn.io',
      'HTTP_X_FORWARDED_HOST' => 'en.minimaltech.co'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $pathlang = $headers->pathLang();
    $this->assertEquals('ja', $pathlang);
  }

  public function testPathLangWithUseProxyTrueButNoForwardedHost () {
    $settings = array(
      'url_pattern_name' => 'subdomain',
      'use_proxy' => 1
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('japanese_server', $settings);

    $pathlang = $headers->pathLang();
    $this->assertEquals('ja', $pathlang);
  }

  public function testRequestOutWithUseProxyTrue () {
    $settings = array(
      'url_pattern_name' => 'subdomain',
      'use_proxy' => 1
    );
    $env = array('HTTP_X_FORWARDED_HOST' => 'en.minimaltech.co');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->requestOut();

    $he = $headers->env();
    $this->assertEquals('minimaltech.co', $he['HTTP_X_FORWARDED_HOST']);
    $this->assertEquals('localhost', $he['SERVER_NAME']);
  }

  public function testRequestOutWithUseProxyFalse () {
    $settings = array(
      'url_pattern_name' => 'subdomain',
      'use_proxy' => false
    );
    $env = array('HTTP_X_FORWARDED_HOST' => 'en.minimaltech.co');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->requestOut();

    $he = $headers->env();
    $this->assertEquals('en.minimaltech.co', $he['HTTP_X_FORWARDED_HOST']);
  }

  public function testRequestOutUrlPatternPath () {
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('japanese_path_request');

    $he = $headers->env();
    $this->assertEquals('/ja/mypage.php', $he['REQUEST_URI']);
    $this->assertEquals('/mypage.php', $he['REDIRECT_URL']);
    $this->assertEquals('/ja/index.php', $he['HTTP_REFERER']);

    $headers->requestOut();

    $he = $headers->env();
    $this->assertEquals('/mypage.php', $he['REQUEST_URI']);
    $this->assertEquals('/mypage.php', $he['REDIRECT_URL']);
    $this->assertEquals('/index.php', $he['HTTP_REFERER']);
  }

  public function testRequestOutUrlPatternQuery()
  {
    $settings = array('url_pattern_name' => 'query');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('japanese_query_request', $settings);

    $he = $headers->env();
    $this->assertEquals('?wovn=ja', $he['QUERY_STRING']);
    $this->assertEquals('/mypage.php?wovn=ja', $he['REQUEST_URI']);
    $this->assertEquals('/index.php?login=no&wovn=ja', $he['HTTP_REFERER']);

    $headers->requestOut();

    $he = $headers->env();
    $this->assertEquals('', $he['QUERY_STRING']);
    $this->assertEquals('/mypage.php', $he['REQUEST_URI']);
    $this->assertEquals('/index.php?login=no', $he['HTTP_REFERER']);
  }

  public function testHttpsProtocolOn () {
    $settings = array(
      'url_pattern_name' => 'subdomain',
      'use_proxy' => false
    );
    $env = array('HTTPS' => 'on');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('https', $headers->protocol);
  }

  public function testHttpsProtocol () {
    $settings = array(
      'url_pattern_name' => 'subdomain',
      'use_proxy' => false
    );
    $env = array('HTTPS' => 'random');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('https', $headers->protocol);
  }

  public function testHttpProtocol () {
    $settings = array(
      'url_pattern_name' => 'subdomain',
      'use_proxy' => false
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

    $this->assertEquals('http', $headers->protocol);
  }

  public function testHttpProtocolEmpty () {
    $settings = array(
      'url_pattern_name' => 'subdomain',
      'use_proxy' => false
    );
    $env = array('HTTPS' => '');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('http', $headers->protocol);
  }

  public function testHttpProtocolHttpsOff () {
    $settings = array(
      'url_pattern_name' => 'subdomain',
      'use_proxy' => false
    );
    $env = array('HTTPS' => 'off');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('http', $headers->protocol);
  }

  public function testRequestOutSubdomainPatternWithHTTP_REFERER () {
    $settings = array('url_pattern_name' => 'subdomain');
    $env = array(
      'HTTP_REFERER' => 'ja.minimaltech.co',
      'REQUEST_URI' => '/dummy'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('japanese_server', $settings, $env);

    $this->assertEquals('ja', $headers->pathLang());

    $headers->requestOut();

    $he = $headers->env();
    $this->assertEquals('minimaltech.co', $he['HTTP_REFERER']);
  }

  public function testRequestOutPathPatternWithHTTP_REFERER () {
    $settings = array('url_pattern_name' => 'path');
    $env = array(
      'HTTP_REFERER' => 'minimaltech.co/ja',
      'REQUEST_URI' => '/ja/dummy'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('ja', $headers->pathLang());

    $headers->requestOut();

    $he = $headers->env();
    $this->assertEquals('minimaltech.co/', $he['HTTP_REFERER']);
  }

  public function testRequestOutQueryPatternWithHTTP_REFERER () {
    $settings = array('url_pattern_name' => 'query');
    $env = array(
      'HTTP_REFERER' => 'minimaltech.co/?wovn=ja',
      'REQUEST_URI' => '/dummy?wovn=ja'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('ja', $headers->pathLang());

    $headers->requestOut();

    $he = $headers->env();
    $this->assertEquals('minimaltech.co/', $he['HTTP_REFERER']);
  }

  public function testResponseOutWithDefaultLangAndSubdomainPattern() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => '/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'subdomain');
    $env = array(
      'HTTP_HOST' => 'localhost',
      'SERVER_NAME' => 'localhost',
      'REQUEST_URI' => 'http://localhost/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(0, count($receivedHeaders));
  }

  public function testResponseOutWithNotDefaultLangAndSubdomainPatternWhenApacheNotUsed() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(false);
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'subdomain');
    $env = array(
      'HTTP_HOST' => 'fr.localhost',
      'SERVER_NAME' => 'fr.localhost',
      'REQUEST_URI' => 'http://fr.localhost/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(0, count($receivedHeaders));
  }

  public function testResponseOutWithNotDefaultLangAndSubdomainPatternWhenHeadersSent() {
    Wovnio\wovnphp\mock_headers_sent(true);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => '/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'subdomain');
    $env = array(
      'HTTP_HOST' => 'fr.localhost',
      'SERVER_NAME' => 'fr.localhost',
      'REQUEST_URI' => 'http://fr.localhost/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(0, count($receivedHeaders));
  }

  public function testResponseOutAbsoluteUrlWithNotDefaultLangAndSubdomainPattern() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => 'http://localhost/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'subdomain');
    $env = array(
      'HTTP_HOST' => 'fr.localhost',
      'SERVER_NAME' => 'fr.localhost',
      'REQUEST_URI' => 'http://fr.localhost/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(1, count($receivedHeaders));
    $this->assertEquals('Location: http://fr.localhost/index.php', $receivedHeaders[0]);
  }

  public function testResponseOutWithNotDefaultLangAndSubdomainPattern() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => '/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'subdomain');
    $env = array(
      'HTTP_HOST' => 'fr.localhost',
      'SERVER_NAME' => 'fr.localhost',
      'REQUEST_URI' => 'http://fr.localhost/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(1, count($receivedHeaders));
    $this->assertEquals('Location: http://fr.localhost/index.php', $receivedHeaders[0]);
  }

  public function testResponseOutWithNotDefaultAlreadyInRedirectLocationLangAndSubdomainPattern() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => 'http://fr.localhost/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'subdomain');
    $env = array(
      'HTTP_HOST' => 'fr.localhost',
      'SERVER_NAME' => 'fr.localhost',
      'REQUEST_URI' => 'http://fr.localhost/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(1, count($receivedHeaders));
    $this->assertEquals('Location: http://fr.localhost/index.php', $receivedHeaders[0]);
  }

  public function testResponseOutOutsideRedirectionWithNotDefaultLangAndSubdomainPattern() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => 'http://google.com/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'subdomain');
    $env = array(
      'HTTP_HOST' => 'fr.localhost',
      'SERVER_NAME' => 'fr.localhost',
      'REQUEST_URI' => 'http://fr.localhost/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(1, count($receivedHeaders));
    $this->assertEquals('Location: http://google.com/index.php', $receivedHeaders[0]);
  }

  public function testResponseOutWithNotDefaultAlreadyInRedirectLocationCustomLangAndSubdomainPattern() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => 'http://fr-test.localhost/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array(
      'url_pattern_name' => 'subdomain',
      'custom_lang_aliases' => array('fr' => 'fr-test')
    );
    $env = array(
      'HTTP_HOST' => 'fr-test.localhost',
      'SERVER_NAME' => 'fr-test.localhost',
      'REQUEST_URI' => 'http://fr-test.localhost/test'
    );
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(1, count($receivedHeaders));
    $this->assertEquals('Location: http://fr-test.localhost/index.php', $receivedHeaders[0]);
  }

  public function testResponseOutWithDefaultLangAndPathPattern() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => '/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'path');
    $env = array( 'REQUEST_URI' => '/test');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(0, count($receivedHeaders));
  }

  public function testResponseOutWithNotDefaultLangAndPathPatternWhenApacheNotUsed() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(false);
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'path');
    $env = array( 'REQUEST_URI' => '/fr/test');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(0, count($receivedHeaders));
  }

  public function testResponseOutWithNotDefaultLangAndPathPatternWhenHeadersSent() {
    Wovnio\wovnphp\mock_headers_sent(true);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => '/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'path');
    $env = array( 'REQUEST_URI' => '/fr/test');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(0, count($receivedHeaders));
  }

  public function testResponseOutWithNotDefaultLangAndPathPattern() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => '/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'path');
    $env = array( 'REQUEST_URI' => '/fr/test');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(1, count($receivedHeaders));
    $this->assertEquals('Location: /fr/index.php', $receivedHeaders[0]);
  }

  public function testResponseOutWithNotDefaultLangAlreadyInRedirectLocationAndPathPattern() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => '/fr/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'path');
    $env = array( 'REQUEST_URI' => '/fr/test');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(1, count($receivedHeaders));
    $this->assertEquals('Location: /fr/index.php', $receivedHeaders[0]);
  }

  public function testResponseOutWithDefaultLangAndQueryPattern() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => '/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'query');
    $env = array( 'REQUEST_URI' => '/test');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(0, count($receivedHeaders));
  }

  public function testResponseOutWithNotDefaultLangAndQueryPatternWhenApacheNotUsed() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(false);
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'query');
    $env = array( 'REQUEST_URI' => '/test?wovn=fr');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(0, count($receivedHeaders));
  }

  public function testResponseOutWithNotDefaultLangAndQueryPatternWhenHeadersSent() {
    Wovnio\wovnphp\mock_headers_sent(true);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => '/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'query');
    $env = array( 'REQUEST_URI' => '/test?wovn=fr');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(0, count($receivedHeaders));
  }

  public function testResponseOutWithNotDefaultLangAndQueryPattern() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => '/index.php'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'query');
    $env = array( 'REQUEST_URI' => '/test?wovn=fr');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(1, count($receivedHeaders));
    $this->assertEquals('Location: /index.php?wovn=fr', $receivedHeaders[0]);
  }

  public function testResponseOutWithNotDefaultLangAlreadyInRedirectLocationAndQueryPattern() {
    Wovnio\wovnphp\mock_headers_sent(false);
    Wovnio\wovnphp\mock_apache_response_headers(true, array(
      'Location' => '/index.php?wovn=fr'
    ));
    Wovnio\wovnphp\mock_header();

    $settings = array('url_pattern_name' => 'query');
    $env = array( 'REQUEST_URI' => '/test?wovn=fr');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $headers->responseOut();
    $receivedHeaders = Wovnio\wovnphp\get_headers_received_by_header_mock();

    $this->assertEquals(1, count($receivedHeaders));
    $this->assertEquals('Location: /index.php?wovn=fr', $receivedHeaders[0]);
  }

  public function testGetDocumentURIWithQueryPattern() {
    $settings = array(
      'url_pattern_name' => 'query',
      'query' => array('page=')
    );
    $env = array( 'REQUEST_URI' => '/en/path?page=1&wovn=vi');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings, $env);

    $this->assertEquals('/en/path', $headers->getDocumentURI());
  }

  public function testGetDocumentURIWithPathPattern() {
    $env = array( 'REQUEST_URI' => '/en/path?page=1');
    list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', array(), $env);

    $this->assertEquals('/path', $headers->getDocumentURI());
  }
}
