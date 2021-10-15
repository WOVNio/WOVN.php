<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'src/wovnio/utils/request_handlers/CurlRequestHandler.php';
require_once 'test/helpers/CurlMock.php';

use Wovnio\Utils\RequestHandlers\CurlRequestHandler;

use PHPUnit\Framework\TestCase;

class CurlRequestHandlerTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        restoreCurl();
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
