<?php
  require_once 'test/helpers/StoreAndHeadersFactory.php';

  require_once 'src/wovnio/wovnphp/API.php';
  require_once 'src/wovnio/wovnphp/Utils.php';
  require_once 'src/wovnio/wovnphp/Lang.php';
  require_once 'src/wovnio/wovnphp/Url.php';
  require_once 'src/wovnio/wovnphp/Store.php';
  require_once 'src/wovnio/wovnphp/Headers.php';
  require_once 'src/wovnio/wovnphp/Lang.php';
  require_once 'src/wovnio/wovnphp/Url.php';
  require_once 'src/wovnio/html/HtmlConverter.php';
  require_once 'src/wovnio/html/HtmlReplaceMarker.php';
  require_once 'src/wovnio/utils/request_handlers/RequestHandlerFactory.php';
  require_once 'src/wovnio/utils/request_handlers/CurlRequestHandler.php';

  require_once 'src/wovnio/modified_vendor/SimpleHtmlDom.php';

  use Wovnio\Test\Helpers\StoreAndHeadersFactory;

  use Wovnio\Wovnphp\API;
  use Wovnio\Wovnphp\Utils;
  use Wovnio\Utils\RequestHandlers\RequestHandlerFactory;

  class APITest extends PHPUnit_Framework_TestCase {
    private function getMockAndRegister($originalClassName, $methods) {
      $builder = $this->getMockBuilder($originalClassName);
      $builder->setMethods($methods);
      $mockObject = $builder->getMock();
      if (method_exists($this, 'registerMockObject')) {
          $this->registerMockObject($mockObject);
      } else {
          $this->mockObjects[] = $mockObject;
      }
      return $mockObject;
    }

    protected function setUp() {
      RequestHandlerFactory::set_instance(NULL);
    }

    protected function tearDown() {
      RequestHandlerFactory::set_instance(NULL);
    }

    public function getExpectedUrl($store, $headers, $content) {
      $token = $store->settings['project_token'];
      $path = $headers->pathnameKeepTrailingSlash;
      $lang = $headers->lang();
      $body_hash = md5($content);
      ksort($store->settings);
      $settings_hash = md5(serialize($store->settings));
      $cache_key = rawurlencode("(token=$token&settings_hash=$settings_hash&body_hash=$body_hash&path=$path&lang=$lang)");

      return $store->settings['api_url'] . 'translation?cache_key=' . $cache_key;
    }

    public function testAPIExists() {
      $this->assertTrue(class_exists('Wovnio\Wovnphp\API'));
    }

    public function testTranslationURL() {
      list($store, $headers) = StoreAndHeadersFactory::fromFixture('japanese_path_request');
      $body = '<html></html>';
      $expected_api_url = $this->getExpectedUrl($store, $headers, $body);

      $this->assertTrue(API::url($store, $headers, $body) === $expected_api_url);
    }

    public function testTranslate() {
      list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
      $html = '<html><head></head><body><h1>en</h1></body></html>';
      $response = '{"body":"\u003Chtml\u003E\u003Chead\u003E\u003C/head\u003E\u003Cbody\u003E\u003Ch1\u003Efr\u003C/h1\u003E\u003C/body\u003E\u003C/html\u003E"}';
      $expected_url = $this->getExpectedUrl($store, $headers, $html);
      $token = $store->settings['project_token'];
      $expected_html = "<html><head><link rel=\"alternate\" hreflang=\"en\" href=\"http://localhost/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=$token&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script></head><body><h1>en</h1></body></html>";
      $expected_data = array(
        'url' => $headers->urlKeepTrailingSlash,
        'token' => $token,
        'lang_code' => $headers->lang(),
        'url_pattern' => 'path',
        'product' => WOVN_PHP_NAME,
        'version' => WOVN_PHP_VERSION,
        'body' => $expected_html
      );
      $expected_result = '<html><head></head><body><h1>fr</h1></body></html>';

      $mock = $this->getMockAndRegister('Wovnio\Utils\RequestHandlers\CurlRequestHandler', array('sendRequest'));
      $mock->expects($this->once())
           ->method('sendRequest')
           ->with(
             $this->equalTo('POST'),
             $this->equalTo($expected_url),
             $this->equalTo($expected_data),
             $this->equalTo(1.0)
           )
           ->willReturn($response);
      RequestHandlerFactory::set_instance($mock);

      $result = API::translate($store, $headers, $html);
      $this->assertEquals( $expected_result, $result);
    }

    public function testTranslateWithCustomLangAliases() {
      $settings = array('custom_lang_aliases' => array('ja' => 'ja-test'));
      list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);
      $token = $store->settings['project_token'];

      $html = '<html><head></head><body><h1>en</h1></body></html>';
      $response = '{"body":"\u003Chtml\u003E\u003Chead\u003E\u003C/head\u003E\u003Cbody\u003E\u003Ch1\u003Efr\u003C/h1\u003E\u003C/body\u003E\u003C/html\u003E"}';

      $expected_body = '<html><head><link rel="alternate" hreflang="en" href="http://localhost/"><script src="//j.wovn.io/1" data-wovnio="key=' . $token . '&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases={&quot;ja&quot;:&quot;ja-test&quot;}&amp;version=WOVN.php" data-wovnio-type="fallback_snippet" async></script></head><body><h1>en</h1></body></html>';
      $expected_url = $this->getExpectedUrl($store, $headers, $html);
      $expected_data = array(
        'url' => $headers->urlKeepTrailingSlash,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => 'path',
        'product' => WOVN_PHP_NAME,
        'version' => WOVN_PHP_VERSION,
        'body' => $expected_body,
        'custom_lang_aliases' => '{"ja":"ja-test"}'
      );
      $expected_result = '<html><head></head><body><h1>fr</h1></body></html>';

      $mock = $this->getMockAndRegister('Wovnio\Utils\RequestHandlers\CurlRequestHandler', array('sendRequest'));
      $mock->expects($this->once())
        ->method('sendRequest')
        ->with(
          $this->equalTo('POST'),
          $this->equalTo($expected_url),
          $this->equalTo($expected_data),
          $this->equalTo(1.0)
        )
        ->willReturn($response);
      RequestHandlerFactory::set_instance($mock);

      $result = API::translate($store, $headers, $html);
      $this->assertEquals($expected_result, $result);
    }

    public function testTranslateWithWovnIgnore() {
      list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
      $html = '<html><head></head><body><h1 wovn-ignore>en</h1>hello</body></html>';
      $response = '{"body":"\u003chtml\u003e\u003chead\u003e\u003c\u002fhead\u003e\u003cbody\u003e\u003ch1 wovn-ignore\u003e\u003c\u0021\u002d\u002d\u0020__wovn\u002dbackend\u002dignored\u002dkey\u002d0\u0020\u002d\u002d\u003e\u003c\u002fh1\u003eBonjour\u003c\u002fbody\u003e\u003c\u002fhtml\u003e"}';
      $expected_url = $this->getExpectedUrl($store, $headers, $html);
      $token = $store->settings['project_token'];
      $expected_html = "<html><head><link rel=\"alternate\" hreflang=\"en\" href=\"http://localhost/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=$token&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script></head><body><h1 wovn-ignore><!-- __wovn-backend-ignored-key-0 --></h1>hello</body></html>";
      $expected_data = array(
        'url' => $headers->urlKeepTrailingSlash,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => 'path',
        'product' => WOVN_PHP_NAME,
        'version' => WOVN_PHP_VERSION,
        'body' => $expected_html
      );

      $mock = $this->getMockAndRegister('Wovnio\Utils\RequestHandlers\CurlRequestHandler', array('sendRequest'));
      $mock->expects($this->once())
        ->method('sendRequest')
        ->with(
          $this->equalTo('POST'),
          $this->equalTo($expected_url),
          $this->equalTo($expected_data),
          $this->equalTo(1.0)
        )
        ->willReturn($response);
      RequestHandlerFactory::set_instance($mock);
      $expected_result = '<html><head></head><body><h1 wovn-ignore>en</h1>Bonjour</body></html>';

      $result = API::translate($store, $headers, $html);
      $this->assertEquals($expected_result, $result);
    }

    public function testTranslateWithErrorHandled() {
      list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
      $html = '<html><head></head><body><h1>en</h1></body></html>';
      $response = '{"missingBodyError":"\u003Chtml\u003E\u003Chead\u003E\u003C/head\u003E\u003Cbody\u003E\u003Ch1\u003Efr\u003C/h1\u003E\u003C/body\u003E\u003C/html\u003E"}';
      $expected_url = $this->getExpectedUrl($store, $headers, $html);

      $token = $store->settings['project_token'];
      $expected_html = '<html><head><link rel="alternate" hreflang="en" href="http://localhost/"><script src="//j.wovn.io/1" data-wovnio="key='.$token.'&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;version=WOVN.php" data-wovnio-type="fallback_snippet" async></script></head><body><h1>en</h1></body></html>';
      $expected_data = array(
        'url' => $headers->urlKeepTrailingSlash,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => 'path',
        'product' => WOVN_PHP_NAME,
        'version' => WOVN_PHP_VERSION,
        'body' => $expected_html
      );

      $mock = $this->getMockAndRegister('Wovnio\Utils\RequestHandlers\CurlRequestHandler', array('sendRequest'));
      $mock->expects($this->once())
           ->method('sendRequest')
           ->with(
             $this->equalTo('POST'),
             $this->equalTo($expected_url),
             $this->equalTo($expected_data),
             $this->equalTo(1.0)
           )
           ->willReturn($response);
      RequestHandlerFactory::set_instance($mock);

      $result = API::translate($store, $headers, $html);
      $this->assertEquals($expected_html, $result);
    }

    public function testTranslateWithConnectionErrorHandled() {
      list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
      $html = '<html><head></head><body><h1>en</h1></body></html>';
      $expected_url = $this->getExpectedUrl($store, $headers, $html);

      $token = $store->settings['project_token'];
      $expected_html = '<html><head><link rel="alternate" hreflang="en" href="http://localhost/"><script src="//j.wovn.io/1" data-wovnio="key='.$token.'&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;version=WOVN.php" data-wovnio-type="fallback_snippet" async></script></head><body><h1>en</h1></body></html>';
      $expected_data = array(
        'url' => $headers->urlKeepTrailingSlash,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => 'path',
        'product' => WOVN_PHP_NAME,
        'version' => WOVN_PHP_VERSION,
        'body' => $expected_html
      );

      $mock = $this->getMockAndRegister('Wovnio\Utils\RequestHandlers\CurlRequestHandler', array('sendRequest'));
      $mock->expects($this->once())
        ->method('sendRequest')
        ->with(
          $this->equalTo('POST'),
          $this->equalTo($expected_url),
          $this->equalTo($expected_data),
          $this->equalTo(1.0)
        )
        ->willReturn(null);
      RequestHandlerFactory::set_instance($mock);

      $result = API::translate($store, $headers, $html);
      $this->assertEquals($expected_html, $result);
    }

    public function testTranslateWithoutMakingAPICallBySetting() {
      $settings = array(
        'disable_api_request_for_default_lang' => true,
        'default_lang' => 'en'
      );
      list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

      $html = '<html><head></head><body><h1>en</h1></body></html>';
      $expected_result = '<html><head><link rel="alternate" hreflang="en" href="http://localhost/"><script src="//j.wovn.io/1" data-wovnio="key=123456&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;version=WOVN.php" async></script></head><body><h1>en</h1></body></html>';

      $mock = $this->getMockAndRegister('Wovnio\Utils\RequestHandlers\CurlRequestHandler', array('sendRequest'));
      $mock->expects($this->never())->method('sendRequest');
      RequestHandlerFactory::set_instance($mock);

      $result = API::translate($store, $headers, $html);
      $this->assertEquals($expected_result, $result);
    }

    public function testTranslateWhenDefaultLangAndSettingIsOff() {
      $settings = array(
        'disable_api_request_for_default_lang' => false,
        'default_lang' => 'en'
      );
      list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

      $html = '<html><head></head><body><h1>en</h1></body></html>';

      $expected_url = $this->getExpectedUrl($store, $headers, $html);
      $token = $store->settings['project_token'];
      $expected_html = '<html><head><link rel="alternate" hreflang="en" href="http://localhost/"><script src="//j.wovn.io/1" data-wovnio="key='.$token.'&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;version=WOVN.php" data-wovnio-type="fallback_snippet" async></script></head><body><h1>en</h1></body></html>';
      $response = '{"body":"<html><head><link rel=\"alternate\" hreflang=\"en\" href=\"http:\/\/localhost\/\"><script src=\'\/\/j.wovn.io\/1\' data-wovnio=\'key='.$token.'\' data-wovnio-type=\'fallback_snippet\' async><\/script><\/head><body><h1>fr<\/h1><\/body><\/html>"}';

      $expected_data = array(
        'url' => $headers->urlKeepTrailingSlash,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => 'path',
        'product' => WOVN_PHP_NAME,
        'version' => WOVN_PHP_VERSION,
        'body' => $expected_html
      );

      $mock = $this->getMockAndRegister('Wovnio\Utils\RequestHandlers\CurlRequestHandler', array('sendRequest'));
      $mock->expects($this->once())
        ->method('sendRequest')
        ->with(
          $this->equalTo('POST'),
          $this->equalTo($expected_url),
          $this->equalTo($expected_data),
          $this->equalTo(1.0)
        )
        ->willReturn($response);
      RequestHandlerFactory::set_instance($mock);

      $expected_result = '<html><head><link rel="alternate" hreflang="en" href="http://localhost/"><script src=\'//j.wovn.io/1\' data-wovnio=\'key='.$token.'\' data-wovnio-type=\'fallback_snippet\' async></script></head><body><h1>fr</h1></body></html>';

      $result = API::translate($store, $headers, $html);
      $this->assertEquals($expected_result, $result);
    }

    public function testTranslateWithSaveMemoryBySendingWovnIgnoreContent() {
      $settings = array('save_memory_by_sending_wovn_ignore_content' => true);
      list($store, $headers) = StoreAndHeadersFactory::fromFixture('default', $settings);

      $html = '<html><head></head><body><h1 wovn-ignore>ignore content</h1></body></html>';
      $response = '{"body":"\u003Chtml\u003E\u003Chead\u003E\u003C/head\u003E\u003Cbody\u003E\u003Ch1\u003Efr\u003C/h1\u003E\u003C/body\u003E\u003C/html\u003E"}';
      $expected_url = $this->getExpectedUrl($store, $headers, $html);
      $token = $store->settings['project_token'];
      $expected_html = "<html><head><link rel=\"alternate\" hreflang=\"en\" href=\"http://localhost/\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=$token&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script></head><body><h1 wovn-ignore>ignore content</h1></body></html>";
      $expected_data = array(
        'url' => $headers->urlKeepTrailingSlash,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => 'path',
        'product' => WOVN_PHP_NAME,
        'version' => WOVN_PHP_VERSION,
        'body' => $expected_html
      );
      $expected_result = '<html><head></head><body><h1>fr</h1></body></html>';

      $mock = $this->getMockAndRegister('Wovnio\Utils\RequestHandlers\CurlRequestHandler', array('sendRequest'));
      $mock->expects($this->once())
        ->method('sendRequest')
        ->with(
          $this->equalTo('POST'),
          $this->equalTo($expected_url),
          $this->equalTo($expected_data),
          $this->equalTo(1.0)
        )
        ->willReturn($response);
      RequestHandlerFactory::set_instance($mock);

      $result = API::translate($store, $headers, $html);
      $this->assertEquals($expected_result, $result);
    }
  }
