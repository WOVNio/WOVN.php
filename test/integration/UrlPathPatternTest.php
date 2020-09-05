<?php
namespace Wovnio\Wovnphp\Tests\Integration;

require_once(__DIR__ . '/../helpers/TestUtils.php');
use Wovnio\Test\Helpers\TestUtils;

class UrlPathPatternTest extends \PHPUnit_Framework_TestCase
{
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

    public function testPathPatternNotFoundPage()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/404.html", '<html><head></head><body>Page Not Found</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'path',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
        ));

        $not_found_page = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/no.html">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/ja/no.html">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/en-US/no.html">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/zh-Hant-HK/no.html">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=Tek3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>Page Not Found</body>'.
        '</html>';
        $this->assertEquals($not_found_page, TestUtils::fetchURL('http://localhost/no.html')->body);
    }

    public function testPathPatternRootDir()
    {
        $langs = array('en', 'ja', 'en-US', 'zh-Hant-HK');
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::enableRewritePathPattern("{$this->docRoot}/.htaccess", $langs);
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'path',
            'supported_langs' => $langs
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/ja/index.html">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/en-US/index.html">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/zh-Hant-HK/index.html">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=Tek3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/en/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/ja/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/en-US/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/zh-Hant-HK/index.html')->body);
    }

    public function testPathPatternRootDirWithIntercepter()
    {
        $langs = array('en', 'ja', 'en-US', 'zh-Hant-HK');
        TestUtils::disableRewriteToWovnIndex("{$this->docRoot}/.htaccess");
        TestUtils::enableRewritePathPattern("{$this->docRoot}/.htaccess", $langs);

        $content =
            "<?php require_once('{$this->docRoot}/WOVN.php/src/wovn_interceptor.php'); ?>\n".
            '<html><head></head><body>test</body></html>';
        TestUtils::writeFile("{$this->docRoot}/index.php", $content);
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'path',
            'supported_langs' => $langs
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/index.php">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/ja/index.php">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/en-US/index.php">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/zh-Hant-HK/index.php">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=Tek3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.php')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/en/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/ja/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/en-US/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/zh-Hant-HK/index.php')->body);
    }

    public function testPathPatternSubDir()
    {
        $langs = array('en', 'ja', 'en-US', 'zh-Hant-HK');
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::enableRewritePathPattern("{$this->docRoot}/.htaccess", $langs);
        mkdir("{$this->docRoot}/sub");
        TestUtils::writeFile("{$this->docRoot}/sub/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'path',
            'supported_langs' => $langs
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/sub/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/ja/sub/index.html">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/en-US/sub/index.html">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/zh-Hant-HK/sub/index.html">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=Tek3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/sub/index.html')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/en/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/ja/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/en-US/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/zh-Hant-HK/sub/index.html')->body);
    }

    public function testPathPatternSubDirWithIntercepter()
    {
        $langs = array('en', 'ja', 'en-US', 'zh-Hant-HK');
        TestUtils::disableRewriteToWovnIndex("{$this->docRoot}/.htaccess");
        TestUtils::enableRewritePathPattern("{$this->docRoot}/.htaccess", $langs);
        $content =
            "<?php require_once('{$this->docRoot}/WOVN.php/src/wovn_interceptor.php'); ?>\n".
            '<html><head></head><body>test</body></html>';
        mkdir("{$this->docRoot}/sub");
        TestUtils::writeFile("{$this->docRoot}/sub/index.php", $content);
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'path',
            'supported_langs' => $langs
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/sub/index.php">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/ja/sub/index.php">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/en-US/sub/index.php">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/zh-Hant-HK/sub/index.php">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=Tek3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/sub/index.php')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/en/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/ja/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/en-US/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/zh-Hant-HK/sub/index.php')->body);
    }
}
