<?php
namespace Wovnio\Wovnphp\Tests;

use \Wovnio\Wovnphp\RequestOptions;

class RequestOptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testNoOptions()
    {
        $query_string = '/?lang=ja';
        $debug_mode = true;
        $options = new RequestOptions($query_string, $debug_mode);

        $this->assertEquals(false, $options->getDisableMode());
        $this->assertEquals(false, $options->getCacheDisableMode());
        $this->assertEquals(false, $options->getDebugMode());
    }

    public function testAllOptions()
    {
        $query_string = '/?lang=ja&wovnDisable&wovnCacheDisable&wovnDebugMode';
        $debug_mode = true;
        $options = new RequestOptions($query_string, $debug_mode);

        $this->assertEquals(true, $options->getDisableMode());
        $this->assertEquals(true, $options->getCacheDisableMode());
        $this->assertEquals(true, $options->getDebugMode());
    }

    public function testNeedDebugModeSettingEnabledForCacheDisableAndDebugMode()
    {
        $query_string = '/?lang=ja&wovnDisable&wovnCacheDisable&wovnDebugMode';
        $debug_mode = false;
        $options = new RequestOptions($query_string, $debug_mode);

        $this->assertEquals(true, $options->getDisableMode());
        $this->assertEquals(false, $options->getCacheDisableMode());
        $this->assertEquals(false, $options->getDebugMode());
    }
}
