<?php
namespace Wovnio\Wovnphp\Tests;

require_once 'src/wovnio/wovnphp/custom_domain/CustomDomainLang.php';

use Wovnio\Wovnphp\CustomDomainLang;

class CustomDomainLangTest extends \PHPUnit_Framework_TestCase
{
    private $customDomainRootPath;
    private $customDomainWithPathNoTrailingSlash;
    private $customDomainWithPathTrailingSlash;
    private $customDomainPathEncodedSpaces;

    protected function setUp()
    {
        $this->customDomainRootPath = new CustomDomainLang('foo.com', '/', 'fr');
        $this->customDomainWithPathNoTrailingSlash = new CustomDomainLang('foo.com', '/path', 'fr');
        $this->customDomainWithPathTrailingSlash = new CustomDomainLang('foo.com', '/path/', 'fr');
        $this->customDomainPathEncodedSpaces = new CustomDomainLang('foo.com', '/dir%20path', 'fr');
    }

    public function testCustomDomainLangParams()
    {
        $this->assertEquals('foo.com', $this->customDomainRootPath->getHost());
        $this->assertEquals('/', $this->customDomainRootPath->getPath());
        $this->assertEquals('fr', $this->customDomainRootPath->getLang());

        $this->assertEquals('foo.com', $this->customDomainWithPathNoTrailingSlash->getHost());
        $this->assertEquals('/path/', $this->customDomainWithPathNoTrailingSlash->getPath());
        $this->assertEquals('fr', $this->customDomainWithPathNoTrailingSlash->getLang());

        $this->assertEquals('foo.com', $this->customDomainWithPathTrailingSlash->getHost());
        $this->assertEquals('/path/', $this->customDomainWithPathTrailingSlash->getPath());
        $this->assertEquals('fr', $this->customDomainWithPathTrailingSlash->getLang());

        $this->assertEquals('foo.com', $this->customDomainPathEncodedSpaces->getHost());
        $this->assertEquals('/dir%20path/', $this->customDomainPathEncodedSpaces->getPath());
        $this->assertEquals('fr', $this->customDomainPathEncodedSpaces->getLang());
    }

    public function testIsMatchWithDifferentDomain()
    {
        $this->assertFalse($this->customDomainRootPath->isMatch(parse_url('http://otherdomain.com/other/test.html')));
    }

    public function testIsMatchWithDifferentPortNumberShouldBeIgnored()
    {
        $this->assertTrue($this->customDomainRootPath->isMatch(parse_url('http://foo.com:3000/other/test.html')));
        $this->assertTrue($this->customDomainRootPath->isMatch(parse_url('http://foo.com:80/other/test.html')));
        $this->assertTrue($this->customDomainRootPath->isMatch(parse_url('http://foo.com/other/test.html')));
    }

    public function testIsMatchWithDomainContainingSubstringShouldBeFalse()
    {
        $this->assertFalse($this->customDomainRootPath->isMatch(parse_url('http://en.foo.com/other/test.html')));
    }

    public function testIsMatchWithSameDomainShouldBeTrue()
    {
        $this->assertTrue($this->customDomainRootPath->isMatch(parse_url('http://foo.com/other/test.html')));
    }

    public function testIsMatchWithSameDomainDifferentCasingShouldBeTrue()
    {
        $this->assertTrue($this->customDomainRootPath->isMatch(parse_url('http://foo.com/other/test.html')));
    }

    public function testIsMatchWithPathStartsWithCustomPathShouldBeTrue()
    {
        $this->assertTrue($this->customDomainRootPath->isMatch(parse_url('http://foo.com')));
        $this->assertTrue($this->customDomainRootPath->isMatch(parse_url('http://foo.com/')));
        $this->assertTrue($this->customDomainRootPath->isMatch(parse_url('http://foo.com/other/test.html?foo=bar')));

        $this->assertTrue($this->customDomainWithPathNoTrailingSlash->isMatch(parse_url('http://foo.com/path')));
        $this->assertTrue($this->customDomainWithPathNoTrailingSlash->isMatch(parse_url('http://foo.com/path/')));
        $this->assertTrue($this->customDomainWithPathNoTrailingSlash->isMatch(parse_url('http://foo.com/path/other/test.html?foo=bar')));

        $this->assertTrue($this->customDomainWithPathTrailingSlash->isMatch(parse_url('http://foo.com/path')));
        $this->assertTrue($this->customDomainWithPathTrailingSlash->isMatch(parse_url('http://foo.com/path/')));
        $this->assertTrue($this->customDomainWithPathTrailingSlash->isMatch(parse_url('http://foo.com/path/other/test.html?foo=bar')));

        $this->assertTrue($this->customDomainPathEncodedSpaces->isMatch(parse_url('http://foo.com/dir%20path')));
        $this->assertTrue($this->customDomainPathEncodedSpaces->isMatch(parse_url('http://foo.com/dir%20path?foo=bar')));
    }

    public function testIsMatchWithPathMatchesSubstringShouldBeFalse()
    {
        $this->assertFalse($this->customDomainWithPathNoTrailingSlash->isMatch(parse_url('http://foo.com/pathsuffix/other/test.html')));
        $this->assertFalse($this->customDomainWithPathTrailingSlash->isMatch(parse_url('http://foo.com/pathsuffix/other/test.html')));
        $this->assertFalse($this->customDomainPathEncodedSpaces->isMatch(parse_url('http://foo.com/dir%20pathsuffix/other/test.html')));
    }

    public function testIsMatchWithPathMatchesCustomPathAsSuffixShouldBeFalse()
    {
        $this->assertFalse($this->customDomainWithPathNoTrailingSlash->isMatch(parse_url('http://foo.com/images/path/foo.png')));
        $this->assertFalse($this->customDomainWithPathTrailingSlash->isMatch(parse_url('http://foo.com/images/path/foo.png')));
    }
}
