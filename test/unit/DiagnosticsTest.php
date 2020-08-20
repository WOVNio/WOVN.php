<?php
namespace Wovnio\Wovnphp\Tests\Unit;

require_once('src/diagnostics.php');
use Wovnio\Test\Helpers\StoreAndHeadersFactory;
use \Wovnio\Wovnphp\Lang;

class DiagnosticsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPhpInfo()
    {
        $this->assertNotEmpty(getPhpInfo());
    }

    public function testGetHtaccess()
    {
        $this->assertNotEmpty(getHtaccess());
    }

    public function testGetDir()
    {
        $dirs = getDir();
        $this->assertNotEmpty($dirs);
        $lines = explode('<br>', $dir);
        foreach ($lines as $line) {
            $this->assertNotContains("WOVN.php", trim($dirs));
            $this->assertNotEquals(".", trim($dirs));
            $this->assertNotEquals("..", trim($dirs));
        }
    }

    public function testGetBaseUrl()
    {
        $base_url = getBaseUrl();
        $this->assertNotEmpty($base_url);
    }
}
