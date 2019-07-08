<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'src/wovnio/utils/request_handlers/CurlRequestHandler.php';
require_once 'test/helpers/CurlMock.php';

use Wovnio\Utils\RequestHandlers\CurlRequestHandler;

class CurlRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        restoreCurl();
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
            "Content-Type: application/octet-stream",
            "Content-Length: $expected_content_length"
        );

        $expected_response = '{"foo": "bar"}';
        $timeout = 10;
        $curl_request_handler = $this->createMockedCurlRequestHandler($api_url, $expected_header, $expected_content, $timeout, $expected_response);

        list($response, $headers, $error) = $curl_request_handler->sendRequest('POST', $api_url, $data, $timeout);

        $this->assertEquals($expected_response, $response);
    }

    private function createMockedCurlRequestHandler($api_url, $header, $content, $timeout, $response)
    {
        $curl_request_handler = $this->getMockBuilder('\Wovnio\Utils\RequestHandlers\CurlRequestHandler')
            ->setMethods(array('post'))
            ->getMock();
        if (method_exists($this, 'registerMockObject')) {
            $this->registerMockObject($curl_request_handler);
        } else {
            $this->mockObjects[] = $curl_request_handler;
        }

        $curl_request_handler->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo($api_url),
                $this->equalTo($header),
                $this->equalTo($content),
                $this->equalTo($timeout)
            )
            ->willReturn(array($response, null, null));

        return $curl_request_handler;
    }

    public function testAvailable() {
        mockCurl(
            true,
            array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close'),
			array('http', 'https')
        );
		$this->assertTrue(CurlRequestHandler::available());
    }

    public function testNotAvailableBecauseExtensionNotLoaded() {
		mockCurl(
			false,
			array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close'),
			array('http', 'https')
		);
		$this->assertFalse(CurlRequestHandler::available());
	}

	public function testNotAvailableBecauseExtensionBecauseOfMissingFunctions() {
		mockCurl(
			true,
			array(),
			array('http', 'https')
		);
		$this->assertFalse(CurlRequestHandler::available());
	}

	public function testNotAvailableBecauseExtensionBecauseOfMissingProtocols() {
		mockCurl(
			true,
			array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close'),
			array()
		);
		$this->assertFalse(CurlRequestHandler::available());
	}

}
