<?php
use Wovnio\Wovnphp\Lang;

class LangTest extends PHPUnit_Framework_TestCase {
  public function testLangExist () {
    $this->assertTrue(class_exists('Wovnio\Wovnphp\Lang'));
    $this->assertClassHasStaticAttribute("lang", 'Wovnio\Wovnphp\Lang');
  }

  public function testLangLength () {
    $this->assertEquals(30, count(Lang::$lang));
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
    $this->assertEquals('ar',       Lang::iso639_1Normalization('ar'));
    $this->assertEquals('bg',       Lang::iso639_1Normalization('bg'));
    $this->assertEquals('zh-Hans',  Lang::iso639_1Normalization('zh-CHS'));
    $this->assertEquals('zh-Hant',  Lang::iso639_1Normalization('zh-CHT'));
    $this->assertEquals('da',       Lang::iso639_1Normalization('da'));
    $this->assertEquals('nl',       Lang::iso639_1Normalization('nl'));
    $this->assertEquals('en',       Lang::iso639_1Normalization('en'));
    $this->assertEquals('fi',       Lang::iso639_1Normalization('fi'));
    $this->assertEquals('fr',       Lang::iso639_1Normalization('fr'));
    $this->assertEquals('de',       Lang::iso639_1Normalization('de'));
    $this->assertEquals('el',       Lang::iso639_1Normalization('el'));
    $this->assertEquals('he',       Lang::iso639_1Normalization('he'));
    $this->assertEquals('id',       Lang::iso639_1Normalization('id'));
    $this->assertEquals('it',       Lang::iso639_1Normalization('it'));
    $this->assertEquals('ja',       Lang::iso639_1Normalization('ja'));
    $this->assertEquals('ko',       Lang::iso639_1Normalization('ko'));
    $this->assertEquals('ms',       Lang::iso639_1Normalization('ms'));
    $this->assertEquals('my',       Lang::iso639_1Normalization('my'));
    $this->assertEquals('ne',       Lang::iso639_1Normalization('ne'));
    $this->assertEquals('no',       Lang::iso639_1Normalization('no'));
    $this->assertEquals('pl',       Lang::iso639_1Normalization('pl'));
    $this->assertEquals('pt',       Lang::iso639_1Normalization('pt'));
    $this->assertEquals('ru',       Lang::iso639_1Normalization('ru'));
    $this->assertEquals('es',       Lang::iso639_1Normalization('es'));
    $this->assertEquals('sv',       Lang::iso639_1Normalization('sv'));
    $this->assertEquals('th',       Lang::iso639_1Normalization('th'));
    $this->assertEquals('hi',       Lang::iso639_1Normalization('hi'));
    $this->assertEquals('tr',       Lang::iso639_1Normalization('tr'));
    $this->assertEquals('uk',       Lang::iso639_1Normalization('uk'));
    $this->assertEquals('vi',       Lang::iso639_1Normalization('vi'));
  }

}
