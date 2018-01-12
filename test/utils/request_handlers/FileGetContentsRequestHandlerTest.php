<?php
require_once 'src/wovnio/utils/request_handlers/FileGetContentsRequestHandler.php';

class FileGetContentsRequestHandlerTest extends PHPUnit_Framework_TestCase {
  public function testPost() {
    $api_url = 'http://api.wovn.io/a/b';
    $data =  array(
      'url' => 'http://wovn.io/a/b',
      'token' => 'Tok3n',
      'lang_code' => 'en',
      'url_pattern' => 'path',
      'body' => '{"hello": "world"}'
    );

    $expected_content = gzencode(http_build_query($data));
    $expected_content_length = strlen($expected_content);
    $expected_http_context = array(
      'header' => "Accept-Encoding: gzip\r\nContent-type: application/octet-stream\r\nContent-Length: $expected_content_length",
      'method' => 'POST',
      'content' => $expected_content
    );
    $expected_response = '{"foo": "bar"}';
    $file_get_handler = $this->createMockedFileGetHandler($api_url, $expected_http_context, $expected_response);
    $timeout = 10;

    $response = $file_get_handler->sendRequest('POST', $api_url, $data, $timeout);
    $this->assertEquals($expected_response, $response);
  }

  private function createMockedFileGetHandler($api_url, $http_context, $response) {
    $builder = $this->getMockBuilder('Wovnio\Utils\RequestHandlers\FileGetContentsRequestHandler');
    $builder->setMethods(array('fileGetContents'));
    $file_get_handler = $builder->getMock();
    if (method_exists($this, 'registerMockObject')) {
      $this->registerMockObject($file_get_handler);
    } else {
      $this->mockObjects[] = $file_get_handler;
    }

    $file_get_handler->expects($this->once())
      ->method('fileGetContents')
      ->with(
        $this->equalTo($api_url),
        $this->equalTo($http_context)
      )
      ->willReturn($response);

    return $file_get_handler;
  }
}
