<?php
namespace Wovnio\Wovnphp\Tests\Integration;

require_once(__DIR__ . '/../helpers/TestUtils.php');
use Wovnio\Test\Helpers\TestUtils;

use PHPUnit\Framework\TestCase;

class UrlCustomDomainPatternTest extends TestCase
{
    private static $orgHostFile;

    public static function setUpBeforeClass()
    {
        self::$orgHostFile = file_get_contents('/etc/hosts');
        TestUtils::addHost('testsite.com');
        TestUtils::addHost('en-us.testsite.com');
        TestUtils::addHost('zh-hant-hk.com');
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
        TestUtils::cleanUpDirectory($this->docRoot);
    }

    public function testCustomDomainPatternRootDir()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        $customDomainLangs = array(
            'en' => array('url' => 'testsite.com'),
            'en-US' => array('url' => 'en-us.testsite.com'),
            'ja' => array('url' => 'testsite.com/ja'),
            'zh-CHS' => array('url' => 'testsite.com/zh/chs'),
            'zh-Hant-HK' => array('url' => 'zh-hant-hk.com/zh'),
        );
        TestUtils::setWovnJson("{$this->docRoot}/wovn.json", array(
            'url_pattern_name' => 'custom_domain',
            'default_lang' => 'en',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'custom_domain_langs' => $customDomainLangs
        ));
        TestUtils::setWovnConfig("{$this->docRoot}/.htaccess", "{$this->docRoot}/wovn.json");
        $customDomainLangsHtmlSwapperRep = array(
            'testsite.com' => 'en',
            'en-us.testsite.com' => 'en-US',
            'testsite.com/ja' => 'ja',
            'testsite.com/zh/chs' => 'zh-CHS',
            'zh-hant-hk.com/zh' => 'zh-Hant-HK'
        );
        $encodedCustomDomainLangsString = htmlentities(json_encode($customDomainLangsHtmlSwapperRep));
        $content_without_html_swapper = '<html lang="en">'.
        '<head>'.
        '<link rel="canonical" href="http://testsite.com/index.html">'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://testsite.com/ja/index.html">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/index.html">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.com/zh/index.html">'.
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
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://zh-hant-hk.com/zh/index.html')->body);
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
            'en' => array('url' => 'testsite.com'),
            'en-US' => array('url' => 'en-us.testsite.com'),
            'ja' => array('url' => 'testsite.com/ja'),
            'zh-CHS' => array('url' => 'testsite.com/zh/chs'),
            'zh-Hant-HK' => array('url' => 'zh-hant-hk.com/zh'),
        );
        TestUtils::setWovnJson("{$this->docRoot}/wovn.json", array(
            'url_pattern_name' => 'custom_domain',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'custom_domain_langs' => $customDomainLangs
        ));
        TestUtils::setWovnConfig("{$this->docRoot}/.htaccess", "{$this->docRoot}/wovn.json");
        $customDomainLangsHtmlSwapperRep = array(
            'testsite.com' => 'en',
            'en-us.testsite.com' => 'en-US',
            'testsite.com/ja' => 'ja',
            'testsite.com/zh/chs' => 'zh-CHS',
            'zh-hant-hk.com/zh' => 'zh-Hant-HK'
        );
        $encodedCustomDomainLangsString = htmlentities(json_encode($customDomainLangsHtmlSwapperRep));
        $content_without_html_swapper = '<html lang="en">'.
        '<head>'.
        '<link rel="canonical" href="http://testsite.com/index.php">'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/index.php">'.
        '<link rel="alternate" hreflang="ja" href="http://testsite.com/ja/index.php">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/index.php">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.com/zh/index.php">'.
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
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://zh-hant-hk.com/zh/index.php')->body);
    }

    public function testCustomDomainPatternSubDir()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        mkdir("{$this->docRoot}/sub");
        TestUtils::writeFile("{$this->docRoot}/sub/index.html", '<html><head></head><body>test</body></html>');
        $customDomainLangs = array(
            'en' => array('url' => 'testsite.com'),
            'en-US' => array('url' => 'en-us.testsite.com'),
            'ja' => array('url' => 'testsite.com/ja'),
            'zh-CHS' => array('url' => 'testsite.com/zh/chs'),
            'zh-Hant-HK' => array('url' => 'zh-hant-hk.com/zh'),
        );
        TestUtils::setWovnJson("{$this->docRoot}/wovn.json", array(
            'url_pattern_name' => 'custom_domain',
            'default_lang' => 'en',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'custom_domain_langs' => $customDomainLangs
        ));
        TestUtils::setWovnConfig("{$this->docRoot}/.htaccess", "{$this->docRoot}/wovn.json");
        $customDomainLangsHtmlSwapperRep = array(
            'testsite.com' => 'en',
            'en-us.testsite.com' => 'en-US',
            'testsite.com/ja' => 'ja',
            'testsite.com/zh/chs' => 'zh-CHS',
            'zh-hant-hk.com/zh' => 'zh-Hant-HK'
        );
        $encodedCustomDomainLangsString = htmlentities(json_encode($customDomainLangsHtmlSwapperRep));
        $content_without_html_swapper = '<html lang="en">'.
        '<head>'.
        '<link rel="canonical" href="http://testsite.com/sub/index.html">'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/sub/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://testsite.com/ja/sub/index.html">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/sub/index.html">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.com/zh/sub/index.html">'.
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
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://zh-hant-hk.com/zh/sub/index.html')->body);
    }

    public function testCustomDomainPatternWithDomainSource()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        mkdir("{$this->docRoot}/ja");
        TestUtils::writeFile("{$this->docRoot}/ja/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnConfig("{$this->docRoot}/.htaccess", "{$this->docRoot}/wovn.json");
        $customDomainLangs = array(
            'en' => array('url' => 'testsite.com/en', 'source' => 'testsite.com/ja'),
            'fr' => array('url' => 'testsite.com/fr'),
            'ja' => array('url' => 'testsite.com/ja')
        );
        TestUtils::setWovnJson("{$this->docRoot}/wovn.json", array(
            'url_pattern_name' => 'custom_domain',
            'default_lang' => 'en',
            'supported_langs' => array('en', 'ja', 'fr'),
            'custom_domain_langs' => $customDomainLangs
        ));

        $customDomainLangsHtmlSwapperRep = array(
            'testsite.com/en' => 'en',
            'testsite.com/fr' => 'fr',
            'testsite.com/ja' => 'ja',
        );

        $encodedCustomDomainLangsString = htmlentities(json_encode($customDomainLangsHtmlSwapperRep));
        $content_without_html_swapper = '<html lang="en">'.
            '<head>'.
            '<link rel="canonical" href="http://testsite.com/en/index.html">'.
            '<link rel="alternate" hreflang="en" href="http://testsite.com/en/index.html">'.
            '<link rel="alternate" hreflang="ja" href="http://testsite.com/ja/index.html">'.
            '<link rel="alternate" hreflang="fr" href="http://testsite.com/fr/index.html">'.
            '<script src="//j.wovn.io/1"'.
            ' data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=custom_domain&amp;langCodeAliases=[]&amp;langParamName=wovn&amp;customDomainLangs=' . $encodedCustomDomainLangsString . '"'.
            ' data-wovnio-info="version=WOVN.php_VERSION"'.
            ' async></script>'.
            '</head>'.
            '<body>test</body>'.
            '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://testsite.com/en/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://testsite.com/fr/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://testsite.com/ja/index.html')->body);
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
            'en' => array('url' => 'testsite.com'),
            'en-US' => array('url' => 'en-us.testsite.com'),
            'ja' => array('url' => 'testsite.com/ja'),
            'zh-CHS' => array('url' => 'testsite.com/zh/chs'),
            'zh-Hant-HK' => array('url' => 'zh-hant-hk.com/zh'),
        );
        TestUtils::setWovnJson("{$this->docRoot}/wovn.json", array(
            'url_pattern_name' => 'custom_domain',
            'default_lang' => 'en',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'custom_domain_langs' => $customDomainLangs
        ));
        TestUtils::setWovnConfig("{$this->docRoot}/.htaccess", "{$this->docRoot}/wovn.json");
        $customDomainLangsHtmlSwapperRep = array(
            'testsite.com' => 'en',
            'en-us.testsite.com' => 'en-US',
            'testsite.com/ja' => 'ja',
            'testsite.com/zh/chs' => 'zh-CHS',
            'zh-hant-hk.com/zh' => 'zh-Hant-HK'
        );
        $encodedCustomDomainLangsString = htmlentities(json_encode($customDomainLangsHtmlSwapperRep));
        $content_without_html_swapper = '<html lang="en">'.
        '<head>'.
        '<link rel="canonical" href="http://testsite.com/sub/index.php">'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/sub/index.php">'.
        '<link rel="alternate" hreflang="ja" href="http://testsite.com/ja/sub/index.php">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/sub/index.php">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.com/zh/sub/index.php">'.
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
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://zh-hant-hk.com/zh/sub/index.php')->body);
    }

    public function testCustomDomainPatternRootDirShouldServerEnvToDefaultLang()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        $content = '<?php echo json_encode($_SERVER); ?>';
        TestUtils::writeFile("{$this->docRoot}/index.php", $content);
        $customDomainLangs = array(
            'en' => array('url' => 'testsite.com'),
            'en-US' => array('url' => 'en-us.testsite.com'),
            'ja' => array('url' => 'testsite.com/ja'),
            'zh-CHS' => array('url' => 'testsite.com/zh/chs'),
            'zh-Hant-HK' => array('url' => 'zh-hant-hk.com/zh'),
        );
        TestUtils::setWovnJson("{$this->docRoot}/wovn.json", array(
            'url_pattern_name' => 'custom_domain',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'custom_domain_langs' => $customDomainLangs,
            'disable_api_request_for_default_lang' => 0
        ));
        TestUtils::setWovnConfig("{$this->docRoot}/.htaccess", "{$this->docRoot}/wovn.json");
        TestUtils::cleanUpDirectory("{$this->docRoot}/v0");
        $customDomainLangsHtmlSwapperRep = array(
            'testsite.com' => 'en',
            'en-us.testsite.com' => 'en-US',
            'testsite.com/ja' => 'ja',
            'testsite.com/zh/chs' => 'zh-CHS',
            'zh-hant-hk.com/zh' => 'zh-Hant-HK'
        );
        $encodedCustomDomainLangsString = htmlentities(json_encode($customDomainLangsHtmlSwapperRep));
        $content_without_html_swapper = '<html lang="en">'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://testsite.com/index.php">'.
        '<link rel="alternate" hreflang="ja" href="http://testsite.com/ja/index.php">'.
        '<link rel="alternate" hreflang="en-US" href="http://en-us.testsite.com/index.php">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://zh-hant-hk.com/zh/index.php">'.
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
            'http://zh-hant-hk.com/zh/index.php'
        );
        foreach ($testCase as $url) {
            $serverVals = json_decode(TestUtils::fetchURL($url)->body, true);
            $this->assertEquals('testsite.com', $serverVals['HTTP_HOST']);
            $this->assertEquals('testsite.com', $serverVals['SERVER_NAME']);
            $this->assertEquals('/index.php', $serverVals['REQUEST_URI']);
        };
    }
}
