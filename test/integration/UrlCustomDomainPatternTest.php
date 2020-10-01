<?php
namespace Wovnio\Wovnphp\Tests\Integration;

require_once(__DIR__ . '/../helpers/TestUtils.php');
use Wovnio\Test\Helpers\TestUtils;

class UrlCustomDomainPatternTest extends \PHPUnit_Framework_TestCase
{
    private static $orgHostFile;

    public static function setUpBeforeClass()
    {
        self::$orgHostFile = file_get_contents('/etc/hosts');
        TestUtils::addHost('testsite.com');
        TestUtils::addHost('en-us.testsite.com');
        TestUtils::addHost('zh-hant-hk.testsite.com');
    }

    public static function tearDownAfterClass()
    {
        if (!empty(self::$orgHostFile)) {
            file_put_contents('/etc/hosts', self::$orgHostFile);
        }
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
        // TestUtils::cleanUpDirectory($this->docRoot);
    }

    public function testCustomDomainPatternRootDir()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        $customDomainLangs = array(
            'testsite.com' => 'en',
            'en-us.testsite.com' => 'en-US',
            'testsite.com/ja' => 'ja',
            'testsite.com/zh/chs' => 'zh-CHS',
            'zh-hant-hk.testsite.com/zh' => 'zh-Hant-HK'
        );
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'custom_domain',
            'default_lang' => en,
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'custom_domain_langs' => $customDomainLangs
        ));

        $encodedCustomDomainLangsString = htmlentities(json_encode($customDomainLangs));
        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://testsite.com/ja/index.html">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/index.html">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.testsite.com/zh/index.html">'.
        '<script src="//j.wovn.io/1"'.
        ' data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=custom_domain&amp;langCodeAliases=[]&amp;langParamName=wovn&amp;customDomainLangs=' . $encodedCustomDomainLangsString . '"'.
        ' data-wovnio-info="version=WOVN.php_VERSION"'.
        ' async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://testsite.com/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://en-us.testsite.com/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://testsite.com/ja/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://testsite.com/zh/chs/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://zh-hant-hk.testsite.com/zh/index.html')->body);
    }

    public function testCustomDomainPatternRootDirWithIntercepter()
    {
        $langPaths = array('ja', 'zh\/chs', 'zh');
        TestUtils::disableRewriteToWovnIndex("{$this->docRoot}/.htaccess");
        TestUtils::enableRewritePathPattern("{$this->docRoot}/.htaccess", $langPaths);
        $content =
            "<?php require_once('{$this->docRoot}/WOVN.php/src/wovn_interceptor.php'); ?>".
            '<html><head></head><body>test</body></html>';
        TestUtils::writeFile("{$this->docRoot}/index.php", $content);
        $customDomainLangs = array(
            'testsite.com' => 'en',
            'en-us.testsite.com' => 'en-US',
            'testsite.com/ja' => 'ja',
            'testsite.com/zh/chs' => 'zh-CHS',
            'zh-hant-hk.testsite.com/zh' => 'zh-Hant-HK'
        );
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'custom_domain',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'custom_domain_langs' => $customDomainLangs
        ));

        $encodedCustomDomainLangsString = htmlentities(json_encode($customDomainLangs));
        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/index.php">'.
        '<link rel="alternate" hreflang="ja" href="http://testsite.com/ja/index.php">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/index.php">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.testsite.com/zh/index.php">'.
        '<script src="//j.wovn.io/1"'.
        ' data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=custom_domain&amp;langCodeAliases=[]&amp;langParamName=wovn&amp;customDomainLangs=' . $encodedCustomDomainLangsString . '"'.
        ' data-wovnio-info="version=WOVN.php_VERSION"'.
        ' async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://testsite.com/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://en-us.testsite.com/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://testsite.com/ja/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://testsite.com/zh/chs/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://zh-hant-hk.testsite.com/zh/index.php')->body);
    }

    public function testCustomDomainPatternSubDir()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        mkdir("{$this->docRoot}/sub");
        TestUtils::writeFile("{$this->docRoot}/sub/index.html", '<html><head></head><body>test</body></html>');
        $customDomainLangs = array(
            'testsite.com' => 'en',
            'en-us.testsite.com' => 'en-US',
            'testsite.com/ja' => 'ja',
            'testsite.com/zh/chs' => 'zh-CHS',
            'zh-hant-hk.testsite.com/zh' => 'zh-Hant-HK'
        );
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'custom_domain',
            'default_lang' => en,
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'custom_domain_langs' => $customDomainLangs
        ));

        $encodedCustomDomainLangsString = htmlentities(json_encode($customDomainLangs));
        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/sub/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://testsite.com/ja/sub/index.html">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/sub/index.html">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.testsite.com/zh/sub/index.html">'.
        '<script src="//j.wovn.io/1"'.
        ' data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=custom_domain&amp;langCodeAliases=[]&amp;langParamName=wovn&amp;customDomainLangs=' . $encodedCustomDomainLangsString . '"'.
        ' data-wovnio-info="version=WOVN.php_VERSION"'.
        ' async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://testsite.com/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://en-us.testsite.com/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://testsite.com/ja/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://testsite.com/zh/chs/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://zh-hant-hk.testsite.com/zh/sub/index.html')->body);
    }

    public function testCustomDomainPatternSubDirWithIntercepter()
    {
        $langs = array('ja', 'zh\/chs', 'zh');
        TestUtils::disableRewriteToWovnIndex("{$this->docRoot}/.htaccess");
        TestUtils::enableRewritePathPattern("{$this->docRoot}/.htaccess", $langs);

        $content =
            "<?php require_once('{$this->docRoot}/WOVN.php/src/wovn_interceptor.php'); ?>\n".
            '<html><head></head><body>test</body></html>';
        mkdir("{$this->docRoot}/sub");
        TestUtils::writeFile("{$this->docRoot}/sub/index.php", $content);
        $customDomainLangs = array(
            'testsite.com' => 'en',
            'en-us.testsite.com' => 'en-US',
            'testsite.com/ja' => 'ja',
            'testsite.com/zh/chs' => 'zh-CHS',
            'zh-hant-hk.testsite.com/zh' => 'zh-Hant-HK'
        );
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'custom_domain',
            'default_lang' => en,
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'custom_domain_langs' => $customDomainLangs
        ));

        $encodedCustomDomainLangsString = htmlentities(json_encode($customDomainLangs));
        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/sub/index.php">'.
        '<link rel="alternate" hreflang="ja" href="http://testsite.com/ja/sub/index.php">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/sub/index.php">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.testsite.com/zh/sub/index.php">'.
        '<script src="//j.wovn.io/1"'.
        ' data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=custom_domain&amp;langCodeAliases=[]&amp;langParamName=wovn&amp;customDomainLangs=' . $encodedCustomDomainLangsString . '"'.
        ' data-wovnio-info="version=WOVN.php_VERSION"'.
        ' async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://testsite.com/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://en-us.testsite.com/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://testsite.com/ja/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://testsite.com/zh/chs/sub/index.php')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://zh-hant-hk.testsite.com/zh/sub/index.php')->body);
    }

    public function testCustomDomainPatternRootDirShouldServerEnvToDefaultLang()
    {
        $langPaths = array('ja', 'zh\/chs', 'zh');
        TestUtils::disableRewriteToWovnIndex("{$this->docRoot}/.htaccess");
        TestUtils::enableRewritePathPattern("{$this->docRoot}/.htaccess", $langPaths);
        $content =
            "<?php require_once('{$this->docRoot}/WOVN.php/src/wovn_interceptor.php'); ?>".
            '<?php echo json_encode($_SERVER); ?>';
        TestUtils::writeFile("{$this->docRoot}/index.php", $content);
        $customDomainLangs = array(
            'testsite.com' => 'en',
            'en-us.testsite.com' => 'en-US',
            'testsite.com/ja' => 'ja',
            'testsite.com/zh/chs' => 'zh-CHS',
            'zh-hant-hk.testsite.com/zh' => 'zh-Hant-HK'
        );
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'custom_domain',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'custom_domain_langs' => $customDomainLangs,
            'disable_api_request_for_default_lang' => false
        ));
        TestUtils::cleanUpDirectory("{$this->docRoot}/v0");

        $encodedCustomDomainLangsString = htmlentities(json_encode($customDomainLangs));
        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/index.php">'.
        '<link rel="alternate" hreflang="ja" href="http://testsite.com/ja/index.php">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/index.php">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.testsite.com/zh/index.php">'.
        '<script src="//j.wovn.io/1"'.
        ' data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=custom_domain&amp;langCodeAliases=[]&amp;langParamName=wovn&amp;customDomainLangs=' . $encodedCustomDomainLangsString . '"'.
        ' data-wovnio-info="version=WOVN.php_VERSION"'.
        ' async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $testCase = array(
            'http://testsite.com/index.php',
            'http://testsite.com/ja/index.php',
            'http://en-us.testsite.com/index.php',
            'http://testsite.com/zh/chs/index.php',
            'http://zh-hant-hk.testsite.com/zh/index.php'
        );
        foreach ($testCase as $url) {
            $serverVals = json_decode(TestUtils::fetchURL($url)->body, true);
            $this->assertEquals('testsite.com', $serverVals['HTTP_HOST']);
            $this->assertEquals('testsite.com', $serverVals['SERVER_NAME']);
            $this->assertEquals('/index.php', $serverVals['REQUEST_URI']);
        };
    }
}
