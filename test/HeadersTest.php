<?php
require_once 'src/wovnio/wovnphp/Headers.php';
require_once 'src/wovnio/wovnphp/Lang.php';
require_once 'src/wovnio/wovnphp/Store.php';
use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Headers;

class HeadersTest extends PHPUnit_Framework_TestCase {
  protected function setUp() {
  }

  function createStore($pattern='path') {
    $store = new Store('./test/config.ini');
    $store->settings['default_lang'] = 'en';
    $store->settings['supported_langs'] = array('en');
    $store->settings['url_pattern_name'] = $pattern;
    $store->settings['project_token'] = 'KK9kZ';
    $store->settings = $store->updateSettings($store->settings);
    return $store;
  }

  public function testHeadersExists() {
    $this->assertTrue(class_exists('Wovnio\Wovnphp\Headers'));
  }

  public function testHeadersConstructor() {
  }


  public function testHeadersMatchQuery() {
    $store = $this->createStore();
    $store->settings['query'] = array('page=');
    $env = array();
    $env = $this->getEnv('_2');
    $env['REQUEST_URI'] = '/?page=1';
    $headers = new Headers($env, $store);

    $redisUrl = $headers->redisUrl;
    $this->assertEquals('ja.localhost?page=1', $redisUrl);
  }

  public function testHeadersMatchQueryEmptyQueryString() {
    $store = $this->createStore();
    $store->settings['query'] = array('page=');
    $env = array();
    $env = $this->getEnv('_2');
    $env['REQUEST_URI'] = '/';
    $headers = new Headers($env, $store);

    $redisUrl = $headers->redisUrl;
    $this->assertEquals('ja.localhost', $redisUrl);
  }

  public function testHeadersMatchQueryEmptyQuerySettings() {
    $store = $this->createStore();
    $store->settings['query'] = array();
    $env = array();
    $env = $this->getEnv('_2');
    $env['REQUEST_URI'] = '/?top=hey';
    $headers = new Headers($env, $store);

    $redisUrl = $headers->redisUrl;
    $this->assertEquals('ja.localhost', $redisUrl);
  }

  public function testHeadersMatchQueryWrongQueryParams() {
    $store = $this->createStore();
    $store->settings['query'] = array('page=');

    $env = $this->getEnv('_2');
    $env['REQUEST_URI'] = '/?top=yes';

    $headers = new Headers($env, $store);

    $redisUrl = $headers->redisUrl;
    $this->assertEquals('ja.localhost', $redisUrl);
  }

  public function testHeadersMatchQueryTwoQueryParams() {
    $store = $this->createStore();
    $store->settings['query'] = array('page=', 'top=');

    $env = $this->getEnv('_2');
    $env['REQUEST_URI'] = '/?page=1&top=yes';

    $headers = new Headers($env, $store);

    $redisUrl = $headers->redisUrl;
    $this->assertEquals('ja.localhost?page=1&top=yes', $redisUrl);
  }

  public function testHeadersMatchQueryTwoQueryParamsSorting() {
    $store = $this->createStore();
    $store->settings['query'] = array('a=', 'b=');

    $env = $this->getEnv('_2');
    $env['REQUEST_URI'] = '/?b=2&a=1';

    $headers = new Headers($env, $store);

    $redisUrl = $headers->redisUrl;
    $this->assertEquals('ja.localhost?a=1&b=2', $redisUrl);
  }

  public function testHeadersMatchQueryTwoQueryParamsSortingOneWrong() {
    $store = $this->createStore();
    $store->settings['query'] = array('a=', 'c=');

    $env = $this->getEnv('_2');
    $env['REQUEST_URI'] = '/?c=3&b=2&a=1';

    $headers = new Headers($env, $store);

    $redisUrl = $headers->redisUrl;
    $this->assertEquals('ja.localhost?a=1&c=3', $redisUrl);
  }

