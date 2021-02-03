<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'src/wovnio/utils/request_handlers/RequestHandlerFactory.php';
require_once 'test/helpers/CurlMock.php';
require_once 'test/helpers/FileGetContentsMock.php';

use Wovnio\Utils\RequestHandlers\RequestHandlerFactory;

use PHPUnit\Framework\TestCase;

class RequestHandlerFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        restoreCurl();
        restoreFileGetContents();
        RequestHandlerFactory::setInstance(null);
    }

    private function setCurlAvailability($available)
    {
        $curl_funcs = $available ? array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close') : array();
        $curl_protocols = $available ? array('http', 'https') : array();
        mockCurl($available, $curl_funcs, $curl_protocols);
    }

    private function setFileGetContentsAvailability($available)
    {
        mockFileGetContents($available);
    }

    public function testCreatesCurlRequestHandlerByDefault()
    {
        $this->setCurlAvailability(true);
        $this->setFileGetContentsAvailability(true);

        $best_request_handler = RequestHandlerFactory::getBestAvailableRequestHandler();
        $this->assertTrue($best_request_handler instanceof CurlRequestHandler);
    }

    public function testCreatesFileGetContentsRequestHandlerWhenCurlUnavailable()
    {
        $this->setCurlAvailability(false);
        $this->setFileGetContentsAvailability(true);

        $best_request_handler = RequestHandlerFactory::getBestAvailableRequestHandler();
        $this->assertTrue($best_request_handler instanceof FileGetContentsRequestHandler);
    }

    public function testCreatesNoRequestHandlerWhenCurlAndFileGetContentsAreUnavailable()
    {
        $this->setCurlAvailability(false);
        $this->setFileGetContentsAvailability(false);

        $this->assertEquals(null, RequestHandlerFactory::getBestAvailableRequestHandler());
    }
}
