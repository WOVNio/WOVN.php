<?php


namespace Wovnio\Wovnphp\Core\Tests\Unit;

require_once 'src/wovnio/wovnphp/core/exceptions/WovnException.php';
require_once 'src/wovnio/wovnphp/core/exceptions/WovnLangException.php';
require_once 'src/wovnio/wovnphp/core/WovnOption.php';
require_once 'src/wovnio/wovnphp/core/WovnLangDirectory.php';
require_once 'src/wovnio/wovnphp/core/WovnLang.php';
require_once 'src/wovnio/wovnphp/core/url_handlers/WovnURLHandler.php';
require_once 'src/wovnio/wovnphp/core/url_handlers/WovnQueryURLHandler.php';
require_once 'src/wovnio/wovnphp/core/url_handlers/WovnPathURLHandler.php';
require_once 'src/wovnio/wovnphp/core/url_handlers/WovnSubdomainURLHandler.php';
require_once 'src/wovnio/wovnphp/core/WovnURL.php';

use Wovnio\Wovnphp\Core\WovnLangDirectory;
use Wovnio\Wovnphp\Core\WovnOption;
use Wovnio\Wovnphp\Core\WovnURL;

class WovnURLTest extends \PHPUnit_Framework_TestCase
{
    private function getLangDirectory()
    {
        $target = array('ja', 'fr');
        $default = 'en';
        $alias = array('ja' => 'japanese', 'fr' => 'french');
        return new WovnLangDirectory($target, $default, $alias);
    }

    private function getOptions($optionOverride = array())
    {
        $optionConfig = parse_ini_file(realpath(__DIR__ . '/../fixture/config/basic.ini'));
        $optionConfig = array_merge($optionConfig, $optionOverride);
        return new WovnOption($optionConfig);
    }

    public function testConstructorWithQueryPatternDefaultLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions();
        $originalUrl = 'https://www.example.com/news/blog/post.html?page=3';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testWithQueryPatternInvalidLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions();
        $originalUrl = 'https://www.example.com/news/blog/post.html?wovn=mm';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testWithQueryPatternNonDefaultLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions();
        $originalUrl = 'https://www.example.com/news/blog/post.html?wovn=fr';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('fr', $url->lang()->code());
    }

    public function testWithQueryPatternNonDefaultLangAlias()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions();
        $originalUrl = 'https://www.example.com/news/blog/post.html?wovn=french';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('fr', $url->lang()->code());
    }

    public function testWithQueryPatternNonDefaultLangVariation1()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions();
        $originalUrl = 'https://www.example.com/news/blog/post.html?page=3&wovn=fr';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('fr', $url->lang()->code());
    }

    public function testWithSubdomainPatternDefaultLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'https://www.example.com/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testWithSubdomainPatternInvalidLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'https://jp.example.com/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testWithSubdomainPatternInvalidLangVariation1()
    {
        $target = array('en');
        $default = 'en';
        $alias = array();
        $directory = new WovnLangDirectory($target, $default, $alias);
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'http://fr-test.my-site.com/index.php';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testWithSubdomainPatternNonDefaultLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'https://ja.www.example.com/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testWithSubdomainPatternNonDefaultLangAlias()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'https://japanese.www.example.com/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testWithSubdomainPatternNonDefaultLangVariation1()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'https://ja.example.com/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testWithSubdomainPatternNonDefaultLangVariation2()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'https://ja.example.co.jp/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testWithPathPatternDefaultLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path'));
        $originalUrl = 'https://www.example.com/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testWithPathPatternDefaultLangVariation1()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path'));
        $originalUrl = 'https://www.example.com';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testWithPathPatternDefaultLangVariation2()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path'));
        $originalUrl = 'https://www.example.com/ja';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testWithPathPatternDefaultLangWithDefaultLangAlias()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_CUSTOM_LANG_ALIASES => array('en' => 'custom_en')));
        $originalUrl = 'https://www.example.com/custom_en';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testWithPathPatternNonDefaultLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path'));
        $originalUrl = 'https://www.example.com/ja/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testWithPathPatternNonDefaultLangVariation1()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path'));
        $originalUrl = 'https://www.example.com/ja/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testWithPathPatternNonDefaultLangVariation2()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path'));
        $originalUrl = 'https://www.example.com/ja/';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testWithPathPatternDefaultLangWithPathPrefix()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testWithPathPatternDefaultLangWithPathPrefixVariation1()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/ja';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testWithPathPatternDefaultLangWithPathPrefixVariation2()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/ja/news/blog/ja';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testWithPathPatternNonDefaultLangWithPathPrefix()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/ja/';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testWithPathPatternNonDefaultLangWithPathPrefixVariation1()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/ja/page.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testWithPathPatternNonDefaultLangWithPathPrefixVariation2()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/ja/text/next/page.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testWithPathPatternNonDefaultLangWithPathPrefixVariation3()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/ja/text/next/';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testWithPathPatternNonDefaultLangWithPathPrefixVariation4()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/ja/text/next/blog/ja/';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }
}
