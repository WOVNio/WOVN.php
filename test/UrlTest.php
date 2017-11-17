<?php

require_once 'src/wovnio/wovnphp/Url.php';
require_once 'src/wovnio/wovnphp/Store.php';
require_once 'src/wovnio/wovnphp/Headers.php';
require_once 'src/wovnio/wovnphp/Lang.php';

use Wovnio\Wovnphp\Url;
use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Headers;
use Wovnio\Wovnphp\Lang;

class UrlTest extends PHPUnit_Framework_TestCase {
  private function getEnv($num="") {
    $env = array();
    $file = parse_ini_file(dirname(__FILE__) . '/mock_env' . $num . '.ini');
    $env = $file['env'];
    return $env;
  }

  private function getStarted ($pattern='path', $additional_env=array()) {
    $store = new Store();
    $store->settings['default_lang'] = 'ja';
    $store->settings['supported_langs'] = array('en');
    if ($pattern === 'query') {
      $store->settings['url_pattern_name'] = 'query';
      $store->settings['url_pattern_reg'] = "((\?.*&)|\?)wovn=(?P<lang>[^&]+)(&|$)";
    }
    if ($pattern === 'subdomain') {
      $store->settings['url_pattern_name'] = 'subdomain';
      $store->settings['url_pattern_reg'] = "^(?P<lang>[^.]+)\.";
    }
    $store->settings['project_token'] = 'KK9kZ';
    $env = array_merge($this->getEnv('_' . $pattern), $additional_env);
    $headers = new Headers($env, $store);
    return array($store, $env, $headers);
  }

