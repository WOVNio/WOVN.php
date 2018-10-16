<?php
use Wovnio\Wovnphp\Lang;

class LangTest extends PHPUnit_Framework_TestCase {
  public function testLangExist () {
    $this->assertTrue(class_exists('Wovnio\Wovnphp\Lang'));
    $this->assertClassHasStaticAttribute("lang", 'Wovnio\Wovnphp\Lang');
  }

  public function testLangLength () {
    $this->assertEquals(39, count(Lang::$lang));
  }

  public function testKeysExist () {
    foreach(Lang::$lang as $key => $lang) {
      $this->assertArrayHasKey('name', $lang);
      $this->assertArrayHasKey('code', $lang);
      $this->assertArrayHasKey('en', $lang);
      $this->assertEquals($key, $lang['code']);
    }
  }

  public function testGetCodeWithValidCode () {
    $this->assertEquals('ms', Lang::getCode('ms'));
  }

  public function testGetCodeWithValidEnglishName () {
    $this->assertEquals('pt', Lang::getCode('Portuguese'));
  }

  public function testGetCodeWithValidNativeName () {
    $this->assertEquals('hi', Lang::getCode('हिन्दी'));
  }

  public function testGetCodeWithInvalidName () {
    $this->assertEquals(null, Lang::getCode('WOVN4LYFE'));
  }

  public function testGetCodeWithEmptyString () {
    $this->assertEquals(null, Lang::getCode(''));
  }

  public function testGetCodeWithNil () {
    $this->assertEquals(null, Lang::getCode(null));
  }

  public function testGetEnglishNamesArray () {
    $array = Lang::getEnglishNamesArray();
    $this->assertTrue(is_array($array));
  }

  public function testISO639_1Normalization () {
    $this->assertEquals('ar',       Lang::iso6391Normalization('ar'));
    $this->assertEquals('eu',       Lang::iso6391Normalization('eu'));
    $this->assertEquals('bn',       Lang::iso6391Normalization('bn'));
    $this->assertEquals('bg',       Lang::iso6391Normalization('bg'));
    $this->assertEquals('ca',       Lang::iso6391Normalization('ca'));
    $this->assertEquals('zh-Hans',  Lang::iso6391Normalization('zh-CHS'));
    $this->assertEquals('zh-Hant',  Lang::iso6391Normalization('zh-CHT'));
    $this->assertEquals('da',       Lang::iso6391Normalization('da'));
    $this->assertEquals('nl',       Lang::iso6391Normalization('nl'));
    $this->assertEquals('en',       Lang::iso6391Normalization('en'));
    $this->assertEquals('fi',       Lang::iso6391Normalization('fi'));
    $this->assertEquals('fr',       Lang::iso6391Normalization('fr'));
    $this->assertEquals('gl',       Lang::iso6391Normalization('gl'));
    $this->assertEquals('de',       Lang::iso6391Normalization('de'));
    $this->assertEquals('el',       Lang::iso6391Normalization('el'));
    $this->assertEquals('he',       Lang::iso6391Normalization('he'));
    $this->assertEquals('hu',       Lang::iso6391Normalization('hu'));
    $this->assertEquals('id',       Lang::iso6391Normalization('id'));
    $this->assertEquals('it',       Lang::iso6391Normalization('it'));
    $this->assertEquals('ja',       Lang::iso6391Normalization('ja'));
    $this->assertEquals('ko',       Lang::iso6391Normalization('ko'));
    $this->assertEquals('lv',       Lang::iso6391Normalization('lv'));
    $this->assertEquals('ms',       Lang::iso6391Normalization('ms'));
    $this->assertEquals('my',       Lang::iso6391Normalization('my'));
    $this->assertEquals('ne',       Lang::iso6391Normalization('ne'));
    $this->assertEquals('no',       Lang::iso6391Normalization('no'));
    $this->assertEquals('fa',       Lang::iso6391Normalization('fa'));
    $this->assertEquals('pl',       Lang::iso6391Normalization('pl'));
    $this->assertEquals('pt',       Lang::iso6391Normalization('pt'));
    $this->assertEquals('ru',       Lang::iso6391Normalization('ru'));
    $this->assertEquals('es',       Lang::iso6391Normalization('es'));
    $this->assertEquals('sw',       Lang::iso6391Normalization('sw'));
    $this->assertEquals('sv',       Lang::iso6391Normalization('sv'));
    $this->assertEquals('th',       Lang::iso6391Normalization('th'));
    $this->assertEquals('hi',       Lang::iso6391Normalization('hi'));
    $this->assertEquals('tr',       Lang::iso6391Normalization('tr'));
    $this->assertEquals('uk',       Lang::iso6391Normalization('uk'));
    $this->assertEquals('ur',       Lang::iso6391Normalization('ur'));
    $this->assertEquals('vi',       Lang::iso6391Normalization('vi'));
    $this->assertEquals(null,       Lang::iso6391Normalization('fake'));
  }

}
