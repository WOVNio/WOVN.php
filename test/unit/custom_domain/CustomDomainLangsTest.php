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

    public function testGetCustomDomainLangByLang()
    {
        $this->assertEquals(null, $this->customDomainLangs->getCustomDomainLangByLang('unknown'));

        $this->assertEquals('fr', ($this->customDomainLangs->getCustomDomainLangByLang('fr'))->getLang());
        $this->assertEquals('ja', ($this->customDomainLangs->getCustomDomainLangByLang('ja'))->getLang());
        $this->assertEquals('zh-CHS', ($this->customDomainLangs->getCustomDomainLangByLang('zh-CHS'))->getLang());
        $this->assertEquals('en', ($this->customDomainLangs->getCustomDomainLangByLang('en'))->getLang());
    }

    public function testGetCustomDomainLangByUrl()
    {
        $this->assertEquals(null, $this->customDomainLangs->getCustomDomainLangByUrl('http://otherdomain.com'));
        $this->assertEquals(null, $this->customDomainLangs->getCustomDomainLangByUrl('http://otherdomain.com/path/test.html'));
        $this->assertEquals(null, $this->customDomainLangs->getCustomDomainLangByUrl('http://otherdomain.com/dir/path/test.html'));

        $this->assertEquals('fr', ($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com'))->getLang());
        $this->assertEquals('fr', ($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/'))->getLang());
        $this->assertEquals('fr', ($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/test.html'))->getLang());

        $this->assertEquals('ja', ($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path'))->getLang());
        $this->assertEquals('ja', ($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path/'))->getLang());
        $this->assertEquals('ja', ($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path/dir'))->getLang());
        $this->assertEquals('ja', ($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path/test.html'))->getLang());

        $this->assertEquals('zh-CHS', ($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/dir/path'))->getLang());
        $this->assertEquals('zh-CHS', ($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/dir/path/'))->getLang());
        $this->assertEquals('zh-CHS', ($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/dir/path/dir'))->getLang());
        $this->assertEquals('zh-CHS', ($this->customDomainLangs->getCustomDomainLangByUrl('http://foo.com/dir/path/test.html'))->getLang());

        $this->assertEquals('en', ($this->customDomainLangs->getCustomDomainLangByUrl('http://english.foo.com/dir/path'))->getLang());
        $this->assertEquals('en', ($this->customDomainLangs->getCustomDomainLangByUrl('http://english.foo.com/dir/path/'))->getLang());
        $this->assertEquals('en', ($this->customDomainLangs->getCustomDomainLangByUrl('http://english.foo.com/dir/path/test.html'))->getLang());
    }

    public function testGetCustomDomainLangByUrlWithNestedPaths()
    {
        $customDomainLangsSetting = array(
            'foo.com/path' => 'ja',
            'foo.com/path/en' => 'en',
            'foo.com/path/fr' => 'fr'
        );
        $customDomainLangs = new CustomDomainLangs($customDomainLangsSetting);
        $this->assertEquals('ja', ($customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path'))->getLang());
        $this->assertEquals('en', ($customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path/en'))->getLang());
        $this->assertEquals('fr', ($customDomainLangs->getCustomDomainLangByUrl('http://foo.com/path/fr'))->getLang());
    }
}
