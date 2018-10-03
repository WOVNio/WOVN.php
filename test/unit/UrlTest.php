<?php
require_once 'test/helpers/StoreAndHeadersFactory.php';

require_once 'src/wovnio/wovnphp/Url.php';
require_once 'src/wovnio/wovnphp/Store.php';
require_once 'src/wovnio/wovnphp/Headers.php';
require_once 'src/wovnio/wovnphp/Lang.php';

use Wovnio\test\Helpers\StoreAndHeadersFactory;

use Wovnio\Wovnphp\Url;
use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Headers;
use Wovnio\Wovnphp\Lang;

class UrlTest extends PHPUnit_Framework_TestCase {
  private function getStarted ($pattern='path', $additional_env=array()) {
    $settings = array(
      'default_lang' => 'ja',
      'supported_langs' => array('en'),
      'url_pattern_name' => $pattern
    );

    return StoreAndHeadersFactory::fromFixture('default', $settings, $additional_env);
  }

  public function testAddLangCodeRelativePathWithPathPattern () {
    $uri = '/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/$lang/test"
    ));

    $this->assertEquals("/$lang$uri", Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeRelativePathWithLangCodeInsideAndPathPattern () {
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

  public function testAddLangCodeRelativePathWithQueryPattern () {
    $uri = '/index.php';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));

    $this->assertEquals("$uri?wovn=$lang", Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeRelativePathAndAnchorWithQueryPattern () {
    $uri = '/index.php#test';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));

    $this->assertEquals("/index.php?wovn=$lang#test", Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeRelativePathWithLangCodeInsideAndQueryPattern () {
    $uri = '/index.php?wovn=fr';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLOfDifferentHostWithSubdomainPattern () {
    $uri = 'http://google.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.my-site.com/test"
    ));

    $this->assertEquals('http://google.com/index.php', Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLOfDifferentHostWithPseudoLangCodeAndSubdomainPattern () {
    $uri = 'http://fr.google.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.my-site.com/test"
    ));

    $this->assertEquals('http://fr.google.com/index.php', Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLWithSubdomainPattern () {
    $uri = 'http://my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.my-site.com/test"
    ));

    $this->assertEquals('http://fr.my-site.com/index.php', Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLWithLangCodeInsideAndSubdomainPattern () {
    $uri = 'http://fr.my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.my-site.com/test"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLWithPathPattern () {
    $uri = 'http://my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/$lang/test"
    ));

    $this->assertEquals('http://my-site.com/fr/index.php', Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLWithLangCodeInsideAndPathPattern () {
    $uri = 'http://my-site.com/fr/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/$lang/test"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLWithQueryPattern () {
    $uri = 'http://my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
    ));

    $this->assertEquals("$uri?wovn=$lang", Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLAndAnchorWithQueryPattern () {
    $uri = 'http://my-site.com/index.php#test';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
    ));

    $this->assertEquals("http://my-site.com/index.php?wovn=$lang#test", Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPURLWithLangCodeInsideAndQueryPattern () {
    $uri = 'http://my-site.com/index.php?wovn=fr';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLOfDifferentHostWithSubdomainPattern () {
    $uri = 'https://google.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.my-site.com/test"
    ));

    $this->assertEquals('https://google.com/index.php', Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLOfDifferentHostWithPseudoLangCodeAndSubdomainPattern () {
    $uri = 'https://fr.google.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.my-site.com/test"
    ));

    $this->assertEquals('https://fr.google.com/index.php', Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLWithSubdomainPattern () {
    $uri = 'https://my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.my-site.com/test"
    ));

    $this->assertEquals('https://fr.my-site.com/index.php', Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndSubdomainPattern () {
    $uri = 'https://fr.my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.my-site.com/test"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLWithPathPattern () {
    $uri = 'https://my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/$lang/test"
    ));

    $this->assertEquals('https://my-site.com/fr/index.php', Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndPathPattern () {
    $uri = 'https://my-site.com/fr/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/$lang/test"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLWithQueryPattern () {
    $uri = 'https://my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/test?wovn=$lang"
    ));

    $this->assertEquals("$uri?wovn=$lang", Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLAndAnchorWithQueryPattern () {
    $uri = 'https://my-site.com/index.php#test';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/test?wovn=$lang"
    ));

    $this->assertEquals("https://my-site.com/index.php?wovn=$lang#test", Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndQueryPattern () {
    $uri = 'https://my-site.com/index.php?wovn=fr';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/test?wovn=$lang"
    ));

    $this->assertEquals($uri, Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteURLAndPortWithSubdomainPattern () {
    $uri = 'https://my-site.com:3000/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'HTTP_HOST' => "$lang.my-site.com:3000",
      'REQUEST_URI' => "https://$lang.my-site.com:3000/test"
    ));

    $this->assertEquals("https://$lang.my-site.com:3000/index.php", Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteURLAndPortWithPathPattern () {
    $uri = 'https://my-site.com:3000/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'HTTP_HOST' => 'my-site.com:3000',
      'REQUEST_URI' => "https://my-site.com:3000/$lang/test"
    ));

    $this->assertEquals("https://my-site.com:3000/$lang/index.php", Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeAbsoluteURLAndPortWithQueryPattern () {
    $uri = 'https://my-site.com:3000/index.php';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'HTTP_HOST' => 'my-site.com:3000',
      'REQUEST_URI' => "https://my-site.com:3000/test?wovn=$lang"
    ));

    $this->assertEquals("$uri?wovn=fr", Url::addLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeRelativePathWithLangCodeInsideAndPathPattern () {
    $uri = '/fr-test/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/$lang/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals($uri, Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeRelativePathWithQueryPattern () {
    $uri = '/index.php';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals("$uri?wovn=fr-test", Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeRelativePathAndAnchorWithQueryPattern () {
    $uri = '/index.php#test';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals("/index.php?wovn=fr-test#test", Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeRelativePathWithLangCodeInsideAndQueryPattern () {
    $uri = '/index.php?wovn=fr-test';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals($uri, Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLOfDifferentHostWithSubdomainPattern () {
    $uri = 'http://google.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.my-site.com/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals('http://google.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLOfDifferentHostWithPseudoLangCodeAndSubdomainPattern () {
    $uri = 'http://fr.google.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.my-site.com/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals('http://fr.google.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithSubdomainPattern () {
    $uri = 'http://my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.my-site.com/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals('http://fr-test.my-site.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithLangCodeInsideAndSubdomainPattern () {
    $uri = 'http://fr-test.my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.my-site.com/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals('http://fr-test.my-site.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithDifferentLangCodeInsideAndSubdomainPattern () {
    $uri = 'http://fr.my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.my-site.com/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals('http://fr.my-site.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithPathPattern () {
    $uri = 'http://my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/$lang/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals('http://my-site.com/fr-test/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithLangCodeInsideAndPathPattern () {
    $uri = 'http://my-site.com/fr-test/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/$lang/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals($uri, Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithDifferentLangCodeInsideAndPathPattern () {
    $uri = 'http://my-site.com/fr/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/$lang/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals('http://my-site.com/fr-test/fr/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithQueryPattern () {
    $uri = 'http://my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals("$uri?wovn=fr-test", Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLAndAnchorWithQueryPattern () {
    $uri = 'http://my-site.com/index.php#test';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals("http://my-site.com/index.php?wovn=fr-test#test", Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPURLWithDifferentLangCodeInsideAndQueryPattern () {
    $uri = 'http://my-site.com/index.php?wovn=fr';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals('http://my-site.com/index.php?wovn=fr&wovn=fr-test', Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLOfDifferentHostWithSubdomainPattern () {
    $uri = 'https://google.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.my-site.com/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals('https://google.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLOfDifferentHostWithPseudoLangCodeAndSubdomainPattern () {
    $uri = 'https://fr-test.google.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.my-site.com/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals('https://fr-test.google.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLWithSubdomainPattern () {
    $uri = 'https://my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.my-site.com/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals('https://fr-test.my-site.com/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndSubdomainPattern () {
    $uri = 'https://fr-test.my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.my-site.com/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals($uri, Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLWithPathPattern () {
    $uri = 'https://my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/$lang/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals('https://my-site.com/fr-test/index.php', Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndPathPattern () {
    $uri = 'https://my-site.com/fr-test/index.php';
    $lang = 'fr';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/$lang/test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals($uri, Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLWithQueryPattern () {
    $uri = 'https://my-site.com/index.php';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/test?wovn=fr-test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals("$uri?wovn=fr-test", Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLAndAnchorWithQueryPattern () {
    $uri = 'https://my-site.com/index.php#test';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/test?wovn=fr-test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals("https://my-site.com/index.php?wovn=fr-test#test", Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteHTTPSURLWithLangCodeInsideAndQueryPattern () {
    $uri = 'https://my-site.com/index.php?wovn=fr-test';
    $lang = 'fr';
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/test?wovn=fr-test"
    ));
    $store->settings['custom_lang_aliases'] = array('fr' => 'fr-test');

    $this->assertEquals($uri, Url::AddLangCode($uri, $store, $lang, $headers));
  }

  public function testAddLangCodeCustomLangCodeAbsoluteURLAndPortWithSubdomainPattern () {
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

  public function testAddLangCodeCustomLangCodeAbsoluteURLAndPortWithPathPattern () {
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

  public function testAddLangCodeCustomLangCodeAbsoluteURLAndPortWithQueryPattern () {
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

  public function testRemoveLangCodeRelativePathWithPathPattern () {
    $lang = 'fr';
    $expected_uri = '/index.php';
    $uri = "/$lang$expected_uri";
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/$lang/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeRelativePathWithLangCodeNotInsideAndPathPattern () {
    $lang = 'fr';
    $expected_uri = '/index.php';
    $uri = "$expected_uri";
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/$lang/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function tesRemoveLangCodeRelativePathWithQueryPattern () {
    $lang = 'fr';
    $expected_uri = '/index.php';
    $uri = "$expected_uri?wovn=$lang";
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeRelativePathWithLangCodeNotInsideAndQueryPattern () {
    $lang = 'fr';
    $expected_uri = '/index.php';
    $uri = "$expected_uri?wovn=$lang";
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "/test?wovn=$lang"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($expected_uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPURLWithSubdomainPattern () {
    $lang = 'fr';
    $expected_uri = 'http://my-site.com/index.php';
    $uri = "http://$lang.my-site.com/index.php";
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.my-site.com/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPURLWithLangCodeNotInsideAndSubdomainPattern () {
    $lang = 'fr';
    $expected_uri = 'http://my-site.com/index.php';
    $uri = $expected_uri;
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://$lang.my-site.com/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPURLWithPathPattern () {
    $lang = 'fr';
    $expected_url = 'http://my-site.com/index.php';
    $uri = 'http://my-site.com/fr/index.php';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/$lang/test"
    ));

    $this->assertEquals($expected_url, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPURLWithLangCodeNotInsideAndPathPattern () {
    $lang = 'fr';
    $expected_url = 'http://my-site.com/index.php';
    $uri = $expected_url;
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/$lang/test"
    ));

    $this->assertEquals($expected_url, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPURLWithQueryPattern () {
    $lang = 'fr';
    $expected_uri = 'http://my-site.com/index.php';
    $uri = "$expected_uri?wovn=$lang";
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPURLWithLangCodeNotInsideAndQueryPattern () {
    $lang = 'fr';
    $expected_uri = 'http://my-site.com/index.php';
    $uri = $expected_uri;
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "http://my-site.com/test?wovn=$lang"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPSURLWithSubdomainPattern () {
    $lang = 'fr';
    $expected_uri = 'https://my-site.com/index.php';
    $uri = 'https://fr.my-site.com/index.php';
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.my-site.com/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPSURLWithLangCodeNotInsideAndSubdomainPattern () {
    $lang = 'fr';
    $expected_uri = 'https://my-site.com/index.php';
    $uri = $expected_uri;
    $pattern = 'subdomain';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://$lang.my-site.com/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPSURLWithPathPattern () {
    $lang = 'fr';
    $expected_uri = 'https://my-site.com/index.php';
    $uri = 'https://my-site.com/fr/index.php';
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/$lang/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPSURLWithLangCodeNotInsideAndPathPattern () {
    $lang = 'fr';
    $expected_uri = 'https://my-site.com/index.php';
    $uri = $expected_uri;
    $pattern = 'path';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/$lang/test"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPSURLWithQueryPattern () {
    $lang = 'fr';
    $expected_uri = 'https://my-site.com/index.php';
    $uri = "$expected_uri?wovn=$lang";
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/test?wovn=$lang"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }

  public function testRemoveLangCodeAbsoluteHTTPSURLWithLangCodeNotInsideAndQueryPattern () {
    $lang = 'fr';
    $expected_uri = 'https://my-site.com/index.php';
    $uri = $expected_uri;
    $pattern = 'query';
    list($store, $headers) = $this->getStarted($pattern, array(
      'REQUEST_URI' => "https://my-site.com/test?wovn=fr$lang"
    ));

    $this->assertEquals($expected_uri, Url::removeLangCode($uri, $pattern, $lang));
  }
}
