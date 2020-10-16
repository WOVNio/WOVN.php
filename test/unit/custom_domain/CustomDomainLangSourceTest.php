<?php

namespace Wovnio\Wovnphp\Tests;

require_once 'src/wovnio/wovnphp/custom_domain/CustomDomainLangSource.php';


use Wovnio\Wovnphp\CustomDomainLangSource;

class CustomDomainLangSourceTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructFromAbsoluteUrlNoProtocol()
    {
        $inputUrl = 'ww3.test.com/nice.html';
        $customDomainLangSource = new CustomDomainLangSource($inputUrl, 'en');
        $this->assertEquals($inputUrl, $customDomainLangSource->getHostAndPathWithoutTrailingSlash());
    }

    public function testConstructFromAbsoluteUrlWithProtocol()
    {
        $inputUrl = 'https://www.test2.com/nice.html';
        $customDomainLangSource = new CustomDomainLangSource($inputUrl, 'en');
        $this->assertEquals('www.test2.com/nice.html', $customDomainLangSource->getHostAndPathWithoutTrailingSlash());
    }

    public function testConstructFromAbsoluteUrlWithQuery()
    {
        $inputUrl = 'https://www.test2.com/nice.html?page=3';
        $customDomainLangSource = new CustomDomainLangSource($inputUrl, 'en');
        $this->assertEquals('www.test2.com/nice.html', $customDomainLangSource->getHostAndPathWithoutTrailingSlash());
    }
}
