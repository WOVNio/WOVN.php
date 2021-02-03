<?php


namespace Wovnio\Wovnphp\Tests\Integration;

require_once 'src/wovnio/wovnphp/CookieLang.php';
require_once(__DIR__ . '/../helpers/TestUtils.php');

use Wovnio\Test\Helpers\StoreAndHeadersFactory;
use Wovnio\Test\Helpers\TestUtils;
use Wovnio\Wovnphp\CookieLang;

use PHPUnit\Framework\TestCase;

class CookieLangTest extends TestCase
{
    protected function setUp(): void
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

    protected function tearDown(): void
    {
        TestUtils::cleanUpDirectory($this->docRoot);
    }

    public function testRequestToDefaultLangQueryPatternWithCookieShouldRedirect()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'query',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'default_lang' => 'en',
            'use_cookie_lang' => true
        ));
        $result = TestUtils::fetchURL('http://localhost/index.html', 3, array('wovn_selected_lang' => 'ja'));

        self::assertEquals(302, $result->statusCode);
        self::assertEquals('http://localhost/index.html?wovn=ja', $result->sensibleHeaders['Location']);
    }

    public function testRequestToDefaultLangPathPatternWithCookieShouldRedirect()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'path',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'default_lang' => 'en',
            'use_cookie_lang' => true
        ));
        $result = TestUtils::fetchURL('http://localhost/index.html', null, array('wovn_selected_lang' => 'ja'));

        self::assertEquals(302, $result->statusCode);
        self::assertEquals('http://localhost/ja/index.html', $result->sensibleHeaders['Location']);
    }

    public function testRequestToTargetLangWithCookieShouldNotRedirect()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'path',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'default_lang' => 'en',
            'use_cookie_lang' => true
        ));
        $result = TestUtils::fetchURL('http://localhost/zh-Hant-HK/index.html', null, array('wovn_selected_lang' => 'ja'));

        self::assertEquals(200, $result->statusCode);
    }

    public function testRequestToTargetLangWithDefaultCookieShouldNotRedirect()
    {
        copy("{$this->sourceDir}/wovn_index_sample.php", "{$this->docRoot}/wovn_index.php");
        TestUtils::writeFile("{$this->docRoot}/index.html", '<html><head></head><body>test</body></html>');
        TestUtils::setWovnIni("{$this->docRoot}/wovn.ini", array(
            'url_pattern_name' => 'path',
            'supported_langs' => array('en', 'ja', 'en-US', 'zh-Hant-HK'),
            'default_lang' => 'en',
            'use_cookie_lang' => true
        ));
        $result = TestUtils::fetchURL('http://localhost/ja/index.html', null, array('wovn_selected_lang' => 'en'));

        self::assertEquals(200, $result->statusCode);
    }
}