  public function testAddLangCodeRelativePathWithPathPattern () {
    $uri = '/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/$lang/test"
    ));

    $this->assertEquals("/$lang$uri", Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeRelativePathWithLangCodeInsideAndPathPattern () {
    $uri = '/fr/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/$lang/test"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeRelativePathWithQueryPattern () {
    $uri = '/index.php';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));

    $this->assertEquals("$uri?wovn=$lang", Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeRelativePathAndAnchorWithQueryPattern () {
    $uri = '/index.php#test';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));

    $this->assertEquals("/index.php?wovn=$lang#test", Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeRelativePathWithLangCodeInsideAndQueryPattern () {
    $uri = '/index.php?wovn=fr';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLOfDifferentHostWithSubdomainPattern () {
    $uri = 'http://google.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.localhost.com/test"
    ));

    $this->assertEquals('http://google.com/index.php', Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLOfDifferentHostWithPseudoLangCodeAndSubdomainPattern () {
    $uri = 'http://fr.google.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.localhost.com/test"
    ));

    $this->assertEquals('http://fr.google.com/index.php', Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLWithSubdomainPattern () {
    $uri = 'http://localhost.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.localhost.com/test"
    ));

    $this->assertEquals('http://fr.localhost.com/index.php', Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLWithLangCodeInsideAndSubdomainPattern () {
    $uri = 'http://fr.localhost.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.localhost.com/test"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLWithPathPattern () {
    $uri = 'http://localhost.com/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://localhost.com/$lang/test"
    ));

    $this->assertEquals('http://localhost.com/fr/index.php', Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLWithLangCodeInsideAndPathPattern () {
    $uri = 'http://localhost.com/fr/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://localhost.com/$lang/test"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLWithQueryPattern () {
    $uri = 'http://localhost.com/index.php';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://localhost.com/test?wovn=$lang"
    ));

    $this->assertEquals("$uri?wovn=$lang", Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLAndAnchorWithQueryPattern () {
    $uri = 'http://localhost.com/index.php#test';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://localhost.com/test?wovn=$lang"
    ));

    $this->assertEquals("http://localhost.com/index.php?wovn=$lang#test", Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLWithLangCodeInsideAndQueryPattern () {
    $uri = 'http://localhost.com/index.php?wovn=fr';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://localhost.com/test?wovn=$lang"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLOfDifferentHostWithSubdomainPattern () {
    $uri = 'https://google.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.localhost.com/test"
    ));

    $this->assertEquals('https://google.com/index.php', Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLOfDifferentHostWithPseudoLangCodeAndSubdomainPattern () {
    $uri = 'https://fr.google.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.localhost.com/test"
    ));

    $this->assertEquals('https://fr.google.com/index.php', Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLWithSubdomainPattern () {
    $uri = 'https://localhost.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.localhost.com/test"
    ));

    $this->assertEquals('https://fr.localhost.com/index.php', Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndSubdomainPattern () {
    $uri = 'https://fr.localhost.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.localhost.com/test"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLWithPathPattern () {
    $uri = 'https://localhost.com/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://localhost.com/$lang/test"
    ));

    $this->assertEquals('https://localhost.com/fr/index.php', Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndPathPattern () {
    $uri = 'https://localhost.com/fr/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://localhost.com/$lang/test"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLWithQueryPattern () {
    $uri = 'https://localhost.com/index.php';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://localhost.com/test?wovn=$lang"
    ));

    $this->assertEquals("$uri?wovn=$lang", Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLAndAnchorWithQueryPattern () {
    $uri = 'https://localhost.com/index.php#test';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://localhost.com/test?wovn=$lang"
    ));

    $this->assertEquals("https://localhost.com/index.php?wovn=$lang#test", Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndQueryPattern () {
    $uri = 'https://localhost.com/index.php?wovn=fr';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://localhost.com/test?wovn=$lang"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteURLAndPortWithSubdomainPattern () {
    $uri = 'https://localhost.com:3000/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'HTTP_HOST' => "$lang.localhost.com:3000",
      'REQUEST_URI' => "https://$lang.localhost.com:3000/test"
    ));

    $this->assertEquals("https://$lang.localhost.com:3000/index.php", Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteURLAndPortWithPathPattern () {
    $uri = 'https://localhost.com:3000/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'HTTP_HOST' => 'localhost.com:3000',
      'REQUEST_URI' => "https://localhost.com:3000/$lang/test"
    ));

    $this->assertEquals("https://localhost.com:3000/$lang/index.php", Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteURLAndPortWithQueryPattern () {
    $uri = 'https://localhost.com:3000/index.php';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'HTTP_HOST' => 'localhost.com:3000',
      'REQUEST_URI' => "https://localhost.com:3000/test?wovn=$lang"
    ));

    $this->assertEquals("$uri?wovn=fr", Url::addLangCode($uri, $pattern, $lang, $headers));
  }

  public function testRemoveLangCodeRelativePathWithPathPattern () {
    $lang = 'fr';
    $expected_uri = '/index.php';
    $uri = "/$lang$expected_uri";
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/$lang/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeRelativePathWithLangCodeNotInsideAndPathPattern () {
    $lang = 'fr';
    $expected_uri = '/index.php';
    $uri = "$expected_uri";
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/$lang/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function tesRemoveLangCodeRelativePathWithQueryPattern () {
    $lang = 'fr';
    $expected_uri = '/index.php';
    $uri = "$expected_uri?wovn=$lang";
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeRelativePathWithLangCodeNotInsideAndQueryPattern () {
    $lang = 'fr';
    $expected_uri = '/index.php';
    $uri = "$expected_uri?wovn=$lang";
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($expected_uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPURLWithSubdomainPattern () {
    $lang = 'fr';
    $expected_uri = 'http://localhost.com/index.php';
    $uri = "http://$lang.localhost.com/index.php";
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.localhost.com/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPURLWithLangCodeNotInsideAndSubdomainPattern () {
    $lang = 'fr';
    $expected_uri = 'http://localhost.com/index.php';
    $uri = $expected_uri;
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.localhost.com/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPURLWithPathPattern () {
    $lang = 'fr';
    $expected_url = 'http://localhost.com/index.php';
    $uri = 'http://localhost.com/fr/index.php';
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://localhost.com/$lang/test"
    ));

    $this->assertEquals($expected_url, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPURLWithLangCodeNotInsideAndPathPattern () {
    $lang = 'fr';
    $expected_url = 'http://localhost.com/index.php';
    $uri = $expected_url;
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://localhost.com/$lang/test"
    ));

    $this->assertEquals($expected_url, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPURLWithQueryPattern () {
    $lang = 'fr';
    $expected_uri = 'http://localhost.com/index.php';
    $uri = "$expected_uri?wovn=$lang";
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://localhost.com/test?wovn=$lang"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPURLWithLangCodeNotInsideAndQueryPattern () {
    $lang = 'fr';
    $expected_uri = 'http://localhost.com/index.php';
    $uri = $expected_uri;
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://localhost.com/test?wovn=$lang"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPSURLWithSubdomainPattern () {
    $lang = 'fr';
    $expected_uri = 'https://localhost.com/index.php';
    $uri = 'https://fr.localhost.com/index.php';
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.localhost.com/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPSURLWithLangCodeNotInsideAndSubdomainPattern () {
    $lang = 'fr';
    $expected_uri = 'https://localhost.com/index.php';
    $uri = $expected_uri;
    $pattern = 'subdomain';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.localhost.com/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPSURLWithPathPattern () {
    $lang = 'fr';
    $expected_uri = 'https://localhost.com/index.php';
    $uri = 'https://localhost.com/fr/index.php';
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://localhost.com/$lang/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPSURLWithLangCodeNotInsideAndPathPattern () {
    $lang = 'fr';
    $expected_uri = 'https://localhost.com/index.php';
    $uri = $expected_uri;
    $pattern = 'path';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://localhost.com/$lang/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPSURLWithQueryPattern () {
    $lang = 'fr';
    $expected_uri = 'https://localhost.com/index.php';
    $uri = "$expected_uri?wovn=$lang";
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://localhost.com/test?wovn=$lang"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPSURLWithLangCodeNotInsideAndQueryPattern () {
    $lang = 'fr';
    $expected_uri = 'https://localhost.com/index.php';
    $uri = $expected_uri;
    $pattern = 'query';
    list($store, $env, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://localhost.com/test?wovn=fr$lang"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }
}