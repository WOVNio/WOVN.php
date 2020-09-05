<?php
namespace Wovnio\Wovnphp\Tests\Integration;

require_once(__DIR__ . '/../helpers/TestUtils.php');
use Wovnio\Test\Helpers\TestUtils;

class UrlSubdomainPatternTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        TestUtils::addHost('testsite.com');
        TestUtils::addHost('en.testsite.com');
        TestUtils::addHost('ja.testsite.com');
        TestUtils::addHost('en-us.testsite.com');
        TestUtils::addHost('zh-hant-hk.testsite.com');
    }

    protected function setUp()
    {
        $this->sourceDir  = realpath(dirname(__FILE__) . '/../..');
        $this->docRoot    = '/var/www/html';

        TestUtils::cleanUpDirectory($this->docRoot);

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
        TestUtils::cleanUpDirectory($this->docRoot);
    }

    public function testSubdomainPatternRootDir()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
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

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://testsite.com/index.html')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://en.testsite.com/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://ja.testsite.com/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://en-us.testsite.com/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://zh-hant-hk.testsite.com/index.html')->body);
    }

    public function testSubdomainPatternRootDirWithIntercepter()
    {
        TestUtils::disableRewriteToWovnIndex("{$this->docRoot}/.htaccess");
        $content =
            "<?php require_once('{$this->docRoot}/WOVN.php/src/wovn_interceptor.php'); ?>".
            '<html><head></head><body>test</body></html>';
        TestUtils::writeFile("{$this->docRoot}/index.php", $content);
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
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

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://testsite.com/index.php')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://en.testsite.com/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://ja.testsite.com/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://en-us.testsite.com/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://zh-hant-hk.testsite.com/index.php')->body);
    }

    public function testSubdomainPatternSubDir()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        mkdir("{$this->docRoot}/sub");
        TestUtils::writeFile("{$this->docRoot}/sub/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
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

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://testsite.com/sub/index.html')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://en.testsite.com/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://ja.testsite.com/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://en-us.testsite.com/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://zh-hant-hk.testsite.com/sub/index.html')->body);
    }

    public function testSubdomainPatternSubDirWithIntercepter()
    {
        TestUtils::disableRewriteToWovnIndex("{$this->docRoot}/.htaccess");
        $content =
            "<?php require_once('{$this->docRoot}/WOVN.php/src/wovn_interceptor.php'); ?>".
            '<html><head></head><body>test</body></html>';
        mkdir("{$this->docRoot}/sub");
        TestUtils::writeFile("{$this->docRoot}/sub/index.php", $content);
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
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

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://testsite.com/sub/index.php')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://en.testsite.com/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://ja.testsite.com/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://en-us.testsite.com/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://zh-hant-hk.testsite.com/sub/index.php')->body);
    }
}
