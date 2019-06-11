<?php
namespace Wovnio\Wovnphp\Tests\Unit;

require_once 'test/helpers/EnvFactory.php';

require_once 'src/wovnio/wovnphp/Utils.php';

use Wovnio\Test\Helpers\EnvFactory;

use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Utils;

class UtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testFunctionsExists()
    {
        $this->assertTrue(class_exists('Wovnio\Wovnphp\Utils'));
        $this->assertTrue(method_exists('Wovnio\Wovnphp\Utils', 'getStoreAndHeaders'));
        $this->assertFalse(method_exists('Wovnio\Wovnphp\Utils', 'dispatchRequest'));
    }

    public function testGetStoreAndHeaders()
    {
        $env = EnvFactory::fromFixture('default');
        list($store, $headers) = Utils::getStoreAndHeaders($env);
        $this->assertEquals('Wovnio\Wovnphp\Store', get_class($store));
        $this->assertEquals('Wovnio\Wovnphp\Headers', get_class($headers));
    }

    public function testIsFilePathURI()
    {
        $env = EnvFactory::fromFixture('default');
        list($store, $headers) = Utils::getStoreAndHeaders($env);
        $this->assertEquals(false, Utils::isFilePathURI('https://google.com', $store));
        $this->assertEquals(false, Utils::isFilePathURI('https://google.com/mp3', $store));
        $this->assertEquals(true, Utils::isFilePathURI('/test.mp3', $store));
        $this->assertEquals(true, Utils::isFilePathURI('/lvl1/lvl2/file.pdf', $store));
    }

    public function testIsFilePathURIWithPathsAndRegex()
    {
        $env = EnvFactory::fromFixture('default');
        list($store, $headers) = Utils::getStoreAndHeaders($env);
        $store->settings['ignore_paths'] = array('/coucou.jpg', 'assets/img/');
        $store->settings['ignore_regex'] = array("/img\/assets$/i");
        $this->assertEquals(false, Utils::isFilePathURI('https://google.com', $store));
        $this->assertEquals(true, Utils::isFilePathURI('https://google.com/coucou.zip', $store));
        $this->assertEquals(true, Utils::isFilePathURI('https://google.com/coucou.7zip', $store));
        $this->assertEquals(true, Utils::isFilePathURI('https://google.com/coucou.7z', $store));
        $this->assertEquals(true, Utils::isFilePathURI('https://google.com/coucou.gzip', $store));
        $this->assertEquals(true, Utils::isFilePathURI('https://google.com/coucou.rar', $store));
        $this->assertEquals(true, Utils::isFilePathURI('https://google.com/coucou.tar.gz', $store));
        $this->assertEquals(true, Utils::isFilePathURI('https://google.com/coucou.jpg', $store));
        $this->assertEquals(true, Utils::isFilePathURI('https://google.com/assets/img/boop', $store));
        $this->assertEquals(true, Utils::isFilePathURI('https://google.com/img/assets', $store));
        $this->assertEquals(false, Utils::isFilePathURI('https://google.com/img/assets/index.html', $store));
    }

    public function testIsHtml()
    {
        $this->assertEquals(false, Utils::isHtml(array(), 'this is not html, even tho it contains < and >'));

        $this->assertEquals(true, Utils::isHtml(array(), '<html><head></head><body><p>this is html</p></body></html>'));
        $this->assertEquals(true, Utils::isHtml(array(), '<p>this is html</p>'));

        $this->assertEquals(true, Utils::isHtml(array('Content-Type: text/html'), '<p>this is html</p>'));
        $this->assertEquals(true, Utils::isHtml(array('Content-Type: application/xhtml+xml'), '<p>this is xhtml</p>'));
        $this->assertEquals(false, Utils::isHtml(array('Content-Type: application/json'), '<p>this is json</p>'));
        $this->assertEquals(false, Utils::isHtml(array('Content-Type: application/pdf'), '<p>this is pdf</p>'));
    }

    public function testIsAmp()
    {
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

    public function testIsAmpWithCommentedHtmlTag()
    {
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
