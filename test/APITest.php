<?php
  require_once 'src/wovnio/wovnphp/API.php';
  require_once 'src/wovnio/wovnphp/Utils.php';
  require_once 'src/wovnio/wovnphp/Lang.php';
  require_once 'src/wovnio/wovnphp/Url.php';
  require_once 'src/wovnio/wovnphp/Store.php';
  require_once 'src/wovnio/wovnphp/Headers.php';
  require_once 'src/wovnio/utils/request_handlers/RequestHandlerFactory.php';
  require_once 'src/wovnio/utils/request_handlers/CurlRequestHandler.php';

  use Wovnio\Wovnphp\API;
  use Wovnio\Wovnphp\Utils;
  use Wovnio\Utils\RequestHandlers\RequestHandlerFactory;

  class APITest extends PHPUnit_Framework_TestCase {
    private function getEnv($num="") {
      $env = array();
      $file = parse_ini_file(dirname(__FILE__) . '/mock_env' . $num . '.ini');
      $env = $file['env'];
      return $env;
    }

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
      $path = $headers->pathname;
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
      $env = $this->getEnv('_path');
      list($store, $headers) = Utils::getStoreAndHeaders($env);
      $body = '<html></html>';
      $expected_api_url = $this->getExpectedUrl($store, $headers, $body);

      $this->assertTrue(API::url($store, $headers, $body) === $expected_api_url);
    }

    public function testTranslate() {
      $env = $this->getEnv('_path');
      list($store, $headers) = Utils::getStoreAndHeaders($env);
      $html = '<html><head></head><body><h1>en</h1></body></html>';
      $response = '{"body":"\u003Chtml\u003E\u003Chead\u003E\u003C/head\u003E\u003Cbody\u003E\u003Ch1\u003Efr\u003C/h1\u003E\u003C/body\u003E\u003C/html\u003E"}';
      $expected_url = $this->getExpectedUrl($store, $headers, $html);
      $expected_data = array(
        'url' => $headers->url,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => 'query',
        'body' => $html
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
      $this->assertTrue($result === $expected_result);
    }

    public function testTranslateWithCustomLangAliases() {
      $env = $this->getEnv('_path');
      list($store, $headers) = Utils::getStoreAndHeaders($env);
      $store->settings['custom_lang_aliases'] = array('ja' => 'ja-test');

      $html = '<html><head></head><body><h1>en</h1></body></html>';
      $response = '{"body":"\u003Chtml\u003E\u003Chead\u003E\u003C/head\u003E\u003Cbody\u003E\u003Ch1\u003Efr\u003C/h1\u003E\u003C/body\u003E\u003C/html\u003E"}';
      $expected_url = $this->getExpectedUrl($store, $headers, $html);
      $expected_data = array(
        'url' => $headers->url,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => 'query',
        'body' => $html,
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

    public function testTranslateWithErrorHandled() {
      $env = $this->getEnv('_path');
      list($store, $headers) = Utils::getStoreAndHeaders($env);
      $html = '<html><head></head><body><h1>en</h1></body></html>';
      $response = '{"missingBodyError":"\u003Chtml\u003E\u003Chead\u003E\u003C/head\u003E\u003Cbody\u003E\u003Ch1\u003Efr\u003C/h1\u003E\u003C/body\u003E\u003C/html\u003E"}';
      $expected_url = $this->getExpectedUrl($store, $headers, $html);
      $expected_data = array(
        'url' => $headers->url,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => 'query',
        'body' => $html
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
      $this->assertTrue($result === NULL);
    }
  }
