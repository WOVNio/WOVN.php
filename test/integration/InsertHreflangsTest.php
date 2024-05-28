<?php
namespace Wovnio\Wovnphp\Tests\Integration;

require_once(__DIR__ . '/../helpers/TestUtils.php');
use Wovnio\Test\Helpers\TestUtils;

use PHPUnit\Framework\TestCase;

class InsertHreflangsTest extends TestCase
{
    private $sourceDir;
    private $docRoot;

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

    public function testInsertHreflangsTrue()
    {
        $langs = array('en', 'ja', 'en-US', 'zh-Hant-HK');
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        $original_html = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="fr" href="http://localhost/fr/index.html">'.
        '</head>'.
        '<body>test</body>'.
        '</html>';
        TestUtils::writeFile("{$this->docRoot}/index.html", $original_html);
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'path',
            'supported_langs' => $langs,
            'insert_hreflangs' => true
        ));

        $content_without_html_swapper = '<html lang="en">'.
        '<head>'.
        '<link rel="alternate" hreflang="en" href="http://localhost/index.html">'.
        '<link rel="alternate" hreflang="ja" href="http://localhost/ja/index.html">'.
        '<link rel="alternate" hreflang="en-US" href="http://localhost/en-US/index.html">'.
        '<link rel="alternate" hreflang="zh-Hant-HK" href="http://localhost/zh-Hant-HK/index.html">'.
        '<script src="//j.wovn.io/1" '.
        'data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;langParamName=wovn" '.
        'data-wovnio-info="version=WOVN.php_VERSION" '.
        'async></script>'.
        '<link rel="alternate" hreflang="fr" href="http://localhost/fr/index.html">'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/en/index.html')->body);
    }

    public function testInsertHreflangsFalse()
    {
        $langs = array('en', 'ja', 'en-US', 'zh-Hant-HK');
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        $original_html = '<html>'.
        '<head>'.
        '<link rel="alternate" hreflang="fr" href="http://localhost/fr/index.html">'.
        '</head>'.
        '<body>test</body>'.
        '</html>';
        TestUtils::writeFile("{$this->docRoot}/index.html", $original_html);
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'path',
            'supported_langs' => $langs,
            'insert_hreflangs' => false
        ));

        $content_without_html_swapper = '<html lang="en">'.
        '<head>'.
        '<script src="//j.wovn.io/1" '.
        'data-wovnio="key=TOKEN&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=path&amp;langCodeAliases=[]&amp;langParamName=wovn" '.
        'data-wovnio-info="version=WOVN.php_VERSION" '.
        'async></script>'.
        '<link rel="alternate" hreflang="fr" href="http://localhost/fr/index.html">'.
        '</head>'.
        '<body>test</body>'.
        '</html>';

        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/index.html')->body);
        $this->assertEquals($content_without_html_swapper, TestUtils::fetchURL('http://localhost/en/index.html')->body);
    }
}
