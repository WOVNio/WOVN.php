<?php
namespace Wovnio\Wovnphp\Tests;

require_once 'src/wovnio/wovnphp/custom_domain/CustomDomainLang.php';
require_once 'src/wovnio/wovnphp/custom_domain/CustomDomainLangs.php';

use Wovnio\Wovnphp\CustomDomainLang;
use Wovnio\Wovnphp\CustomDomainLangs;

class CustomDomainLangsTest extends \PHPUnit_Framework_TestCase
{
    private $customDomainLangsSetting;
    private $customDomainLangs;

    protected function setUp()
    {
        $this->customDomainLangsSetting = array(
            'foo.com/' => 'fr',
            'foo.com/path' => 'ja',
            'foo.com/dir/path' => 'zh-CHS',
            'english.foo.com/' => 'en'
        );
        $this->customDomainLangs = new CustomDomainLangs($this->customDomainLangsSetting);
    }

    private function getLang($customDomainlang)
    {
        return $customDomainlang->getLang();
    }

    public function testGetCustomDomainLangByLang()
    {
        $this->assertEquals(null, $this->customDomainLangs->getCustomDomainLangByLang('unknown'));

        $this->assertEquals('fr', $this->getLang($this->customDomainLangs->getCustomDomainLangByLang('fr')));
        $this->assertEquals('ja', $this->getLang($this->customDomainLangs->getCustomDomainLangByLang('ja')));
        $this->assertEquals('zh-CHS', $this->getLang($this->customDomainLangs->getCustomDomainLangByLang('zh-CHS')));
        $this->assertEquals('en', $this->getLang($this->customDomainLangs->getCustomDomainLangByLang('en')));
    }

    public function testGetCustomDomainLangByUrl()
    {
        $this->assertEquals(null, $this->customDomainLangs->getCustomDomainLangByUrl('http://otherdomain.com'));
        $this->assertEquals(null, $this->customDomainLangs->getCustomDomainLangByUrl('http://otherdomain.com/path/test.html'));
        $this->assertEquals(null, $this->customDomainLangs->getCustomDomainLangByUrl('http://otherdomain.com/dir/path/test.html'));

        $this->assertEquals('fr', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com')));
        $this->assertEquals('fr', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/')));
        $this->assertEquals('fr', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/test.html')));

        $this->assertEquals('ja', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path')));
        $this->assertEquals('ja', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path/')));
        $this->assertEquals('ja', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path/dir')));
        $this->assertEquals('ja', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path/test.html')));

        $this->assertEquals('zh-CHS', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/dir/path')));
        $this->assertEquals('zh-CHS', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/dir/path/')));
        $this->assertEquals('zh-CHS', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/dir/path/dir')));
        $this->assertEquals('zh-CHS', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/dir/path/test.html')));

        $this->assertEquals('en', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://english.foo.com/dir/path')));
        $this->assertEquals('en', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://english.foo.com/dir/path/')));
        $this->assertEquals('en', $this->getLang($this->customDomainLangs->getCustomDomainLangByUrl('http://english.foo.com/dir/path/test.html')));
    }

    public function testGetCustomDomainLangByUrlWithNestedPaths()
    {
        $customDomainLangsSetting = array(
            'foo.com/path' => 'ja',
            'foo.com/path/en' => 'en',
            'foo.com/path/fr' => 'fr'
        );
        $customDomainLangs = new CustomDomainLangs($customDomainLangsSetting);
        $this->assertEquals('ja', $this->getLang($customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path')));
        $this->assertEquals('en', $this->getLang($customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path/en')));
        $this->assertEquals('fr', $this->getLang($customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path/fr')));
    }
}