  public function testHeadersMatchQueryLongQueryString() {
    $store = $this->createStore();
    $store->settings['query'] = array('a=', 'b=', 'c=', 'd=', 'e=', 'f=', 'g=', 'h=');

    $env = $this->getEnv('_2');
    $env['REQUEST_URI'] = '/?e=5&d=4&c=3&b=2&a=1&f=6&g=7&h=8&z=10';

    $headers = new Headers($env, $store);

    $redisUrl = $headers->redisUrl;
    $this->assertEquals('ja.localhost?a=1&b=2&c=3&d=4&e=5&f=6&g=7&h=8', $redisUrl);
  }

  // setQueryParam

  public function testHeadersSetQueryParamRequestUri() {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv('_2');
    $env['REQUEST_URI'] = '/';
    $env['SERVER_PROTOCOL'] = 'http';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('/?param=val', $he['REQUEST_URI']);
  }

  public function testHeadersSetQueryParamRequestUriAdd () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv('_2');
    $env['REQUEST_URI'] = '/?a=b';
    $env['QUERY_STRING'] = 'a=b';
    $env['SERVER_PROTOCOL'] = 'http';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('/?a=b&param=val', $he['REQUEST_URI']);
  }

  public function testHeadersSetQueryParamRedirectQueryString() {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv('_2');
    $env['REDIRECT_QUERY_STRING'] = '';
    $env['SERVER_PROTOCOL'] = 'http';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val', $he['REDIRECT_QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryString() {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = '';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamUnsetQueryString() {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv('_2');
    $env['REQUEST_URI'] = '/';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringOverwrite () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'param=what';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringAdd () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'param1=what';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param1=what&param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringNoVal () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'param';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringMulti () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'p=v&a=b';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('p=v&a=b&param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringOverwriteMultiBegin () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'param=what&p=v';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val&p=v', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringOverwriteMultiMiddle () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'a=b&param=what&p=v';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('a=b&param=val&p=v', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringOverwriteMultiEnd () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'a=b&param=what';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('a=b&param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringAddMulti () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'param1=what';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param1=what&param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamQueryStringNoValMulti () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'param';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('param=val', $he['QUERY_STRING']);
  }

  public function testHeadersSetQueryParamWithPath() {
    $store = $this->createStore();
    $env = $this->getEnv();
    $env['REQUEST_URI'] = '/path/here?param=val';
    $env['QUERY_STRING'] = 'param=val';
    $env['REDIRECT_QUERY_STRING'] = 'param=val';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('hey', 'yo');
    $headersEnv = $headers->env();
    $this->assertEquals('/path/here?param=val&hey=yo', $headersEnv['REQUEST_URI']);
    $this->assertEquals('param=val&hey=yo', $headersEnv['QUERY_STRING']);
    $this->assertEquals('param=val&hey=yo', $headersEnv['REDIRECT_QUERY_STRING']);
  }

  public function testHeadersSetQueryParamWithFilePath() {
    $store = $this->createStore();
    $env = $this->getEnv();
    $env['REQUEST_URI'] = '/path/here.php?param=val';
    $env['QUERY_STRING'] = 'param=val';
    $env['REDIRECT_QUERY_STRING'] = 'param=val';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('hey', 'yo');
    $headersEnv = $headers->env();
    $this->assertEquals('/path/here.php?param=val&hey=yo', $headersEnv['REQUEST_URI']);
    $this->assertEquals('param=val&hey=yo', $headersEnv['QUERY_STRING']);
    $this->assertEquals('param=val&hey=yo', $headersEnv['REDIRECT_QUERY_STRING']);
  }

  public function testHeadersSetQueryParamGET () {
    global $_GET;
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['REQUEST_URI'] = '/';
    $_GET = array();
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('val', $_GET['param']);
  }

  public function testHeadersSetQueryParamOverwriteGET () {
    global $_GET;
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['REQUEST_URI'] = '/';
    $_GET = array();
    $_GET['param'] = 'there';
    $headers = new Headers($env, $store);

    $headers->setQueryParam('param', 'val');
    $he = $headers->env();
    $this->assertEquals('val', $_GET['param']);
  }

  public function testHeadersSetQueryParamRequest() {
    global $_REQUEST;
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['REQUEST_URI'] = '/';
    $_REQUEST = array();
    $headers = new Headers($env, $store);
    $headers->setQueryParam('param', 'val');
    $this->assertEquals('val', $_REQUEST['param']);
  }


  // setQueryParams

  public function testSetQueryParamsRequestUri() {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv('_2');
    $env['REQUEST_URI'] = '/';
    $headers = new Headers($env, $store);

    $qa = array();
    array_push($qa, "param2=val2");

    $headers->setQueryParams($qa);
    $he = $headers->env();
    $this->assertEquals('/?param2=val2', $he['REQUEST_URI']);
  }

  public function testSetQueryParamsQueryString () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv('_2');
    $env['QUERY_STRING'] = '';
    $headers = new Headers($env, $store);

    $qa = array();
    array_push($qa, "param2=val2");

    $headers->setQueryParams($qa);
    $he = $headers->env();
    $this->assertEquals('param2=val2', $he['QUERY_STRING']);
  }

  public function testSetQueryParamsQueryStringMulti () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = '';
    $headers = new Headers($env, $store);

    $qa = array();
    array_push($qa, "param1=val1");
    array_push($qa, "param2=val2");

    $headers->setQueryParams($qa);
    $he = $headers->env();
    $this->assertEquals('param1=val1&param2=val2', $he['QUERY_STRING']);
  }

  public function testSetQueryParamsQueryStringEmpty () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = '';
    $headers = new Headers($env, $store);

    $qa = array();

    $headers->setQueryParams($qa);
    $he = $headers->env();
    $this->assertEquals('', $he['QUERY_STRING']);
  }

  public function testSetQueryParamsQueryStringMultiReplace () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'param2=what&param1=oh';
    $headers = new Headers($env, $store);

    $qa = array();
    array_push($qa, "param1=val1");
    array_push($qa, "param2=val2");

    $headers->setQueryParams($qa);
    $he = $headers->env();
    $this->assertEquals('param2=val2&param1=val1', $he['QUERY_STRING']);
  }

  public function testSetQueryParamsQueryStringMultiPartialReplace () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'param2=what&oh=yeah&param1=oh';
    $headers = new Headers($env, $store);

    $qa = array();
    array_push($qa, "param1=val1");
    array_push($qa, "param2=val2");

    $headers->setQueryParams($qa);
    $he = $headers->env();
    $this->assertEquals('param2=val2&oh=yeah&param1=val1', $he['QUERY_STRING']);
  }

  public function testHeadersClearQueryParamsRequestUri () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['REQUEST_URI'] = '/?hey=yeah';
    $headers = new Headers($env, $store);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals('/', $he['REQUEST_URI']);
  }

  public function testHeadersClearQueryParamsEmptyRequestUri () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['REQUEST_URI'] = '/';
    $headers = new Headers($env, $store);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals('/', $he['REQUEST_URI']);
  }

  public function testHeadersClearQueryParamsEmptyRequestUriHangingHatena () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['REQUEST_URI'] = '/?';
    $headers = new Headers($env, $store);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals('/', $he['REQUEST_URI']);
  }

  public function testHeadersClearQueryParamsQueryString () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'heythere';
    $headers = new Headers($env, $store);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals('', $he['QUERY_STRING']);
  }

  public function testHeadersClearQueryParamsQueryStringEmpty () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = '';
    $headers = new Headers($env, $store);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals('', $he['QUERY_STRING']);
  }

  public function testHeadersClearQueryParamsQueryStringMulti () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'hey=there&oh=ok';
    $headers = new Headers($env, $store);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals('', $he['QUERY_STRING']);
  }

  public function testHeadersClearQueryParamsGET () {
    global $_GET;
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = 'hey=there';
    $_GET = array();
    $_GET['hey'] = 'there';
    $headers = new Headers($env, $store);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals(0, count($_GET));
  }

  public function testHeadersClearQueryParamsEmptyGET () {
    global $_GET;
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = '';
    $_GET = array();
    $headers = new Headers($env, $store);

    $headers->clearQueryParams();
    $he = $headers->env();
    $this->assertEquals(0, count($_GET));
  }

  public function testHeadersRedirectLocationWithQueryPatternAndNoQuery () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = '';
    $headers = new Headers($env, $store);
    $store->settings['url_pattern_name'] = 'query';
    $headers->url = 'google.com/test';
    $lang = 'ja';
    $expected = 'http://google.com/test?wovn=ja';
    $out = $headers->redirectLocation($lang);
    $this->assertEquals($expected, $out);
  }

  public function testHeadersRedirectLocationWithQueryPatternAndExistingQuery () {
    $store = $this->createStore();
    $env = array();
    $env = $this->getEnv();
    $env['QUERY_STRING'] = '?page=1';
    $headers = new Headers($env, $store);
    $store->settings['url_pattern_name'] = 'query';
    $headers->protocol = 'http';
    $headers->url = 'google.com/test?page=1';
    $lang = 'ja';
    $expected = 'http://google.com/test?page=1&wovn=ja';
    $out = $headers->redirectLocation($lang);
    $this->assertEquals($expected, $out);
  }

  public function testHeadersWithUseProxyTrue () {
    $store = $this->createStore();
    $store->settings['use_proxy'] = 1;

    $env = $this->getEnv();
    $env['HTTP_X_FORWARDED_HOST'] = 'ja.wovn.io';
    $env['HTTP_X_FORWARDED_PROTO'] = 'https';

    $headers = new Headers($env, $store);
    $this->assertEquals('ja.wovn.io', $headers->unmaskedHost);
    $this->assertEquals('ja.wovn.io', $headers->host);
    $this->assertEquals('https', $headers->protocol);
  }

  public function testHeadersWithUseProxyFalse () {
    $store = $this->createStore();
    $store->settings['use_proxy'] = false;

    $env = $this->getEnv();
    $env['HTTP_X_FORWARDED_HOST'] = 'ja.wovn.io';
    $env['HTTP_X_FORWARDED_PROTO'] = 'https';

    $headers = new Headers($env, $store);
    $this->assertEquals('ja.localhost', $headers->unmaskedHost);
    $this->assertEquals('ja.localhost', $headers->host);
    $this->assertEquals('http', $headers->protocol);
  }

  public function testHeadersWithUseProxyTrueButNoForwardedInfo () {
    $store = $this->createStore();
    $store->settings['use_proxy'] = 1;

    $env = $this->getEnv();

    $headers = new Headers($env, $store);
    $this->assertEquals('ja.localhost', $headers->unmaskedHost);
    $this->assertEquals('ja.localhost', $headers->host);
    $this->assertEquals('http', $headers->protocol);
  }

  public function testRemoveLangWithPathPattern () {
    $store = $this->createStore();
    $this->assertEquals('path', $store->settings['url_pattern_name']);
    $env = $this->getEnv();
    $headers = new Headers($env, $store);

    $without_scheme = $headers->removeLang('wovn.io/ja', 'ja');
    $this->assertEquals('wovn.io/', $without_scheme);

    $with_scheme = $headers->removeLang('https://wovn.io/en/', 'en');
    $this->assertEquals('https://wovn.io/', $with_scheme);
  }

  public function testRemoveLangWithPathPatternAndChinese () {
    $store = $this->createStore();
    $this->assertEquals('path', $store->settings['url_pattern_name']);
    $env = $this->getEnv();
    $headers = new Headers($env, $store);

    $traditional = $headers->removeLang('wovn.io/zh-cht', 'zh-CHT');
    $this->assertEquals('wovn.io/', $traditional);

    $simplified = $headers->removeLang('https://wovn.io/zh-CHS', 'zh-CHS');
    $this->assertEquals('https://wovn.io/', $simplified);
  }

  public function testRemoveLangWithQueryPattern () {
    $store = $this->createStore();
    $store->settings['url_pattern_name'] = 'query';
    $env = $this->getEnv();
    $headers = new Headers($env, $store);

    $without_scheme = $headers->removeLang('wovn.io/?wovn=ja', 'ja');
    $this->assertEquals('wovn.io/', $without_scheme);

    $with_scheme = $headers->removeLang('http://minimaltech.co?wovn=en', 'en');
    $this->assertEquals('http://minimaltech.co', $with_scheme);
  }

  public function testRemoveLangWithQueryPatternAndChinese () {
    $store = $this->createStore();
    $store->settings['url_pattern_name'] = 'query';
    $env = $this->getEnv();
    $headers = new Headers($env, $store);

    $traditional = $headers->removeLang('minimaltech.co/?wovn=zh-CHT', 'zh-CHT');
    $this->assertEquals('minimaltech.co/', $traditional);

    $simplified = $headers->removeLang('http://minimaltech.co?wovn=zh-chs', 'zh-CHS');
    $this->assertEquals('http://minimaltech.co', $simplified);
  }

  public function testRemoveLangWithSubdomainPattern () {
    $store = $this->createStore();
    $store->settings['url_pattern_name'] = 'subdomain';
    $env = $this->getEnv();
    $headers = new Headers($env, $store);

    $without_scheme = $headers->removeLang('ja.minimaltech.co', 'ja');
    $this->assertEquals('minimaltech.co', $without_scheme);

    $with_scheme = $headers->removeLang('http://en.wovn.io/', 'en');
    $this->assertEquals('http://wovn.io/', $with_scheme);
  }

  public function testRemoveLangWithSubdomainPatternAndChinese () {
    $store = $this->createStore();
    $store->settings['url_pattern_name'] = 'subdomain';
    $env = $this->getEnv();
    $headers = new Headers($env, $store);

    $traditional = $headers->removeLang('zh-cht.wovn.io', 'zh-CHT');
    $this->assertEquals('wovn.io', $traditional);

    $simplified = $headers->removeLang('https://zh-CHS.wovn.io', 'zh-CHS');
    $this->assertEquals('https://wovn.io', $simplified);
  }

  public function testPathLangWithPathPattern () {
    $store = $this->createStore();
    $this->assertEquals('path', $store->settings['url_pattern_name']);
    $env = $this->getEnv();
    $env['SERVER_NAME'] = 'wovn.io';
    $env['REQUEST_URI'] = '/zh-CHT/test';
    $headers = new Headers($env, $store);

    $pathlang = $headers->pathLang();
    $this->assertEquals('zh-CHT', $pathlang);
  }

  public function testPathLangWithPathPatternAndLangCodeNotAtBegining () {
    $store = $this->createStore();
    $this->assertEquals('path', $store->settings['url_pattern_name']);
    $env = $this->getEnv();
    $env['SERVER_NAME'] = 'wovn.io';
    $env['REQUEST_URI'] = '/thi/en/test';
    $headers = new Headers($env, $store);

    $pathlang = $headers->pathLang();
    $this->assertEquals('', $pathlang);
  }

  public function testPathLangWithPathPatternAndLangNameInsteadOfLangCode () {
    $store = $this->createStore();
    $this->assertEquals('path', $store->settings['url_pattern_name']);
    $env = $this->getEnv();
    $env['SERVER_NAME'] = 'wovn.io';
    $env['REQUEST_URI'] = '/thai/test';
    $headers = new Headers($env, $store);

    $pathlang = $headers->pathLang();
    $this->assertEquals('', $pathlang);
  }

  public function testPathLangWithQueryPattern () {
    $store = $this->createStore();
    $store->settings['url_pattern_reg'] = "((\?.*&)|\?)wovn=(?P<lang>[^&]+)(&|$)";
    $env = $this->getEnv();
    $env['SERVER_NAME'] = 'wovn.io';
    $env['REQUEST_URI'] = '/test?wovn=zh-CHS';
    $headers = new Headers($env, $store);

    $pathlang = $headers->pathLang();
    $this->assertEquals('zh-CHS', $pathlang);
  }

  public function testPathLangWithSubdomainPattern () {
    $store = $this->createStore('subdomain');
    $env = $this->getEnv();
    $env['SERVER_NAME'] = 'zh-cht.wovn.io';
    $env['REQUEST_URI'] = '/test';
    $headers = new Headers($env, $store);

    $pathlang = $headers->pathLang();
    $this->assertEquals('zh-CHT', $pathlang);
  }

  public function testPathLangWithSubdomainPatternAndLangNameInsteadOfLangCode () {
    $store = $this->createStore('subdomain');
    $env = $this->getEnv();
    $env['SERVER_NAME'] = 'thai.wovn.io';
    $env['REQUEST_URI'] = '/test';
    $headers = new Headers($env, $store);

    $pathlang = $headers->pathLang();
    $this->assertEquals('', $pathlang);
  }

  public function testPathLangWithUseProxyTrue () {
    $store = $this->createStore();
    $store->settings['url_pattern_name'] = 'subdomain';
    $store->settings['url_pattern_reg'] = "^(?P<lang>[^.]+)\.";
    $store->settings['use_proxy'] = 1;

    $env = $this->getEnv();
    $env['HTTP_X_FORWARDED_HOST'] = 'en.minimaltech.co';

    $headers = new Headers($env, $store);
    $pathlang = $headers->pathLang();
    $this->assertEquals('en', $pathlang);
  }

  public function testPathLangWithUseProxyFalse () {
    $store = $this->createStore();
    $store->settings['use_proxy'] = false;
    $store->settings['url_pattern_name'] = 'subdomain';
    $store->settings['url_pattern_reg'] = "^(?P<lang>[^.]+)\.";

    $env = $this->getEnv();
    $env['SERVER_NAME'] = 'ja.wovn.io';
    $env['HTTP_X_FORWARDED_HOST'] = 'en.minimaltech.co';

    $headers = new Headers($env, $store);
    $pathlang = $headers->pathLang();
    $this->assertEquals('ja', $pathlang);
  }

  public function testPathLangWithUseProxyTrueButNoForwardedHost () {
    $store = $this->createStore();
    $store->settings['url_pattern_name'] = 'subdomain';
    $store->settings['url_pattern_reg'] = "^(?P<lang>[^.]+)\.";
    $store->settings['use_proxy'] = 1;

    $env = $this->getEnv();

    $headers = new Headers($env, $store);
    $pathlang = $headers->pathLang();
    $this->assertEquals('ja', $pathlang);
  }

  public function testRequestOutWithUseProxyTrue () {
    $store = $this->createStore();
    $store->settings['url_pattern_name'] = 'subdomain';
    $store->settings['url_pattern_reg'] = "^(?P<lang>[^.]+)\.";
    $store->settings['use_proxy'] = 1;

    $env = $this->getEnv();
    $env['HTTP_X_FORWARDED_HOST'] = 'en.minimaltech.co';
    $_SERVER['REQUEST_URI'] = $env['REQUEST_URI'];

    $headers = new Headers($env, $store);
    $includePath = 'dummy';
    $headers->requestOut($includePath);
    $this->assertEquals('minimaltech.co', $env['HTTP_X_FORWARDED_HOST']);
  }

  public function testRequestOutWithUseProxyFalse () {
    $store = $this->createStore();
    $store->settings['use_proxy'] = false;
    $store->settings['url_pattern_name'] = 'subdomain';
    $store->settings['url_pattern_reg'] = "^(?P<lang>[^.]+)\.";

    $env = $this->getEnv();
    $env['HTTP_X_FORWARDED_HOST'] = 'en.minimaltech.co';
    $_SERVER['REQUEST_URI'] = $env['REQUEST_URI'];

    $headers = new Headers($env, $store);
    $includePath = 'dummy';
    $headers->requestOut($includePath);
    $this->assertEquals('en.minimaltech.co', $env['HTTP_X_FORWARDED_HOST']);
  }

  public function testHttpsProtocolOn () {
    $store = $this->createStore();
    $store->settings['use_proxy'] = false;
    $store->settings['url_pattern_name'] = 'subdomain';
    $store->settings['url_pattern_reg'] = "^(?P<lang>[^.]+)\.";

    $env = $this->getEnv();
    $env['HTTPS'] = 'on';

    $headers = new Headers($env, $store);
    $this->assertEquals('https', $headers->protocol);
  }

  public function testHttpsProtocol () {
    $store = $this->createStore();
    $store->settings['use_proxy'] = false;
    $store->settings['url_pattern_name'] = 'subdomain';
    $store->settings['url_pattern_reg'] = "^(?P<lang>[^.]+)\.";

    $env = $this->getEnv();
    $env['HTTPS'] = 'random';

    $headers = new Headers($env, $store);
    $this->assertEquals('https', $headers->protocol);
  }

  public function testHttpProtocol () {
    $store = $this->createStore();
    $store->settings['use_proxy'] = false;
    $store->settings['url_pattern_name'] = 'subdomain';
    $store->settings['url_pattern_reg'] = "^(?P<lang>[^.]+)\.";

    $env = $this->getEnv();

    $headers = new Headers($env, $store);
    $this->assertEquals('http', $headers->protocol);
  }

  public function testHttpProtocolEmpty () {
    $store = $this->createStore();
    $store->settings['use_proxy'] = false;
    $store->settings['url_pattern_name'] = 'subdomain';
    $store->settings['url_pattern_reg'] = "^(?P<lang>[^.]+)\.";

    $env = $this->getEnv();
    $env['HTTPS'] = '';

    $headers = new Headers($env, $store);
    $this->assertEquals('http', $headers->protocol);
  }

  public function testHttpProtocolHttpsOff () {
    $store = $this->createStore();
    $store->settings['use_proxy'] = false;
    $store->settings['url_pattern_name'] = 'subdomain';
    $store->settings['url_pattern_reg'] = "^(?P<lang>[^.]+)\.";

    $env = $this->getEnv();
    $env['HTTPS'] = 'off';

    $headers = new Headers($env, $store);
    $this->assertEquals('http', $headers->protocol);
  }

  private function getEnv($num="") {
    $env = array();
    $file = parse_ini_file(dirname(__FILE__) . '/mock_env' . $num . '.ini');
    $env = $file['env'];
    return $env;
  }

  public function testRequestOutSubdomainPatternWithHTTP_REFERER () {
    $includePath = '/dummy';

    $store = $this->createStore();
    $store->settings['url_pattern_name'] = 'subdomain';
    $store->settings['url_pattern_reg'] = "^(?P<lang>[^.]+)\.";

    $env = $this->getEnv();
    $env['HTTP_REFERER'] = 'ja.minimaltech.co';
    $env['REQUEST_URI'] = $includePath;
    $_SERVER['REQUEST_URI'] = $env['REQUEST_URI'];

    $headers = new Headers($env, $store);

    $this->assertEquals('ja', $headers->pathLang());
    $headers->requestOut($includePath);
    $this->assertEquals('minimaltech.co', $env['HTTP_REFERER']);
  }

  public function testRequestOutPathPatternWithHTTP_REFERER () {
    $includePath = '/ja/dummy';

    $store = $this->createStore();
    $store->settings['url_pattern_name'] = 'path';
    $store->settings['url_pattern_reg'] = '\/(?P<lang>[^\/.]+)(\/|\?|$)';

    $env = $this->getEnv();
    $env['HTTP_REFERER'] = 'minimaltech.co/ja';
    $env['REQUEST_URI'] = $includePath;
    $_SERVER['REQUEST_URI'] = $env['REQUEST_URI'];

    $headers = new Headers($env, $store);

    $this->assertEquals('ja', $headers->pathLang());
    $headers->requestOut($includePath);
    $this->assertEquals('minimaltech.co/', $env['HTTP_REFERER']);
  }

  public function testRequestOutQueryPatternWithHTTP_REFERER () {
    $includePath = '/dummy?wovn=ja';

    $store = $this->createStore();
    $store->settings['url_pattern_name'] = 'query';
    $store->settings['url_pattern_reg'] = '((\?.*&)|\?)wovn=(?P<lang>[^&]+)(&|$)';

    $env = $this->getEnv();
    $env['HTTP_REFERER'] = 'minimaltech.co/?wovn=ja';
    $env['REQUEST_URI'] = $includePath;
    $_SERVER['REQUEST_URI'] = $env['REQUEST_URI'];

    $headers = new Headers($env, $store);

    $this->assertEquals('ja', $headers->pathLang());
    $headers->requestOut($includePath);
    $this->assertEquals('minimaltech.co/', $env['HTTP_REFERER']);
  }
}
