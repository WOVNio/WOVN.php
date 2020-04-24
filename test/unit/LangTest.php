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
        foreach (Lang::$index as $langCode => $lang) {
            if ($langCode == 'zh-CHS') {
                $this->assertEquals('zh-Hans', Lang::iso6391Normalization($langCode));
            } elseif ($langCode == 'zh-CHT') {
                $this->assertEquals('zh-Hant', Lang::iso6391Normalization($langCode));
            } else {
                $this->assertEquals($langCode, Lang::iso6391Normalization($langCode));
            }
        }
        $this->assertEquals(null, Lang::iso6391Normalization('fake'));
        $this->assertEquals(null, Lang::iso6391Normalization(null));
    }
}
