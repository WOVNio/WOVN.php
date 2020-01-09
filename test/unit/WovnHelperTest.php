<?php
namespace Wovnio\Wovnphp\Tests\wovn_helper;

require_once 'src/wovn_helper.php';

class WovnHelperTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
    */
    public function testWovnHelperDefaultIndexFilesWithOption()
    {
        define('WOVNPHP_DEFAULT_INDEX_FILE', 'test1.php');
        $this->assertEquals(array('test1.php'), wovn_helper_default_index_files());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
    */
    public function testWovnHelperDefaultIndexFilesWithArrayOption()
    {
        define('WOVNPHP_DEFAULT_INDEX_FILE', array('test1.php', 'test2.html'));
        $this->assertEquals(array('test1.php', 'test2.html'), wovn_helper_default_index_files());
    }
}
