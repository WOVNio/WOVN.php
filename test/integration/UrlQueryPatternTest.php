<?php
namespace Wovnio\Wovnphp\Tests\Integration;

require_once(__DIR__ . '/../helpers/TestUtils.php');
use Wovnio\Test\Helpers\TestUtils;

class UrlQueryPatternTest extends \PHPUnit_Framework_TestCase
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

    private function disableRewriteToWovnIndex()
    {
        // Remove rewrite rule to wovn_index.php
        copy("{$this->sourceDir}/htaccess_sample", "{$this->docRoot}/.htaccess");
        $htaccess = file_get_contents("{$this->docRoot}/.htaccess");
        file_put_contents("{$this->docRoot}/.htaccess", str_replace('RewriteRule .? wovn_index.php [L]', '', $htaccess));
    }

    public function testQueryPatternRootDir()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/index.html?wovn=ja">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/index.html?wovn=en-US">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/index.html?wovn=zh-Hant-HK">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html?wovn=en')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?wovn=ja')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?wovn=en-US')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?wovn=zh-Hant-HK')->body);
    }

    public function testQueryPatternRootDirWithIntercepter()
    {
        TestUtils::disableRewriteToWovnIndex("{$this->docRoot}/.htaccess");

        // Set content with wovn_interceptor.php
        $content =
            "<?php require_once('{$this->docRoot}/WOVN.php/src/wovn_interceptor.php'); ?>".
            '<html><head></head><body>test</body></html>';
        TestUtils::writeFile("{$this->docRoot}/index.php", $content);
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/index.php">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/index.php?wovn=ja">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/index.php?wovn=en-US">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/index.php?wovn=zh-Hant-HK">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.php')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.php?wovn=en')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.php?wovn=ja')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.php?wovn=en-US')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.php?wovn=zh-Hant-HK')->body);
    }

    public function testQueryPatternSubDir()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        mkdir("{$this->docRoot}/sub");
        TestUtils::writeFile("{$this->docRoot}/sub/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/sub/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/sub/index.html?wovn=ja">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/sub/index.html?wovn=en-US">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/sub/index.html?wovn=zh-Hant-HK">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/sub/index.html')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/sub/index.html?wovn=en')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/sub/index.html?wovn=ja')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/sub/index.html?wovn=en-US')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/sub/index.html?wovn=zh-Hant-HK')->body);
    }

    public function testQueryPatternSubDirWithIntercepter()
    {
        TestUtils::disableRewriteToWovnIndex("{$this->docRoot}/.htaccess");
        $content =
            "<?php require_once('{$this->docRoot}/WOVN.php/src/wovn_interceptor.php'); ?>".
            '<html><head></head><body>test</body></html>';
        mkdir("{$this->docRoot}/sub");
        TestUtils::writeFile("{$this->docRoot}/sub/index.php", $content);
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/sub/index.php">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/sub/index.php?wovn=ja">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/sub/index.php?wovn=en-US">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/sub/index.php?wovn=zh-Hant-HK">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/sub/index.php')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/sub/index.php?wovn=en')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/sub/index.php?wovn=ja')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/sub/index.php?wovn=en-US')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/sub/index.php?wovn=zh-Hant-HK')->body);
    }

    public function testQueryPatternNotFoundPage()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/404.html", '<html><head></head><body>Page Not Found</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
        ));

        $not_found_page = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/no.html">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/no.html?wovn=ja">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/no.html?wovn=en-US">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/no.html?wovn=zh-Hant-HK">'.
        '<script src="//j.wovn.io/1" data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script>'.
        '</head>'.
        '<body>Page Not Found</body>'.
        '</html>';
        $this->assertEquals($not_found_page, TestUtils::fetchURL('http://localhost/no.html')->body);
    }

    public function testQueryPatternWhenHtmlSwapperDoesnotReturnResponse()
    {
        $langs = array('en', 'ja');
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => $langs,
            'api_url' => 'http://localhost/not_exist_html_swapper_url/'
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/index.html?wovn=ja">'.
        '<script src="//j.wovn.io/1" '.
        'data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=ja&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" '.
        'data-wovnio-info="version=WOVN.php_VERSION" '.
        'data-wovnio-type="fallback_snippet" '.
        'async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html?wovn=ja')->body);
    }

    public function testQueryPatternWithCustomLangAliases()
    {
        $langs = array(
            'en' => 'custom_en',
            'ja' => 'custom_ja',
            'en-US' => 'custom_en_US',
            'zh-Hant-HK' => 'custom_zh_Hant_HK'
        );
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => array_keys($langs),
            'custom_lang_aliases' => $langs
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/index.html?wovn=custom_en">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/index.html?wovn=custom_ja">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/index.html?wovn=custom_en_US">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/index.html?wovn=custom_zh_Hant_HK">'.
        '<script src="//j.wovn.io/1" '.
        'data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases={&quot;en&quot;:&quot;custom_en&quot;,&quot;ja&quot;:&quot;custom_ja&quot;,&quot;en-US&quot;:&quot;custom_en_US&quot;,&quot;zh-Hant-HK&quot;:&quot;custom_zh_Hant_HK&quot;}&amp;langParamName=wovn" '.
        'data-wovnio-info="version=WOVN.php_VERSION" '.
        'async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html?wovn=custom_en')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?wovn=custom_ja')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?wovn=custom_en_US')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?wovn=custom_zh_Hant_HK')->body);
    }

    public function testQueryPatternWithLangParamName()
    {
        $langs = array('en', 'ja', 'en-US', 'zh-Hant-HK');
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => $langs,
            'lang_param_name' => 'lang_param'
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/index.html?lang_param=ja">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/index.html?lang_param=en-US">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/index.html?lang_param=zh-Hant-HK">'.
        '<script src="//j.wovn.io/1" '.
        'data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=lang_param" '.
        'data-wovnio-info="version=WOVN.php_VERSION" '.
        'async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html?lang_param=en')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?lang_param=ja')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?lang_param=en-US')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?lang_param=zh-Hant-HK')->body);
    }

    public function testQueryPatternWithDisableApiRequestForDefaultLangFalse()
    {
        $langs = array('en', 'ja', 'en-US', 'zh-Hant-HK');
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => $langs,
            'disable_api_request_for_default_lang' => false
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/index.html?lang_param=ja">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/index.html?lang_param=en-US">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/index.html?lang_param=zh-Hant-HK">'.
        '<script src="//j.wovn.io/1" '.
        'data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=lang_param" '.
        'data-wovnio-info="version=WOVN.php_VERSION" '.
        'async></script>'.
        '</head>'.
        '<body>test</body>'.
        '</html>';
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?lang_param=en')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?lang_param=ja')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?lang_param=en-US')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?lang_param=zh-Hant-HK')->body);
    }

    public function testQueryPatternWithIgnorePaths()
    {
        $langs = array('en', 'ja');
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>root</body></html>');
        mkdir("{$this->docRoot}/sub");
        TestUtils::writeFile("{$this->docRoot}/sub/index.html", '<html><head></head><body>sub</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => $langs,
            'ignore_paths' => array('/sub')
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/index.html?wovn=ja">'.
        '<script src="//j.wovn.io/1" '.
        'data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" '.
        'data-wovnio-info="version=WOVN.php_VERSION" '.
        'async></script>'.
        '</head>'.
        '<body>root</body>'.
        '</html>';
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html')->body);
        $this->assertEquals('<html><head></head><body>sub</body></html>', TestUtils::fetchURL('http://localhost/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>sub</body></html>', TestUtils::fetchURL('http://localhost/sub/index.html?wovn=ja')->body);
    }

    public function testQueryPatternWithIgnoreRegex()
    {
        $langs = array('en', 'ja');
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>root</body></html>');
        mkdir("{$this->docRoot}/sub");
        TestUtils::writeFile("{$this->docRoot}/sub/index.html", '<html><head></head><body>sub</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => $langs,
            'ignore_regex' => array('/sub\/.*/')
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/index.html?wovn=ja">'.
        '<script src="//j.wovn.io/1" '.
        'data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" '.
        'data-wovnio-info="version=WOVN.php_VERSION" '.
        'async></script>'.
        '</head>'.
        '<body>root</body>'.
        '</html>';
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html')->body);
        $this->assertEquals('<html><head></head><body>sub</body></html>', TestUtils::fetchURL('http://localhost/sub/index.html')->body);
        $this->assertEquals('<html><head></head><body>sub</body></html>', TestUtils::fetchURL('http://localhost/sub/index.html?wovn=ja')->body);
    }

    public function testQueryPatternWithNoIndexLangs()
    {
        $langs = array('en', 'ja');
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>root</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => $langs,
            'no_index_langs' => array('en')
        ));

        $content_without_html_swapper = '<html>'.
        '<head>'.
        '<meta name="robots" content="noindex">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/index.html?wovn=ja">'.
        '<script src="//j.wovn.io/1" '.
        'data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" '.
        'data-wovnio-info="version=WOVN.php_VERSION" '.
        'async></script>'.
        '</head>'.
        '<body>root</body>'.
        '</html>';
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html')->body);
        $this->assertEquals('<html><head></head><body>html-swapper-mock</body></html>', TestUtils::fetchURL('http://localhost/index.html?wovn=ja')->body);
    }
}
