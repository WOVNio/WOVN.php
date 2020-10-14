<?php
namespace Wovnio\Wovnphp\Tests;

require_once 'src/wovnio/wovnphp/custom_domain/CustomDomainLangs.php';
require_once 'src/wovnio/wovnphp/custom_domain/CustomDomainLangUrlHandler.php';

use Wovnio\Wovnphp\CustomDomainLangs;
use Wovnio\Wovnphp\CustomDomainLangUrlHandler;

class CustomDomainLangUrlHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $customDomainLangsSetting;
    private $customDomainLangs;

    protected function setUp()
    {
        $this->customDomainLangsSetting = array(
            'foo.com/' => 'fr',
            'foo.com/path' => 'ja',
            'foo.com/dir/path' => 'zh-CHS',
            'english.foo.com/' => 'en',
            'zh-hant-hk.foo.com/zh' => 'zh-Hant-HK'
        );
        $this->customDomainLangs = new CustomDomainLangs($this->customDomainLangsSetting, 'en');
    }

    public function testAddCustomDomainLangToAbsoluteUrl()
    {
        // apply to original lang
        $this->assertEquals('foo.com', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('foo.com', 'fr', $this->customDomainLangs));
        $this->assertEquals('foo.com/path', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('foo.com', 'ja', $this->customDomainLangs));
        $this->assertEquals('foo.com/dir/path', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('foo.com', 'zh-CHS', $this->customDomainLangs));
        $this->assertEquals('english.foo.com', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('foo.com', 'en', $this->customDomainLangs));
        $this->assertEquals('zh-hant-hk.foo.com/zh', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('foo.com', 'zh-Hant-HK', $this->customDomainLangs));

        // apply to target lang
        $this->assertEquals('foo.com', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('zh-hant-hk.foo.com/zh', 'fr', $this->customDomainLangs));
        $this->assertEquals('foo.com/path', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('zh-hant-hk.foo.com/zh', 'ja', $this->customDomainLangs));
        $this->assertEquals('foo.com/dir/path', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('zh-hant-hk.foo.com/zh', 'zh-CHS', $this->customDomainLangs));
        $this->assertEquals('english.foo.com', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('zh-hant-hk.foo.com/zh', 'en', $this->customDomainLangs));
        $this->assertEquals('zh-hant-hk.foo.com/zh', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('zh-hant-hk.foo.com/zh', 'zh-Hant-HK', $this->customDomainLangs));

        $this->assertEquals('zh-hant-hk.foo.com/zh', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('foo.com/path', 'zh-Hant-HK', $this->customDomainLangs));
        $this->assertEquals('zh-hant-hk.foo.com/zh/', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('foo.com/path/', 'zh-Hant-HK', $this->customDomainLangs));
        $this->assertEquals('zh-hant-hk.foo.com/zh/index.html', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('foo.com/path/index.html', 'zh-Hant-HK', $this->customDomainLangs));
        $this->assertEquals('zh-hant-hk.foo.com/zh/path2/index.html', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('foo.com/path/path2/index.html', 'zh-Hant-HK', $this->customDomainLangs));
        $this->assertEquals('zh-hant-hk.foo.com/zh/path2/index.html?test=1', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('foo.com/path/path2/index.html?test=1', 'zh-Hant-HK', $this->customDomainLangs));
        $this->assertEquals('zh-hant-hk.foo.com/zh/path2/index.html#hash', CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl('foo.com/path/path2/index.html#hash', 'zh-Hant-HK', $this->customDomainLangs));
    }

    public function testChangeToNewCustomDomainLang()
    {
        $fr = $this->customDomainLangs->getCustomDomainLangByLang('fr');
        $ja = $this->customDomainLangs->getCustomDomainLangByLang('ja');
        $zh_chs = $this->customDomainLangs->getCustomDomainLangByLang('zh-CHS');
        $en = $this->customDomainLangs->getCustomDomainLangByLang('en');
        $zh_hant_hk = $this->customDomainLangs->getCustomDomainLangByLang('zh-Hant-HK');

        $this->assertEquals('foo.com', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('foo.com', $fr, $fr));
        $this->assertEquals('foo.com/path', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('foo.com', $fr, $ja));
        $this->assertEquals('foo.com/dir/path', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('foo.com', $fr, $zh_chs));
        $this->assertEquals('english.foo.com', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('foo.com', $fr, $en));
        $this->assertEquals('zh-hant-hk.foo.com/zh', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('foo.com', $fr, $zh_hant_hk));

        $this->assertEquals('foo.com', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zh', $zh_hant_hk, $fr));
        $this->assertEquals('foo.com/path', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zh', $zh_hant_hk, $ja));
        $this->assertEquals('foo.com/dir/path', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zh', $zh_hant_hk, $zh_chs));
        $this->assertEquals('english.foo.com', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zh', $zh_hant_hk, $en));
        $this->assertEquals('zh-hant-hk.foo.com/zh', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zh', $zh_hant_hk, $zh_hant_hk));

        $this->assertEquals('foo.com/path', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zh', $zh_hant_hk, $ja));
        $this->assertEquals('foo.com/path/', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zh/', $zh_hant_hk, $ja));
        $this->assertEquals('foo.com/path/index.html', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zh/index.html', $zh_hant_hk, $ja));
        $this->assertEquals('foo.com/path/path', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zh/path', $zh_hant_hk, $ja));
        $this->assertEquals('foo.com/path/path/index.html', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zh/path/index.html', $zh_hant_hk, $ja));
        $this->assertEquals('foo.com/path/path/index.html?test=1', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zh/path/index.html?test=1', $zh_hant_hk, $ja));
        $this->assertEquals('foo.com/path/path/index.html#hash', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zh/path/index.html#hash', $zh_hant_hk, $ja));

        $this->assertEquals('zh-hant-hk.foo.com/zhtrap', CustomDomainLangUrlHandler::changeToNewCustomDomainLang('zh-hant-hk.foo.com/zhtrap', $zh_hant_hk, $ja));
    }
}
