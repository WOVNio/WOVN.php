<?php
namespace Wovnio\Wovnphp\Tests\Unit;

require_once 'test/helpers/EnvFactory.php';
require_once 'src/wovnio/wovnphp/Utils.php';
require_once 'test/helpers/StoreAndHeadersFactory.php';

use Wovnio\test\Helpers\StoreAndHeadersFactory;
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

    public function testGetStoreAndHeadersWithWovnConfigOfEnv()
    {
        $env = EnvFactory::fromFixture('default');
        $env['WOVN_CONFIG'] = dirname(__FILE__) . '/../fixtures/config/siteA.ini';
        list($store, $headers) = Utils::getStoreAndHeaders($env);
        $this->assertEquals('SiteA', $store->settings['project_token']);

        $env = EnvFactory::fromFixture('default');
        $env['WOVN_CONFIG'] = dirname(__FILE__) . '/../fixtures/config/siteB.ini';
        list($store, $headers) = Utils::getStoreAndHeaders($env);
        $this->assertEquals('SiteB', $store->settings['project_token']);

        $env = EnvFactory::fromFixture('default');
        list($store, $headers) = Utils::getStoreAndHeaders($env);
        $this->assertEquals('', $store->settings['project_token']);
    }

    public function testIsIgnoredPathUsingIgnorePathSetting()
    {
        $env = EnvFactory::fromFixture('default');
        list($store, $headers) = Utils::getStoreAndHeaders($env);
        $store->settings['ignore_paths'] = array('coucou.html', '/assets/img/', '/admin');

        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com', $store));

        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/coucou.html', $store));
        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/coucou.html/', $store));
        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/coucou.html/boop', $store));
        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/coucou.html?foo', $store));
        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/coucou.html#foo', $store));
        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com/page/coucou.html', $store));
        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com/coucou.htmlx', $store));
        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com/coucou', $store));

        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/assets/img', $store));
        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/assets/img/', $store));
        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/assets/img/boop', $store));
        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com/assets/img.png', $store));
        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com/other/assets/img', $store));
        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com/other/assets/img/', $store));

        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/admin', $store));
        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/admin/', $store));
        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/admin/user', $store));
        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com/admins', $store));
        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com/user/admin', $store));
    }

    public function testIsIgnoredPathUsingIgnoreRegexSetting()
    {
        $env = EnvFactory::fromFixture('default');
        list($store, $headers) = Utils::getStoreAndHeaders($env);
        $store->settings['ignore_regex'] = array("/img\/assets$/i", "/\/dog.png$/i");

        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com', $store));

        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/img/assets', $store));
        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/IMG/ASSETS', $store));
        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/global/img/assets', $store));
        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com/global/img/assets/', $store));
        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com/global/img/assets/cat.png', $store));

        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/global/img/assets/dog.png', $store));
        $this->assertEquals(true, Utils::isIgnoredPath('https://google.com/dog.png', $store));
        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com/dog.png/', $store));
        $this->assertEquals(false, Utils::isIgnoredPath('https://google.com/stray_dog.png', $store));
    }

    public function testIsHtml()
    {
        $this->assertEquals(false, Utils::isHtml('this is not html, even tho it contains < and >'));

        $this->assertEquals(true, Utils::isHtml('<html><head></head><body><p>this is html</p></body></html>'));
        $this->assertEquals(true, Utils::isHtml('<?php require_once(\'{$this->docRoot}/WOVN.php/src/wovn_interceptor.php\'); ?><html><head></head><body>test</body></html>'));
        $this->assertEquals(false, Utils::isHtml('this is json'));
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
