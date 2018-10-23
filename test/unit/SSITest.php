<?php
namespace Wovnio\Wovnphp\Tests\Unit;

require_once 'src/wovnio/wovnphp/SSI.php';
use Wovnio\Wovnphp\SSI;

class SSITest extends \PHPUnit_Framework_TestCase
{
  public function testSSI()
  {
    $original = getcwd();
    $dir = __DIR__ . '/ssi_test';
    $exception = null;

    chdir($dir);
    try {
        $result = SSI::readFile('a.html', $dir);
        // This output is same as Apache 2.4.18
        $this->assertEquals("a.html b.html\n dir1-index.html\n c.html d.html\n e.html z.html\n\n z.html\n\n\n", $result);

        $result = SSI::readFile('loop.html', $dir);
        $this->assertEquals("loop.html loop.html loop.html loop.html loop.html loop.html loop.html loop.html loop.html loop.html <!-- File does not include by limitation: loop.html-->\n\n\n\n\n\n\n\n\n\n", $result);
    } catch (Exception $e) {
        $exception = $e;
    }
    chdir($original);

    if ($exception) {
        throw $exception;
    }
  }
}
