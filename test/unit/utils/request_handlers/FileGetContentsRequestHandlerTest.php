<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'src/wovnio/utils/request_handlers/FileGetContentsRequestHandler.php';
require_once 'test/helpers/FileGetContentsMock.php';

use Wovnio\Utils\RequestHandlers\FileGetContentsRequestHandler;

class FileGetContentsRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        restoreFileGetContents();
    }

    public function testPost()
    {
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
        $expected_header = array(
            'Content-Type: application/octet-stream',
            "Content-Length: $expected_content_length"
        );
        $expected_response = '{"foo": "bar"}';
        $timeout = 10;
        $file_get_handler = $this->createMockedFileGetHandler($api_url, $expected_header, $expected_content, $timeout, $expected_response);

        list($response, $headers, $error)  = $file_get_handler->sendRequest('POST', $api_url, $data, $timeout);
        $this->assertEquals($expected_response, $response);
    }

    private function createMockedFileGetHandler($api_url, $header, $content, $timeout, $response)
    {
        $file_get_handler = $this->getMockBuilder('\Wovnio\Utils\RequestHandlers\FileGetContentsRequestHandler')
            ->setMethods(array('post'))
            ->getMock();

        if (method_exists($this, 'registerMockObject')) {
            $this->registerMockObject($file_get_handler);
        } else {
            $this->mockObjects[] = $file_get_handler;
        }

        $file_get_handler
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo($api_url),
                $this->equalTo($header),
                $this->equalTo($content),
                $this->equalTo($timeout)
            )
            ->willReturn(array($response, null, null));

        return $file_get_handler;
    }

    private function assertPostRequest($file_get_contents_response, $expected_response, $expected_headers, $expected_error)
    {
        $sut = $this->getMock('Wovnio\Utils\RequestHandlers\FileGetContentsRequestHandler', array('fileGetContents'));
        $body = '<html><head></head><body><h1>Congratulations!</h1></body></html>';
        $data = array(
            'url' => 'https://api.wovn.io/v0/translations/?cache_key=12232015',
            'token' => 'v0Y3u',
            'lang_code' => 'fr',
            'url_pattern' => 'path',
            'lang_param_name' => 'wovn',
            'product' => WOVN_PHP_NAME,
            'version' => WOVN_PHP_VERSION,
            'body' => $body
        );

        $that = &$this;
        $sut->expects($this->once())
			->method('fileGetContents')
			->will($this->returnCallback(function ($uri, $http_context) use (&$that, &$data, &$file_get_contents_response, &$expected_headers) {
                $http_context_array = stream_context_get_options($http_context)['http'];
                $http_headers = explode("\r\n", $http_context_array['header']);
                $formatted_data = http_build_query($data);
                $compressed_data = gzencode($formatted_data);
                $content_length = strlen($compressed_data);
                $expected_http_headers = array(
                    'Content-Type: application/octet-stream',
                    "Content-Length: $content_length",
                    'Accept-Encoding: gzip'
                );

                $that->assertEquals($data['url'], $uri);
                $that->assertEquals($expected_http_headers, $http_headers);
                $that->assertEquals('POST', $http_context_array['method']);
                $that->assertEquals($compressed_data, $http_context_array['content']);
                $that->assertEquals(1, $http_context_array['timeout']);

                return array($file_get_contents_response, $expected_headers);
            }));

        list($response, $response_headers, $response_error) = $sut->sendRequest('POST', $data['url'], $data, 1);

        $this->assertEquals($expected_response, $response);
        $this->assertEquals($expected_headers, $response_headers);
        $this->assertEquals($expected_error, $response_error);
    }

    public function testAvailable()
    {
        mockFileGetContents(true);
        $this->assertTrue(FileGetContentsRequestHandler::available());
    }

    public function testNotAvailable()
    {
        mockFileGetContents(false);
        $this->assertFalse(FileGetContentsRequestHandler::available());
    }

    public function testSendRequestPost()
    {
        $formatted_response = array(
            'body' =>'<html><head></head><body><h1>Félicitations !</h1></body></html>'
        );
        $expected_response = json_encode($formatted_response);
        $compressed_response = gzencode($expected_response);
        $expected_headers = array(
            'HTTP/1.0 200',
            'Content-Encoding: gzip'
        );

        $this->assertPostRequest($compressed_response, $expected_response, $expected_headers, null);
    }

    public function testSendRequestPostWithError()
    {
        $error_code = 500;
        $error_headers = array(
            "HTTP/1.0 $error_code"
        );
        $expected_error = "[fgc] Request failed ($error_code)";

        $this->assertPostRequest(false, null, $error_headers, $expected_error);
    }
}
