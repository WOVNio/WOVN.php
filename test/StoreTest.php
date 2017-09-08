<?php
ini_set('error_log', null);
// WARNING all the tests making calls to redis server are commented until we found a way to mock calls 
// this waste ressources

use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Lang;

class StoreTest extends PHPUnit_Framework_TestCase {
  protected function setUp() {
    $currentSettingsFile = dirname(__FILE__) . '/current_settings.ini';
    if (file_exists($currentSettingsFile)) {
      unlink($currentSettingsFile);
    }
  }

  private function getMockAndRegister($originalClassName, $methods) {
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

  public function testStoreExists() {
    $this->assertTrue(class_exists('Wovnio\Wovnphp\Store'));
  }

  public function testQuerySettingOneParam() {
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
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertEquals(array('a='), $store->settings['query']);
  }

  public function testQuerySettingTwoParam() {
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
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertEquals(array('a=', 'b='), $store->settings['query']);
  }

  public function testQuerySettingTwoParamsSorting() {
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
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertEquals(array('a=', 'b='), $store->settings['query']);
  }

  public function testUseProxySetting() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'use_proxy = 1' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertEquals('https://api.wovn.io/v0/', $store->settings['api_url']);
    $this->assertArrayHasKey('use_proxy', $store->settings);
    $this->assertEquals(1, $store->settings['use_proxy']);
  }

  public function testWovnDevModeSettingOn() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'wovn_dev_mode = 1' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertArrayHasKey('wovn_dev_mode', $store->settings);
    $this->assertEquals(1, $store->settings['wovn_dev_mode']);
  }

  public function testWovnDevModeSettingOff() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'wovn_dev_mode = 0' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertArrayHasKey('wovn_dev_mode', $store->settings);
    $this->assertEquals(0, $store->settings['wovn_dev_mode']);
  }

  public function testWovnDevModeSettingOffByDefault() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertArrayHasKey('wovn_dev_mode', $store->settings);
    $this->assertEquals(0, $store->settings['wovn_dev_mode']);
  }

  public function testApiUrlSettingWithWovnDevModeOn() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'wovn_dev_mode = 1' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertEquals('http://api.dev-wovn.io:3000/v0/', $store->settings['api_url']);
  }

  public function testApiUrlSettingWithWovnDevModeOff() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'wovn_dev_mode = 0' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertEquals('https://api.wovn.io/v0/', $store->settings['api_url']);
  }

  public function testCustomApiUrlSettingNotChangedWithWovnDevModeOn() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'api_url = "https://test-api.io"' . "\n" .
            'wovn_dev_mode = 1' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertEquals('https://test-api.io', $store->settings['api_url']);
  }

  public function testCustomApiUrlSettingNotChangedWithWovnDevModeOff() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'api_url = "https://test-api.io"' . "\n" .
            'wovn_dev_mode = 0' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertEquals('https://test-api.io', $store->settings['api_url']);
  }
}
