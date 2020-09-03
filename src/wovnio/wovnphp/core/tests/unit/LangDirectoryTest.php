<?php


namespace Wovnio\Wovnphp\Core\Tests\Unit;

require_once 'src/wovnio/wovnphp/core/exceptions/WovnException.php';
require_once 'src/wovnio/wovnphp/core/exceptions/WovnLangException.php';
require_once 'src/wovnio/wovnphp/core/WovnLangDirectory.php';
require_once 'src/wovnio/wovnphp/core/WovnLang.php';


use Wovnio\Wovnphp\Core\WovnLangDirectory;

class LangDirectoryTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $target = array('ja', 'fr');
        $default = 'en';
        $alias = array('ja' => 'nihongo');
        $directory = new WovnLangDirectory($target, $default, $alias);
        self::assertEquals('en', $directory->getLang('en')->code());
    }

    public function testConstructorWithInvalidLangCode()
    {
        $target = array('mm', 'fr');
        $default = 'mm';
        $alias = array('mm' => 'nihongo');
        $directory = new WovnLangDirectory($target, $default, $alias);
        self::assertEmpty($directory->defaultLang());
    }

    public function testConstructorDefaultLang()
    {
        $target = array('ja', 'fr');
        $default = 'en';
        $alias = array();
        $directory = new WovnLangDirectory($target, $default, $alias);
        self::assertTrue($directory->getLang('en')->isValidLang());
        self::assertEquals('en', $directory->defaultLang()->code());
    }

    public function testConstructorTargetLangs()
    {
        $target = array('ja', 'fr');
        $default = 'en';
        $alias = array();
        $directory = new WovnLangDirectory($target, $default, $alias);
        self::assertTrue($directory->getLang('ja')->isValidLang());
        self::assertTrue($directory->getLang('fr')->isValidLang());
    }

    public function testConstructorAliases()
    {
        $target = array('ja', 'fr');
        $default = 'en';
        $alias = array('ja' => 'japanese', 'fr' => 'french');
        $directory = new WovnLangDirectory($target, $default, $alias);
        self::assertEquals('japanese', $directory->getLang('ja')->alias());
        self::assertEquals('french', $directory->getLang('fr')->alias());
    }

    public function testGetLangWithAliases()
    {
        $target = array('ja');
        $default = 'en';
        $alias = array('ja' => 'japanese');
        $directory = new WovnLangDirectory($target, $default, $alias);
        self::assertEquals('ja', $directory->getLang('japanese')->code());
    }
}
