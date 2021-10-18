<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'src/wovnio/utils/request_handlers/FileGetContentsRequestHandler.php';
require_once 'test/helpers/FileGetContentsMock.php';

use Wovnio\Utils\RequestHandlers\FileGetContentsRequestHandler;

use PHPUnit\Framework\TestCase;

class FileGetContentsRequestHandlerTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        restoreFileGetContents();
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
}
