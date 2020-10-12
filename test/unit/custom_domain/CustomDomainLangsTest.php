<?php
namespace Wovnio\Wovnphp\Tests;

require_once 'src/wovnio/wovnphp/custom_domain/CustomDomainLang.php';
require_once 'src/wovnio/wovnphp/custom_domain/CustomDomainLangs.php';

use PHP_CodeSniffer\Tests\Standards\AllSniffs;
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
            'english.foo.com/' => array('lang' => 'en', 'source' => 'global.foo.com/')
        );
        $this->customDomainLangs = new CustomDomainLangs($this->customDomainLangsSetting);
    }

    private function getLang($customDomainLang)
    {
        return $customDomainLang->getLang();
    }

    private function getHostAndPathWithoutTrailingSlash($customDomainLang)
    {
        return $customDomainLang->getHostAndPathWithoutTrailingSlash();
    }

    private function hashEquals($a, $b)
    {
        if (count($a) != count($b)) {
            return false;
        }
        foreach ($a as $key => $value) {
            if (!array_key_exists($key, $b)) {
                return false;
            }
            if ($a[$key] != $b[$key]) {
                return false;
            }
        }
        return true;
    }

    public function testGetCustomDomainLangByLang()
    {
        $this->assertEquals(null, $this->customDomainLangs->getCustomDomainLangByLang('unknown'));

        $this->assertEquals('fr', $this->getLang($this->customDomainLangs->getCustomDomainLangByLang('fr')));
        $this->assertEquals('ja', $this->getLang($this->customDomainLangs->getCustomDomainLangByLang('ja')));
        $this->assertEquals('zh-CHS', $this->getLang($this->customDomainLangs->getCustomDomainLangByLang('zh-CHS')));
        $this->assertEquals('en', $this->getLang($this->customDomainLangs->getCustomDomainLangByLang('en')));
    }

    public function testGetSourceCustomDomainByLang()
    {
        $this->assertEquals('foo.com', $this->getHostAndPathWithoutTrailingSlash($this->customDomainLangs->getSourceCustomDomainByLang('fr')));
        $this->assertEquals('foo.com/path', $this->getHostAndPathWithoutTrailingSlash($this->customDomainLangs->getSourceCustomDomainByLang('ja')));
        $this->assertEquals('foo.com/dir/path', $this->getHostAndPathWithoutTrailingSlash($this->customDomainLangs->getSourceCustomDomainByLang('zh-CHS')));
        $this->assertEquals('global.foo.com', $this->getHostAndPathWithoutTrailingSlash($this->customDomainLangs->getSourceCustomDomainByLang('en')));
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

    public function testToHtmlSwapperHash()
    {
        $expected = array(
            'foo.com' => 'fr',
            'foo.com/path' => 'ja',
            'foo.com/dir/path' => 'zh-CHS',
            'english.foo.com' => 'en'
        );

        $this->assertEquals(true, $this->hashEquals($expected, $this->customDomainLangs->toHtmlSwapperHash()));
    }

    public function testComputeSourceVirtualUrlDefaultToDefault()
    {
        $currentUri = "global.foo.com/blog/entry1.html";
        $computedUri = $this->customDomainLangs->computeSourceVirtualUrl($currentUri, "en", "en");
        $expectedComputedUri = "english.foo.com/blog/entry1.html";
        $this->assertEquals($expectedComputedUri, $computedUri);
    }

    public function testComputeSourceVirtualUrlOtherToDefault()
    {
        $currentUri = "foo.com/path/blog/entry1.html";
        $computedUri = $this->customDomainLangs->computeSourceVirtualUrl($currentUri, "ja", "en");
        $expectedComputedUri = "english.foo.com/blog/entry1.html";
        $this->assertEquals($expectedComputedUri, $computedUri);
    }
}
