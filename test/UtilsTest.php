<?php
require_once 'src/wovnio/wovnphp/Utils.php';

use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Utils;

class UtilsTest extends PHPUnit_Framework_TestCase {
  private function getEnv($num="") {
    $env = array();
    $file = parse_ini_file(dirname(__FILE__) . '/mock_env' . $num . '.ini');
    $env = $file['env'];
    return $env;
  }

  public function testFunctionsExists() {
    $this->assertTrue(class_exists('Wovnio\Wovnphp\Utils'));
    $this->assertTrue(method_exists('Wovnio\Wovnphp\Utils', 'getStoreAndHeaders'));
    $this->assertFalse(method_exists('Wovnio\Wovnphp\Utils', 'dispatchRequest'));
  }

  public function testGetStoreAndHeaders() {
    $env = $this->getEnv('_path');
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $this->assertEquals('Wovnio\Wovnphp\Store', get_class($store));
    $this->assertEquals('Wovnio\Wovnphp\Headers', get_class($headers));
  }

  public function testisFilePathURI() {
    $this->assertEquals(false, Utils::isFilePathURI('https://google.com'));
    $this->assertEquals(false, Utils::isFilePathURI('https://google.com/mp3'));
    $this->assertEquals(true, Utils::isFilePathURI('/test.mp3'));
    $this->assertEquals(true, Utils::isFilePathURI('/lvl1/lvl2/file.pdf'));
  }
}
