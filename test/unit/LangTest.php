<?php
namespace Wovnio\Wovnphp\Tests;

use \Wovnio\Wovnphp\Lang;

class LangTest extends \PHPUnit_Framework_TestCase
{
    public function testLangExist()
    {
        $this->assertTrue(class_exists('\Wovnio\Wovnphp\Lang'));
        $this->assertClassHasStaticAttribute("index", 'Wovnio\Wovnphp\Lang');
    }

    public function testLangLength()
    {
        $this->assertEquals(74, count(Lang::$index));
    }

    public function testKeysExist()
    {
        foreach (Lang::$index as $key => $lang) {
            $this->assertArrayHasKey('name', $lang);
            $this->assertArrayHasKey('code', $lang);
            $this->assertArrayHasKey('en', $lang);
            $this->assertEquals($key, $lang['code']);
        }
    }

    public function testGetCodeWithValidCode()
    {
        $this->assertEquals('ms', Lang::getCode('ms'));
    }

    public function testGetCodeWithValidEnglishName()
    {
        $this->assertEquals('pt', Lang::getCode('Portuguese'));
    }

    public function testGetCodeWithValidNativeName()
    {
        $this->assertEquals('hi', Lang::getCode('हिन्दी'));
    }

    public function testGetCodeWithInvalidName()
    {
        $this->assertEquals(null, Lang::getCode('WOVN4LYFE'));
    }

    public function testGetCodeWithEmptyString()
    {
        $this->assertEquals(null, Lang::getCode(''));
    }

    public function testGetCodeWithNil()
    {
        $this->assertEquals(null, Lang::getCode(null));
    }

    public function testGetEnglishNamesArray()
    {
        $array = Lang::getEnglishNamesArray();
        $this->assertTrue(is_array($array));
    }

