<?php
namespace Wovnio\Wovnphp\Tests\wovn_helper;

require_once 'src/wovn_helper.php';

use PHPUnit\Framework\TestCase;

class WovnHelperTest extends TestCase
{
    public function testWovnHelperDefaultIndexFiles()
    {
        $this->assertEquals(array(
            "index.html",
            "index.shtml",
            "index.htm",
            "index.php",
            "index.php3",
            "index.phtml",
            "app.php"
        ), wovn_helper_default_index_files());
    }
}
