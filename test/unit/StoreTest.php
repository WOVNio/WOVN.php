<?php
namespace Wovnio\Wovnphp\Tests\Unit;

ini_set('error_log', null);

require_once 'src/wovnio/wovnphp/Lang.php';
require_once 'src/wovnio/wovnphp/Store.php';
require_once 'src/wovnio/html/HtmlConverter.php';

use \Wovnio\Wovnphp\Store;

class StoreTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $currentSettingsFile = dirname(__FILE__) . '/current_settings.ini';
        if (file_exists($currentSettingsFile)) {
            unlink($currentSettingsFile);
        }
    }

    private function getMockAndRegister($originalClassName, $methods)
    {
        $builder = $this->getMockBuilder($originalClassName);
        $builder->setMethods($methods);
        $mockObject = $builder->getMock();
        if (method_exists($this, 'registerMockObject')) {
            $this->registerMockObject($mockObject);
        } else {
            $this->mockObjects[] = $mockObject;
        }
        return $mockObject;
    }

    public function testStoreExists()
    {
        $this->assertTrue(class_exists('\Wovnio\Wovnphp\Store'));
    }

    public function testQuerySettingOneParam()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'query[] = "a"' . "\n" .
            'backend_host = "rs1.wovn.io"' . "\n" .
            'backend_port = "6379"' . "\n" .
            'default_lang = "English"' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals(array('a='), $store->settings['query']);
    }

    public function testQuerySettingTwoParam()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'query[] = "a"' . "\n" .
            'query[] = "b"' . "\n" .
            'backend_host = "rs1.wovn.io"' . "\n" .
            'backend_port = "6379"' . "\n" .
            'default_lang = "English"' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals(array('a=', 'b='), $store->settings['query']);
    }

    public function testQuerySettingTwoParamsSorting()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'query[] = "b"' . "\n" .
            'query[] = "a"' . "\n" .
            'backend_host = "rs1.wovn.io"' . "\n" .
            'backend_port = "6379"' . "\n" .
            'default_lang = "English"' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals(array('a=', 'b='), $store->settings['query']);
    }

    public function testEncodingSetting()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'encoding = UTF-8' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals('UTF-8', $store->settings['encoding']);
    }

    public function testEncodingSettingWithInvalidEncoding()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'encoding = INVALID_ENCODING' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals(null, $store->settings['encoding']);
    }

    public function testEncodingSettingWithoutEncoding()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals(null, $store->settings['encoding']);
    }

    public function testUseProxySetting()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'use_proxy = 1' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals('https://wovn.global.ssl.fastly.net/v0/', $store->settings['api_url']);
        $this->assertArrayHasKey('use_proxy', $store->settings);
        $this->assertEquals(1, $store->settings['use_proxy']);
    }

    public function testWovnDevModeSettingOn()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'wovn_dev_mode = 1' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertArrayHasKey('wovn_dev_mode', $store->settings);
        $this->assertEquals(1, $store->settings['wovn_dev_mode']);
    }

    public function testWovnDevModeSettingOff()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'wovn_dev_mode = 0' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertArrayHasKey('wovn_dev_mode', $store->settings);
        $this->assertEquals(0, $store->settings['wovn_dev_mode']);
    }

    public function testWovnDevModeSettingOffByDefault()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertArrayHasKey('wovn_dev_mode', $store->settings);
        $this->assertEquals(0, $store->settings['wovn_dev_mode']);
    }

    public function testApiUrlSettingWithWovnDevModeOn()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'wovn_dev_mode = 1' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals('http://api.dev-wovn.io:3000/v0/', $store->settings['api_url']);
    }

    public function testApiUrlSettingWithWovnDevModeOff()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'wovn_dev_mode = 0' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals('https://wovn.global.ssl.fastly.net/v0/', $store->settings['api_url']);
    }

    public function testCustomApiUrlSettingNotChangedWithWovnDevModeOn()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'api_url = "https://test-api.io"' . "\n" .
            'wovn_dev_mode = 1' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals('https://test-api.io', $store->settings['api_url']);
    }

    public function testCustomApiUrlSettingNotChangedWithWovnDevModeOff()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'api_url = "https://test-api.io"' . "\n" .
            'wovn_dev_mode = 0' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals('https://test-api.io', $store->settings['api_url']);
    }

    public function testConvertToCustomLangCode()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'custom_lang_aliases["ja"] = "ja-test"' . "\n" .
            'wovn_dev_mode = 0' . "\n";
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals('ja-test', $store->convertToCustomLangCode('ja'));
    }

    public function testFixSupportedLangsSetWithCustomLangCode()
    {
        $sut = new Store(array(
            'project_token' => 'T0k3n_',
            'default_lang' => 'en',
            'supported_langs' => array('en', 'fr', 'cn', 'tw', 'kr'),
            'custom_lang_aliases' => array(
                'zh-CHS' => 'cn',
                'zh-CHT' => 'tw',
                'ko' => 'kr'
            )
        ));

        $this->assertEquals(array('en', 'fr', 'zh-CHS', 'zh-CHT', 'ko'), $sut->settings['supported_langs']);
    }

    public function testNoIndexLangs()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
            'no_index_langs[] = en',
            'no_index_langs[] = fr'
        ));
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals(array('en', 'fr'), $store->settings['no_index_langs']);
    }

    public function testSitePrefixPath()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "en"',
            'url_pattern_name = path',
            'site_prefix_path = dir1'
        ));
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals('dir1', $store->settings['site_prefix_path']);
        $this->assertEquals('\/dir1\/(?P<lang>[^\/.]+)(\/|\?|$)', $store->settings['url_pattern_reg']);
    }

    public function testSitePrefixPathWithDeepDirectory()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "en"',
            'url_pattern_name = path',
            'site_prefix_path = /dir1/dir2/dir3/dir4/'
        ));
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);
        $this->assertEquals('dir1/dir2/dir3/dir4', $store->settings['site_prefix_path']);
        $this->assertEquals('\/dir1\/dir2\/dir3\/dir4\/(?P<lang>[^\/.]+)(\/|\?|$)', $store->settings['url_pattern_reg']);
    }
}