    public function testISO6391Normalization()
    {
        // iso6391 is same with lang code
        $this->assertEquals('ar', Lang::iso6391Normalization('ar'));
        $this->assertEquals('eu', Lang::iso6391Normalization('eu'));
        $this->assertEquals('bn', Lang::iso6391Normalization('bn'));
        $this->assertEquals('bg', Lang::iso6391Normalization('bg'));
        $this->assertEquals('ca', Lang::iso6391Normalization('ca'));
        $this->assertEquals('zh-CN', Lang::iso6391Normalization('zh-CN'));
        $this->assertEquals('zh-Hant-HK', Lang::iso6391Normalization('zh-Hant-HK'));
        $this->assertEquals('zh-Hant-TW', Lang::iso6391Normalization('zh-Hant-TW'));
        $this->assertEquals('da', Lang::iso6391Normalization('da'));
        $this->assertEquals('nl', Lang::iso6391Normalization('nl'));
        $this->assertEquals('en', Lang::iso6391Normalization('en'));
        $this->assertEquals('en-AU', Lang::iso6391Normalization('en-AU'));
        $this->assertEquals('en-CA', Lang::iso6391Normalization('en-CA'));
        $this->assertEquals('en-IN', Lang::iso6391Normalization('en-IN'));
        $this->assertEquals('en-NZ', Lang::iso6391Normalization('en-NZ'));
        $this->assertEquals('en-ZA', Lang::iso6391Normalization('en-ZA'));
        $this->assertEquals('en-GB', Lang::iso6391Normalization('en-GB'));
        $this->assertEquals('en-SG', Lang::iso6391Normalization('en-SG'));
        $this->assertEquals('en-US', Lang::iso6391Normalization('en-US'));
        $this->assertEquals('fi', Lang::iso6391Normalization('fi'));
        $this->assertEquals('fr', Lang::iso6391Normalization('fr'));
        $this->assertEquals('fr-CA', Lang::iso6391Normalization('fr-CA'));
        $this->assertEquals('fr-FR', Lang::iso6391Normalization('fr-FR'));
        $this->assertEquals('fr-CH', Lang::iso6391Normalization('fr-CH'));
        $this->assertEquals('gl', Lang::iso6391Normalization('gl'));
        $this->assertEquals('de', Lang::iso6391Normalization('de'));
        $this->assertEquals('de-AT', Lang::iso6391Normalization('de-AT'));
        $this->assertEquals('de-DE', Lang::iso6391Normalization('de-DE'));
        $this->assertEquals('de-LI', Lang::iso6391Normalization('de-LI'));
        $this->assertEquals('de-CH', Lang::iso6391Normalization('de-CH'));
        $this->assertEquals('el', Lang::iso6391Normalization('el'));
        $this->assertEquals('he', Lang::iso6391Normalization('he'));
        $this->assertEquals('hu', Lang::iso6391Normalization('hu'));
        $this->assertEquals('id', Lang::iso6391Normalization('id'));
        $this->assertEquals('it', Lang::iso6391Normalization('it'));
        $this->assertEquals('it-IT', Lang::iso6391Normalization('it-IT'));
        $this->assertEquals('it-CH', Lang::iso6391Normalization('it-CH'));
        $this->assertEquals('ja', Lang::iso6391Normalization('ja'));
        $this->assertEquals('ko', Lang::iso6391Normalization('ko'));
        $this->assertEquals('lv', Lang::iso6391Normalization('lv'));
        $this->assertEquals('ms', Lang::iso6391Normalization('ms'));
        $this->assertEquals('my', Lang::iso6391Normalization('my'));
        $this->assertEquals('ne', Lang::iso6391Normalization('ne'));
        $this->assertEquals('no', Lang::iso6391Normalization('no'));
        $this->assertEquals('fa', Lang::iso6391Normalization('fa'));
        $this->assertEquals('pl', Lang::iso6391Normalization('pl'));
        $this->assertEquals('pt', Lang::iso6391Normalization('pt'));
        $this->assertEquals('pt-BR', Lang::iso6391Normalization('pt-BR'));
        $this->assertEquals('pt-PT', Lang::iso6391Normalization('pt-PT'));
        $this->assertEquals('ru', Lang::iso6391Normalization('ru'));
        $this->assertEquals('es', Lang::iso6391Normalization('es'));
        $this->assertEquals('es-RA', Lang::iso6391Normalization('es-RA'));
        $this->assertEquals('es-CL', Lang::iso6391Normalization('es-CL'));
        $this->assertEquals('es-CO', Lang::iso6391Normalization('es-CO'));
        $this->assertEquals('es-CR', Lang::iso6391Normalization('es-CR'));
        $this->assertEquals('es-HN', Lang::iso6391Normalization('es-HN'));
        $this->assertEquals('es-419', Lang::iso6391Normalization('es-419'));
        $this->assertEquals('es-MX', Lang::iso6391Normalization('es-MX'));
        $this->assertEquals('es-PE', Lang::iso6391Normalization('es-PE'));
        $this->assertEquals('es-ES', Lang::iso6391Normalization('es-ES'));
        $this->assertEquals('es-US', Lang::iso6391Normalization('es-US'));
        $this->assertEquals('es-UY', Lang::iso6391Normalization('es-UY'));
        $this->assertEquals('es-VE', Lang::iso6391Normalization('es-VE'));
        $this->assertEquals('sw', Lang::iso6391Normalization('sw'));
        $this->assertEquals('sv', Lang::iso6391Normalization('sv'));
        $this->assertEquals('tl', Lang::iso6391Normalization('tl'));
        $this->assertEquals('th', Lang::iso6391Normalization('th'));
        $this->assertEquals('hi', Lang::iso6391Normalization('hi'));
        $this->assertEquals('tr', Lang::iso6391Normalization('tr'));
        $this->assertEquals('uk', Lang::iso6391Normalization('uk'));
        $this->assertEquals('ur', Lang::iso6391Normalization('ur'));
        $this->assertEquals('vi', Lang::iso6391Normalization('vi'));

        // iso6391 is not same with lang code
        $this->assertEquals('zh-Hans', Lang::iso6391Normalization('zh-CHS'));
        $this->assertEquals('zh-Hant', Lang::iso6391Normalization('zh-CHT'));
        $this->assertEquals(null, Lang::iso6391Normalization('fake'));
        $this->assertEquals(null, Lang::iso6391Normalization(null));
    }
}
