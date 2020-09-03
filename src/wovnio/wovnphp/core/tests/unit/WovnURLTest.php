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

    public function testConstructorWithQueryPatternInvalidLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions();
        $originalUrl = 'https://www.example.com/news/blog/post.html?wovn=mm';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testConstructorWithQueryPatternNonDefaultLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions();
        $originalUrl = 'https://www.example.com/news/blog/post.html?wovn=fr';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('fr', $url->lang()->code());
    }

    public function testConstructorWithQueryPatternNonDefaultLangAlias()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions();
        $originalUrl = 'https://www.example.com/news/blog/post.html?wovn=french';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('fr', $url->lang()->code());
    }

    public function testConstructorWithQueryPatternNonDefaultLangVariation1()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions();
        $originalUrl = 'https://www.example.com/news/blog/post.html?page=3&wovn=fr';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('fr', $url->lang()->code());
    }

    public function testConstructorWithSubdomainPatternDefaultLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'https://www.example.com/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testConstructorWithSubdomanPatternInvalidLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'https://jp.example.com/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testConstructorWithSubdomainPatternNonDefaultLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'https://ja.www.example.com/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testConstructorWithSubdomainPatternNonDefaultLangAlias()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'https://japanese.www.example.com/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testConstructorWithSubdomainPatternNonDefaultLangVariation1()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'https://ja.example.com/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testConstructorWithSubdomainPatternNonDefaultLangVariation2()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'subdomain'));
        $originalUrl = 'https://ja.example.co.jp/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testConstructorWithPathPatternDefaultLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path'));
        $originalUrl = 'https://www.example.com/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testConstructorWithPathPatternDefaultLangVariation1()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path'));
        $originalUrl = 'https://www.example.com';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testConstructorWithPathPatternDefaultLangVariation2()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path'));
        $originalUrl = 'https://www.example.com/ja';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testConstructorWithPathPatternNonDefaultLang()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path'));
        $originalUrl = 'https://www.example.com/ja/news/blog/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testConstructorWithPathPatternNonDefaultLangVariation1()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path'));
        $originalUrl = 'https://www.example.com/ja/post.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testConstructorWithPathPatternNonDefaultLangVariation2()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path'));
        $originalUrl = 'https://www.example.com/ja/';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testConstructorWithPathPatternDefaultLangWithPathPrefix()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testConstructorWithPathPatternDefaultLangWithPathPrefixVariation1()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/ja';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testConstructorWithPathPatternDefaultLangWithPathPrefixVariation2()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/ja/news/blog/ja';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('en', $url->lang()->code());
    }

    public function testConstructorWithPathPatternNonDefaultLangWithPathPrefix()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/ja/';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testConstructorWithPathPatternNonDefaultLangWithPathPrefixVariation1()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/ja/page.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testConstructorWithPathPatternNonDefaultLangWithPathPrefixVariation2()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/ja/text/next/page.html';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testConstructorWithPathPatternNonDefaultLangWithPathPrefixVariation3()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/ja/text/next/';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }

    public function testConstructorWithPathPatternNonDefaultLangWithPathPrefixVariation4()
    {
        $directory = $this->getLangDirectory();
        $options = $this->getOptions(array(WovnOption::OPT_URL_PATTERN_NAME => 'path', WovnOption::OPT_SITE_PREFIX_PATH => 'news/blog'));
        $originalUrl = 'https://www.example.com/news/blog/ja/text/next/blog/ja/';
        $url = new WovnURL($originalUrl, $directory, $options);
        self::assertEquals('ja', $url->lang()->code());
    }
}
