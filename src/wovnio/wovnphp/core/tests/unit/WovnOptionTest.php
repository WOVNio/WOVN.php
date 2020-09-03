<?php

namespace Wovnio\Wovnphp\Core\Tests\Unit;

require_once 'src/wovnio/wovnphp/core/WovnOption.php';
require_once 'src/wovnio/wovnphp/core/exceptions/WovnException.php';
require_once 'src/wovnio/wovnphp/core/exceptions/WovnConfigurationException.php';

use Wovnio\Wovnphp\Core\WovnOption;

class WovnOptionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorBasic()
    {
        $optionConfig = parse_ini_file(realpath(__DIR__ . '/../fixture/config/basic.ini'));
        $option = new WovnOption($optionConfig);
        self::assertEquals('TOKEN', $option->get(WovnOption::OPT_PROJECT_TOKEN));
        self::assertEquals('query', $option->get(WovnOption::OPT_URL_PATTERN_NAME));
        self::assertEquals('en', $option->get(WovnOption::OPT_DEFAULT_LANG));
        self::assertEquals(array('ja', 'fr'), $option->get(WovnOption::OPT_SUPPORTED_LANGS));
        self::assertEquals('UTF-8', $option->get(WovnOption::OPT_ENCODING));
    }

    /**
     * @expectedException Wovnio\Wovnphp\Core\WovnConfigurationException
     * @expectedExceptionMessage Option supported_langs is required by WOVN.php core.
     */
    public function testConstructorMissingRequiredConfig()
    {
        $optionConfig = parse_ini_file(realpath(__DIR__ . '/../fixture/config/basic.ini'));
        unset($optionConfig[WovnOption::OPT_SUPPORTED_LANGS]);
        new WovnOption($optionConfig);
    }

    /**
     * @expectedException Wovnio\Wovnphp\Core\WovnConfigurationException
     * @expectedExceptionMessage Option supported_langs is required by WOVN.php core.
     */
    public function testConstructorIncorrectValueType()
    {
        $optionConfig = parse_ini_file(realpath(__DIR__ . '/../fixture/config/basic.ini'));
        $optionConfig[WovnOption::OPT_SUPPORTED_LANGS] = 3;
        new WovnOption($optionConfig);
    }

    public function testConstructorDefaultValues()
    {
        $optionConfig = parse_ini_file(realpath(__DIR__ . '/../fixture/config/basic.ini'));
        $option = new WovnOption($optionConfig);
        self::assertEquals(false, $option->get(WovnOption::OPT_BYPASS_AMP));
        self::assertEquals('wovn', $option->get(WovnOption::OPT_LANG_PARAM_NAME));
    }

    public function testConstructorWithDependencyValues()
    {
        $optionConfig = parse_ini_file(realpath(__DIR__ . '/../fixture/config/basic.ini'));
        $optionConfig[WovnOption::OPT_URL_PATTERN_NAME] = 'query';
        $optionConfig[WovnOption::OPT_LANG_PARAM_NAME] = 'language';
        $option = new WovnOption($optionConfig);
        self::assertEquals('query', $option->get(WovnOption::OPT_URL_PATTERN_NAME));
        self::assertEquals('language', $option->get(WovnOption::OPT_LANG_PARAM_NAME));
    }

    /**
     * @expectedException Wovnio\Wovnphp\Core\WovnConfigurationException
     * @expectedExceptionMessage Option site_prefix_path requires dependency option url_pattern_name to be set to path!
     */
    public function testConstructorWithMissingDependencyValues()
    {
        $optionConfig = parse_ini_file(realpath(__DIR__ . '/../fixture/config/basic.ini'));
        $optionConfig[WovnOption::OPT_URL_PATTERN_NAME] = 'query';
        $optionConfig[WovnOption::OPT_SITE_PREFIX_PATH] = 'news/blog';
        new WovnOption($optionConfig);
    }
}
