<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'src/wovnio/utils/request_handlers/CurlRequestHandler.php';
require_once 'test/helpers/CurlMock.php';
require_once 'test/helpers/StoreAndHeadersFactory.php';

use Wovnio\Test\Helpers\StoreAndHeadersFactory;
use Wovnio\Utils\RequestHandlers\CurlRequestHandler;

use PHPUnit\Framework\TestCase;

class CurlRequestHandlerTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        restoreCurl();
        restoreCurlExec();
    }

    private function sendRequest()
    {
        list($store, $headers) = StoreAndHeadersFactory::fromFixture('default');
        $handler = new CurlRequestHandler($store);
        return $handler->sendRequest('POST', 'https://wovn.global.ssl.fastly.net/v0/translation', array('body' => 'x'), 1.0);
    }

    public function testPostReturnsBodyOnHttp200()
    {
        mockCurlExec("HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n{\"body\":\"translated\"}", 200);

        list($response, $headers, $error) = $this->sendRequest();

        $this->assertEquals('{"body":"translated"}', $response);
        $this->assertEquals(array('status' => 'HTTP/1.1 200 OK', 'Content-Type' => 'application/json'), $headers);
        $this->assertNull($error);
    }

    public function testPostReturnsErrorOnHttp429()
    {
        mockCurlExec("HTTP/1.1 429 Too Many Requests\r\nRetry-After: 1\r\n\r\nToo Many Requests", 429);

        list($response, $headers, $error) = $this->sendRequest();

        $this->assertNull($response);
        $this->assertEquals(array('status' => 'HTTP/1.1 429 Too Many Requests', 'Retry-After' => '1'), $headers);
        $this->assertEquals('[cURL] Request failed (0-429).', $error);
    }

    public function testPostReturnsErrorOnHttp500()
    {
        mockCurlExec("HTTP/1.1 500 Internal Server Error\r\n\r\n{\"body\":\"should not be used\"}", 500);

        list($response, $headers, $error) = $this->sendRequest();

        $this->assertNull($response);
        $this->assertEquals(array('status' => 'HTTP/1.1 500 Internal Server Error'), $headers);
        $this->assertEquals('[cURL] Request failed (0-500).', $error);
    }

    public function testPostReturnsErrorOnTransportFailure()
    {
        mockCurlExec(false, 0, 'Operation timed out', 28);

        list($response, $headers, $error) = $this->sendRequest();

        $this->assertNull($response);
        $this->assertEquals(array(), $headers);
        $this->assertEquals('[cURL] Request failed (28-0).', $error);
    }

    public function testAvailable()
    {
        mockCurl(
            true,
            array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close'),
            array('http', 'https')
        );
        $this->assertTrue(CurlRequestHandler::available());
    }

    public function testNotAvailableBecauseExtensionNotLoaded()
    {
        mockCurl(
            false,
            array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close'),
            array('http', 'https')
        );
        $this->assertFalse(CurlRequestHandler::available());
    }

    public function testNotAvailableBecauseExtensionBecauseOfMissingFunctions()
    {
        mockCurl(
            true,
            array(),
            array('http', 'https')
        );
        $this->assertFalse(CurlRequestHandler::available());
    }

    public function testNotAvailableBecauseExtensionBecauseOfMissingProtocols()
    {
        mockCurl(
            true,
            array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close'),
            array()
        );
        $this->assertFalse(CurlRequestHandler::available());
    }
}
