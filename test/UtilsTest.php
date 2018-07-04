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

  public function testIsFilePathURI() {
    $this->assertEquals(false, Utils::isFilePathURI('https://google.com'));
    $this->assertEquals(false, Utils::isFilePathURI('https://google.com/mp3'));
    $this->assertEquals(true, Utils::isFilePathURI('/test.mp3'));
    $this->assertEquals(true, Utils::isFilePathURI('/lvl1/lvl2/file.pdf'));
  }

  public function testIsHtml() {
    $this->assertEquals(false, Utils::isHtml(array(),'this is not html, even tho it contains < and >'));

    $this->assertEquals(true, Utils::isHtml(array(),'<html><head></head><body><p>this is html</p></body></html>'));
    $this->assertEquals(true, Utils::isHtml(array(),'<p>this is html</p>'));

    $this->assertEquals(true, Utils::isHtml(array('Content-Type: text/html'),'<p>this is html</p>'));
    $this->assertEquals(true, Utils::isHtml(array('Content-Type: application/xhtml+xml'),'<p>this is xhtml</p>'));
    $this->assertEquals(false, Utils::isHtml(array('Content-Type: application/json'),'<p>this is json</p>'));
    $this->assertEquals(false, Utils::isHtml(array('Content-Type: application/pdf'),'<p>this is pdf</p>'));
  }

  public function testIsAmp() {
    $this->assertEquals(false, Utils::isAmp('<html><head></head><body><p>this is html</p></body></html>'));
    $this->assertEquals(false, Utils::isAmp('<htmlnop amp><head></head><body><p>this is html</p></body></html>'));

    $this->assertEquals(true, Utils::isAmp('<html amp><head></head><body><p>this is html</p></body></html>'));
    $this->assertEquals(true, Utils::isAmp('<html amp ><head></head><body><p>this is html</p></body></html>'));
    $this->assertEquals(true, Utils::isAmp('<html amp=""><head></head><body><p>this is html</p></body></html>'));
    $this->assertEquals(true, Utils::isAmp("<html\namp><head></head><body><p>this is html</p></body></html>"));
    $this->assertEquals(true, Utils::isAmp('<html lang=en amp><head></head><body><p>this is html</p></body></html>'));
    $this->assertEquals(true, Utils::isAmp('<html amp lang=en><head></head><body><p>this is html</p></body></html>'));

    $this->assertEquals(true, Utils::isAmp("<html ⚡><head></head><body><p>this is html</p></body></html>"));
    $this->assertEquals(true, Utils::isAmp("<html ⚡ ><head></head><body><p>this is html</p></body></html>"));
    $this->assertEquals(true, Utils::isAmp("<html ⚡=\"\"><head></head><body><p>this is html</p></body></html>"));
    $this->assertEquals(true, Utils::isAmp("<html\n⚡><head></head><body><p>this is html</p></body></html>"));
    $this->assertEquals(true, Utils::isAmp("<html lang=en ⚡><head></head><body><p>this is html</p></body></html>"));
    $this->assertEquals(true, Utils::isAmp("<html ⚡ lang=en><head></head><body><p>this is html</p></body></html>"));
  }

  public function testIsAmpWithCommentedHtmlTag() {
    $commented_amp = <<<XML
  <!-- <html amp> -->
  <html>
    <head></head>
    <body></body>
  </html>
XML;
    $commented_amp_symbol = <<<XML
  <!-- <html ⚡> -->
  <html>
    <head></head>
    <body></body>
  </html>
XML;
    $uncommented_amp = <<<XML
  <!-- <html> -->
  <html amp>
    <head></head>
    <body></body>
  </html>
XML;
    $uncommented_amp_symbol = <<<XML
  <!-- <html> -->
  <html ⚡>
    <head></head>
    <body></body>
  </html>
XML;
    $uncommented_amp_with_multiline_comment = <<<XML
  <!-- <html>
  -->
  <html amp>
    <head></head>
    <body></body>
  </html>
XML;

    $this->assertEquals(false, Utils::isAmp($commented_amp));
    $this->assertEquals(false, Utils::isAmp($commented_amp_symbol));

    $this->assertEquals(true, Utils::isAmp($uncommented_amp));
    $this->assertEquals(true, Utils::isAmp($uncommented_amp_symbol));
    $this->assertEquals(true, Utils::isAmp($uncommented_amp_with_multiline_comment));
  }
}
