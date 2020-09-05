<?php
namespace Wovnio\Wovnphp\Tests\Integration;

require_once(__DIR__ . '/../helpers/Utils.php');
use Wovnio\Test\Helpers\Utils;

class UrlSubdomainPatternTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Utils::addHost('testsite.com');
        Utils::addHost('en.testsite.com');
        Utils::addHost('ja.testsite.com');
        Utils::addHost('en-us.testsite.com');
        Utils::addHost('zh-hant-hk.testsite.com');
    }

    protected function setUp()
    {
        $this->sourceDir  = realpath(dirname(__FILE__) . '/../..');
        $this->docRoot    = '/var/www/html';

        Utils::cleanUpDirectory($this->docRoot);

        // Copy WOVN.php
        mkdir("{$this->docRoot}/WOVN.php");
        exec("cp -rf {$this->sourceDir}/src {$this->docRoot}/WOVN.php/src");
        copy("{$this->sourceDir}/htaccess_sample", "{$this->docRoot}/.htaccess");

        // Set html-swapper mock
        mkdir("{$this->docRoot}/v0");
        copy("{$this->sourceDir}/test/fixtures/integration/v0/translation", "{$this->docRoot}/v0/translation");
    }

    protected function tearDown()
    {
        Utils::cleanUpDirectory($this->docRoot);
    }

    public function testSubdomainPatternRootDir()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        Utils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        Utils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'subdomain',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK')
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://ja.testsite.com/index.html">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/index.html">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.testsite.com/index.html">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=Tek3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=subdomain&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, Utils::fetchURL('http://testsite.com/index.html')->body);
        $this->assertEquals($content_without_html_swapper, Utils::fetchURL('http://en.testsite.com/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', Utils::fetchURL('http://ja.testsite.com/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', Utils::fetchURL('http://en-us.testsite.com/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', Utils::fetchURL('http://zh-hant-hk.testsite.com/index.html')->body);
    }

    public function testSubdomainPatternRootDirWithIntercepter()
    {
        Utils::disableRewriteToWovnIndex("{$this->docRoot}/.htaccess");
        $content =
            "<?php require_once('{$this->docRoot}/WOVN.php/src/wovn_interceptor.php'); ?>".
            '<html><head></head><body>test</body></html>';
        Utils::writeFile("{$this->docRoot}/index.php", $content);
        Utils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'subdomain',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK')
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/index.php">'.
        '<link rel="alternate" hreflang="ja" href="http://ja.testsite.com/index.php">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/index.php">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.testsite.com/index.php">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=Tek3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=subdomain&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, Utils::fetchURL('http://testsite.com/index.php')->body);
        $this->assertEquals($content_without_html_swapper, Utils::fetchURL('http://en.testsite.com/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', Utils::fetchURL('http://ja.testsite.com/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', Utils::fetchURL('http://en-us.testsite.com/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', Utils::fetchURL('http://zh-hant-hk.testsite.com/index.php')->body);
    }

    public function testSubdomainPatternSubDir()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        mkdir("{$this->docRoot}/sub");
        Utils::writeFile("{$this->docRoot}/sub/index.html", '<html><head></head><body>test</body></html>');
        Utils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'subdomain',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK')
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/sub/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://ja.testsite.com/sub/index.html">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/sub/index.html">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.testsite.com/sub/index.html">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=Tek3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=subdomain&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, Utils::fetchURL('http://testsite.com/sub/index.html')->body);
        $this->assertEquals($content_without_html_swapper, Utils::fetchURL('http://en.testsite.com/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', Utils::fetchURL('http://ja.testsite.com/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', Utils::fetchURL('http://en-us.testsite.com/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', Utils::fetchURL('http://zh-hant-hk.testsite.com/sub/index.html')->body);
    }

    public function testSubdomainPatternSubDirWithIntercepter()
    {
        Utils::disableRewriteToWovnIndex("{$this->docRoot}/.htaccess");
        $content =
            "<?php require_once('{$this->docRoot}/WOVN.php/src/wovn_interceptor.php'); ?>".
            '<html><head></head><body>test</body></html>';
        mkdir("{$this->docRoot}/sub");
        Utils::writeFile("{$this->docRoot}/sub/index.php", $content);
        Utils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'subdomain',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK')
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/sub/index.php">'.
        '<link rel="alternate" hreflang="ja" href="http://ja.testsite.com/sub/index.php">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/sub/index.php">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.testsite.com/sub/index.php">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=Tek3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=subdomain&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, Utils::fetchURL('http://testsite.com/sub/index.php')->body);
        $this->assertEquals($content_without_html_swapper, Utils::fetchURL('http://en.testsite.com/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', Utils::fetchURL('http://ja.testsite.com/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', Utils::fetchURL('http://en-us.testsite.com/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', Utils::fetchURL('http://zh-hant-hk.testsite.com/sub/index.php')->body);
    }
}
