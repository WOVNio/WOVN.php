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

    public function testNotFoundConfigurationFile()
    {
        $file_config = dirname(__FILE__) . '/notfound.ini';

        $store = Store::createFromFile($file_config);

        $this->assertEquals('Wovnio\Wovnphp\Store', get_class($store));
        $this->assertFalse($store->isValid());
    }

    public function testIsValidWithInvalidConfiguration()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = implode("\n", array(
            'project_token = ""',
            'default_lang = en',
            'supported_langs[] = en',
            'supported_langs[] = ja'
        ));
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);

        $this->assertFalse($store->isValid());
    }

    public function testIsValidWithValidConfiguration()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = implode("\n", array(
            'project_token = Token',
            'default_lang = en',
            'supported_langs[] = en',
            'supported_langs[] = ja'
        ));
        file_put_contents($file_config, $data);
        $store = Store::createFromFile($file_config);
        unlink($file_config);

        $this->assertTrue($store->isValid());
    }

    public function testEncodingSetting()
    {
        $file_config = dirname(__FILE__) . '/test_config.ini';
        if (file_exists($file_config)) {
            unlink($file_config);
        }
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
            'encoding = UTF-8',
        ));
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
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
            'encoding = INVALID_ENCODING',
        ));
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
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
        ));
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
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
            'use_proxy = 1',
        ));
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
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
            'wovn_dev_mode = 1',
        ));
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
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
            'wovn_dev_mode = 0',
        ));
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
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
        ));
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
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
            'wovn_dev_mode = 1',
        ));
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
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
            'wovn_dev_mode = 0',
        ));
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
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
            'api_url = "https://test-api.io"',
            'wovn_dev_mode = 1',
        ));
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
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
            'api_url = "https://test-api.io"',
            'wovn_dev_mode = 0',
        ));
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
        $data = implode("\n", array(
            'project_token = "T0k3N"',
            'default_lang = "English"',
            'custom_lang_aliases["ja"] = "ja-test"',
            'wovn_dev_mode = 0',
        ));
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
        $testCases = array(
            array('path', 'dir1', 'dir1'),
            array('path', '/dir1', 'dir1'),
            array('path', '/dir1/', 'dir1'),
            array('path', '/dir1/dir2', 'dir1/dir2'),
            array('path', '/dir1/dir2/', 'dir1/dir2'),
            array('path', 'dir1/dir2/', 'dir1/dir2'),
            array('subdomain', null, null)
        );
        foreach ($testCases as $case) {
            list($url_pattern_name, $site_prefix_path, $expected_site_prefix_path) = $case;

            $file_config = dirname(__FILE__) . '/test_config.ini';
            if (file_exists($file_config)) {
                unlink($file_config);
            }
            $data = implode("\n", array(
                'project_token = "T0k3N"',
                'default_lang = "en"',
                "url_pattern_name = $url_pattern_name",
                "site_prefix_path = $site_prefix_path"
            ));
            file_put_contents($file_config, $data);
            $store = Store::createFromFile($file_config);
            unlink($file_config);
            $this->assertEquals($expected_site_prefix_path, $store->settings['site_prefix_path']);
        }
    }
}
