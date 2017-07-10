<?php
  require_once 'src/wovnio/wovnphp/API.php';
  require_once 'src/wovnio/wovnphp/Utils.php';
  require_once 'src/wovnio/wovnphp/Store.php';
  require_once 'src/wovnio/wovnphp/Headers.php';
  require_once 'src/wovnio/utils/request_handlers/RequestHandlerFactory.php';
  require_once 'src/wovnio/utils/request_handlers/CurlRequestHandler.php';

  use Wovnio\Wovnphp\API;
  use Wovnio\Wovnphp\Utils;
  use Wovnio\Wovnphp\Store;
  use Wovnio\Wovnphp\Headers;
  use Wovnio\Utils\RequestHandlers\RequestHandlerFactory;
  use Wovnio\Utils\RequestHandlers\CurlRequestHandler;

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

    public function testAPIExists() {
      $this->assertTrue(class_exists('Wovnio\Wovnphp\API'));
    }

    public function testTranslationURL() {
      $env = $this->getEnv('_path');
      list($store, $headers) = Utils::getStoreAndHeaders($env);
      $expected_api_url = $store->settings['api_url'] . 'translation';

      $this->assertTrue(API::url($store, API::ACTION_TRANSLATE) === $expected_api_url);
    }

    /* TODO should work
    public function testTranslate() {
      $env = $this->getEnv('_path');
      list($store, $headers) = Utils::getStoreAndHeaders($env);
      $html = '<html><head></head><body><h1>en</h1></body></html>';
      $response = '{"body":"\u003Chtml\u003E\u003Chead\u003E\u003C/head\u003E\u003Cbody\u003E\u003Ch1\u003Efr\u003C/h1\u003E\u003C/body\u003E\u003C/html\u003E"}';
      $expected_url = $store->settings['api_url'] . 'translation'
        . '?token=' . $store->settings['project_token']
        . '&setting_digest=' . md5(serialize(asort($store->settings)))
        . '&content_digest=' . md5($html);
      $expected_data = array(
        'url' => $headers->url,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => 'path',
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

    public function testTranslateWithErrorHandled() {
      $env = $this->getEnv('_path');
      list($store, $headers) = Utils::getStoreAndHeaders($env);
      $html = '<html><head></head><body><h1>en</h1></body></html>';
      $response = '{"missingBodyError":"\u003Chtml\u003E\u003Chead\u003E\u003C/head\u003E\u003Cbody\u003E\u003Ch1\u003Efr\u003C/h1\u003E\u003C/body\u003E\u003C/html\u003E"}';
      $expected_url = $store->settings['api_url'] . 'translation'
        . '?token=' . $store->settings['project_token']
        . '&setting_digest=' . md5(serialize(asort($store->settings)))
        . '&content_digest=' . md5($html);
      $expected_data = array(
        'url' => $headers->url,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => 'path',
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
    */
  }
