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
  public function testStoreExists() {
    $this->assertTrue(class_exists('Wovnio\Wovnphp\Store'));
  }


  public function testSettingsValidWithValidSettings() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'backend_host = "rs1.wovn.io"' . "\n" .
            'backend_port = "6379"' . "\n" . 
            'default_lang = "English"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertTrue($store->isSettingsValid());
    $this->assertEquals('https://api.wovn.io/v0/', $store->settings['api_url']);
    $this->assertArrayHasKey('use_proxy', $store->settings);
    $this->assertFalse($store->settings['use_proxy']);
  }

  public function testSettingsValidWithValidProjectSettings() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N2"' . "\n" .
            'secret_key = "00a0aaa000000a00a00000aa"' . "\n" .
            'backend_host = "rs1.wovn.io"' . "\n" .
            'backend_port = "6379"' . "\n" . 
            'default_lang = "English"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertTrue($store->isSettingsValid());
    $this->assertEquals('https://api.wovn.io/v0/', $store->settings['api_url']);
    $this->assertArrayHasKey('use_proxy', $store->settings);
    $this->assertFalse($store->settings['use_proxy']);
  }

  public function testSettingsValidWithInvalidSettingsNothingValid() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "PROJECT_TOKEN"' . "\n" .
            'backend_host = "rs1.wovn.io"' . "\n" .
            'backend_port = "6379"' . "\n" . 
            'default_lang = ""' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertFalse($store->isSettingsValid(false));
  }

  public function testSettingsValidWithInvalidSettingsEmptyLangName() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'backend_host = "rs1.wovn.io"' . "\n" .
            'backend_port = "6379"' . "\n" . 
            'default_lang = ""' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertFalse($store->isSettingsValid(false));
  }

  public function testSettingsValidWithInvalidSettingsEmptyToken() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = ""' . "\n" .
            'backend_host = "rs1.wovn.io"' . "\n" .
            'backend_port = "6379"' . "\n" . 
            'default_lang = "English"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertFalse($store->isSettingsValid(false));
  }

  public function testSettingsValidWithInvalidSettingsWrongTokenLength() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "TOOOKEEENNN"' . "\n" .
            'backend_host = "rs1.wovn.io"' . "\n" .
            'backend_port = "6379"' . "\n" . 
            'default_lang = "English"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertFalse($store->isSettingsValid(false));
  }

  public function testSettingsValidWithInvalidSettingsUnknownLangName() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'backend_host = "rs1.wovn.io"' . "\n" .
            'backend_port = "6379"' . "\n" . 
            'default_lang = "Eclwiendowenfd"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertFalse($store->isSettingsValid(false));
  }

  public function testSettingsValidWithValidSettingsTimeout() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
      'secret_key = "00a0aaa000000a00a00000aa"' . "\n" .
      'backend_host = "rs1.wovn.io"' . "\n" .
      'backend_port = "6379"' . "\n" .
      'default_lang = "English"' . "\n".
      'api_timeout = "123"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertTrue($store->isSettingsValid());
  }

  public function testSettingsValidWithInvalidSettingsTimeout() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
      'secret_key = "00a0aaa000000a00a00000aa"' . "\n" .
      'backend_host = "rs1.wovn.io"' . "\n" .
      'backend_port = "6379"' . "\n" .
      'default_lang = "Eclwiendowenfd"' . "\n".
      'api_timeout = "a"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertFalse($store->isSettingsValid(false));
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
    $this->assertTrue($store->isSettingsValid());
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
    $this->assertTrue($store->isSettingsValid());
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
    $this->assertTrue($store->isSettingsValid());
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
    $this->assertTrue($store->isSettingsValid());
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
    $this->assertTrue($store->isSettingsValid());
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
    $this->assertTrue($store->isSettingsValid());
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
    $this->assertTrue($store->isSettingsValid());
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
    $this->assertTrue($store->isSettingsValid());
    $this->assertEquals('https://test-api.io', $store->settings['api_url']);
  }

  public function testMustUseServerErrorSettingsFalseByDefault() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertTrue($store->isSettingsValid());
    $this->assertEquals(FALSE, $store->mustUseServerErrorSettings());
  }

  public function testMustUseServerErrorSettingsChecked() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'use_server_error_settings = 1' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertTrue($store->isSettingsValid());
    $this->assertArrayHasKey('use_server_error_settings', $store->settings);
    $this->assertEquals(1, $store->settings['use_server_error_settings']);
    $this->assertEquals(TRUE, $store->mustUseServerErrorSettings());
  }

  public function testMustUseServerErrorSettingsUnchecked() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'use_server_error_settings =' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);
    $this->assertTrue($store->isSettingsValid());
    $this->assertArrayHasKey('use_server_error_settings', $store->settings);
    $this->assertEquals('', $store->settings['use_server_error_settings']);
    $this->assertEquals(FALSE, $store->mustUseServerErrorSettings());
  }

  public function testGetConfig() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n".
            'default_lang = "English"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    $this->assertEquals('T0k3N', $store->getConfig('project_token'));
  }

  public function testGetConfigWithInvalidKey() {
    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n".
      'default_lang = "English"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    $this->assertEquals(null, $store->getConfig('invalid_key'));
  }

  public function testRequestValuesFromServerNotCachedNotExpired() {
    //mocks
    $values = array();
    $values['text_vals'] = array('a' => 'b');
    $values['img_vals'] = array();

    $mock = $this->getMockAndRegister('Wovnio\Wovnphp\Store', array('fileGetContentsWithTimeout'));
    $mock->expects($this->once())
         ->method('fileGetContentsWithTimeout')
         ->with(
           $this->equalTo('https://api.wovn.io/v0/values?token=T0k3N&url=http://google.com'),
           $this->equalTo(1)
         )
         ->willReturn(json_encode($values));

    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'api_url = "https://api.wovn.io/v0/"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);

    $mock->settings = $store->settings;

    $this->assertEquals($values, $mock->requestValuesFromServer('http://google.com', '', true));
  }

  public function testRequestValuesFromServerCached() {
    //mocks
    $values = array();
    $values['cached'] = true;

    $mock = $this->getMockAndRegister('Wovnio\Wovnphp\Store', array('fileGetContentsWithTimeout'));
    $mock->expects($this->once())
         ->method('fileGetContentsWithTimeout')
         ->with(
           $this->equalTo('https://api.wovn.io/v0/values?token=T0k3N&url=http://google.com'),
           $this->equalTo(1)
         )
         ->willReturn(json_encode($values));

    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'api_url = "https://api.wovn.io/v0/"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);

    $mock->settings = $store->settings;

    $this->assertEquals(array(), $mock->requestValuesFromServer('http://google.com', '', true));
  }

  public function testRequestValuesFromServerExpired() {
    //mocks
    $values = array();
    $values['text_vals'] = array('a' => 'b');
    $values['img_vals'] = array();
    $values['expired'] = true;

    $mock = $this->getMockAndRegister('Wovnio\Wovnphp\Store', array('fileGetContentsWithTimeout'));
    $mock->expects($this->once())
         ->method('fileGetContentsWithTimeout')
         ->with(
           $this->equalTo('https://api.wovn.io/v0/values?token=T0k3N&url=http://google.com'),
           $this->equalTo(1)
         )
         ->willReturn(json_encode($values));

    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'api_url = "https://api.wovn.io/v0/"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);

    $mock->settings = $store->settings;

    $this->assertEquals($values, $mock->requestValuesFromServer('http://google.com', '', true));
  }

  public function testRequestValuesFromServerEmpty() {
    //mocks
    $values = array();

    $mock = $this->getMockAndRegister('Wovnio\Wovnphp\Store', array('fileGetContentsWithTimeout'));
    $mock->expects($this->once())
         ->method('fileGetContentsWithTimeout')
         ->with(
           $this->equalTo('https://api.wovn.io/v0/values?token=T0k3N&url=http://google.com'),
           $this->equalTo(1)
         )
         ->willReturn(json_encode($values));

    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'api_url = "https://api.wovn.io/v0/"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);

    $mock->settings = $store->settings;

    $this->assertEquals($values, $mock->requestValuesFromServer('http://google.com', '', true));
  }

  public function testRequestValuesFromServerNull() {
    //mocks
    $values = null;

    $mock = $this->getMockAndRegister('Wovnio\Wovnphp\Store', array('fileGetContentsWithTimeout'));
    $mock->expects($this->once())
         ->method('fileGetContentsWithTimeout')
         ->with(
           $this->equalTo('https://api.wovn.io/v0/values?token=T0k3N&url=http://google.com'),
           $this->equalTo(1)
         )
         ->willReturn(json_encode($values));

    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'api_url = "https://api.wovn.io/v0/"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);

    $mock->settings = $store->settings;

    $this->assertEquals(array(), $mock->requestValuesFromServer('http://google.com', '', true));
  }

  public function testRequestValuesFromServer400Errors() {
    //mocks
    $values = null;

    $mock = $this->getMockAndRegister('Wovnio\Wovnphp\Store', array('fileGetContentsWithTimeout'));
    $mock->expects($this->once())
         ->method('fileGetContentsWithTimeout')
         ->with(
           $this->equalTo('https://api.wovn.io/v0/values?token=T0k3N&url=http://google.com'),
           $this->equalTo(1)
         )
         ->willReturn("{\"code\":400,\"message\":\"message\"}");

    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'api_url = "https://api.wovn.io/v0/"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);

    $mock->settings = $store->settings;

    $this->assertEquals(array(), $mock->requestValuesFromServer('http://google.com', '', true));
  }

  public function testRequestValuesFromServer500Errors() {
    //mocks
    $values = null;

    $mock = $this->getMockAndRegister('Wovnio\Wovnphp\Store', array('fileGetContentsWithTimeout'));
    $mock->expects($this->once())
         ->method('fileGetContentsWithTimeout')
         ->with(
           $this->equalTo('https://api.wovn.io/v0/values?token=T0k3N&url=http://google.com'),
           $this->equalTo(1)
         )
         ->willReturn("{\"code\":500,\"message\":\"Internal Server Error\"}");

    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
            'default_lang = "English"' . "\n" .
            'api_url = "https://api.wovn.io/v0/"' . "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);

    $mock->settings = $store->settings;

    $this->assertEquals(array(), $mock->requestValuesFromServer('http://google.com', '', true));
  }

  public function testRequestValuesTimeoutSetting() {
    //mocks
    $values = null;

    $mock = $this->getMockAndRegister('Wovnio\Wovnphp\Store', array('fileGetContentsWithTimeout'));
    $mock->expects($this->once())
      ->method('fileGetContentsWithTimeout')
      ->with(
        $this->equalTo('https://api.wovn.io/v0/values?token=T0k3N&url=http://google.com'),
        $this->equalTo(1234)
      )
      ->willReturn("{\"code\":500,\"message\":\"Internal Server Error\"}");

    $file_config = dirname(__FILE__) . '/test_config.ini';
    if (file_exists($file_config)) {
      unlink($file_config);
    }
    $data = 'project_token = "T0k3N"' . "\n" .
      'secret_key = "00a0aaa000000a00a00000aa"' . "\n" .
      'default_lang = "English"' . "\n" .
      'api_url = "https://api.wovn.io/v0/"' . "\n".
      'api_timeout = 1234'. "\n";
    file_put_contents($file_config, $data);
    $store = new Store($file_config);
    unlink($file_config);

    $mock->settings = $store->settings;

    $this->assertEquals(array(), $mock->requestValuesFromServer('http://google.com', '', true));
  }

  public function testGenerateIniFileContent() {
    $config = array();
    $config['project_token']       = 'T0k3N';
    $config['default_lang']     = 'en';
    $config['api_url']          = 'https://api.wovn.io/v0/values';
    $config['test_mode']        = null;
    $config['test_url']         = null;
    $config['url_pattern_name'] = 'path';
    $config['supported_langs']  = array('en', 'ja');
    $config['use_proxy']        = null;

    $data = Store::generateIniFileContent($config);
    $expected = "project_token = T0k3N\n"
              . "default_lang = en\n"
              . "api_url = https://api.wovn.io/v0/values\n"
              . "test_mode = \n"
              . "test_url = \n"
              . "url_pattern_name = path\n"
              . "supported_langs[0] = en\n"
              . "supported_langs[1] = ja\n"
              . "use_proxy = \n"
              . "last_change = ";
    $pattern = '/^' . preg_quote($expected, '/') . '/';
    $this->assertEquals(1, preg_match($pattern, $data));
  }

//  public function testStoreConstructor() {
//    $configFile = dirname(__FILE__) . '/../config.ini';
//    $store = new Store($configFile);
//    $this->assertArrayHasKey('project_token', $store->settings);
//    $this->assertArrayHasKey('url_pattern_name', $store->settings);
//    $this->assertArrayHasKey('url_pattern_reg', $store->settings);
//    $this->assertArrayHasKey('query', $store->settings);
//    $this->assertArrayHasKey('backend_host', $store->settings);
//    $this->assertArrayHasKey('backend_port', $store->settings);
//    $this->assertArrayHasKey('default_lang', $store->settings);
//    $this->assertArrayHasKey('supported_langs', $store->settings);
//    $this->assertEquals('KEY_FROM_CODE_SNIPPET', $store->settings['project_token']);
//    $this->assertEquals('path', $store->settings['url_pattern_name']);
//    $this->assertEquals('\/(?<lang>[^\/.]+)(\/|\?|$)', $store->settings['url_pattern_reg']);
//    $this->assertSame(array(), $store->settings['query']);
//    $this->assertEquals('rs1.wovn.io', $store->settings['backend_host']);
//    $this->assertEquals('6379', $store->settings['backend_port']);
//    $this->assertEquals('en', $store->settings['default_lang']);
//    $this->assertSame(array('en'), $store->settings['supported_langs']);
//  }

//  public function testCreateSettingsFile() {
//    $store = new Store();
//    $store->createSettingsFile();
//    $file = dirname(__FILE__) . '/../current_settings.ini';
//    $settings = parse_ini_file($file);
//    // TODO
//  }
//

//  public function testReadSettingsFile() {
//    $store = new Store();
//    $settings = $store->settings;
//    $settingsInFile = $store->readSettingsFile();
//    $this->assertSame($settings['supported_langs'], $settingsInFile['supported_langs']);
//    $this->assertSame($settings['query'], $settingsInFile['query']);
//    $this->assertEquals($settings['project_token'], $settingsInFile['project_token']); 
//    $this->assertEquals($settings['secret_key'], $settingsInFile['secret_key']); 
//    $this->assertEquals($settings['url_pattern_name'], $settingsInFile['url_pattern_name']); 
//    $this->assertEquals($settings['url_pattern_reg'], $settingsInFile['url_pattern_reg']); 
//    $this->assertEquals($settings['backend_host'], $settingsInFile['backend_host']); 
//    $this->assertEquals($settings['backend_port'], $settingsInFile['backend_port']); 
//    $this->assertEquals($settings['default_lang'], $settingsInFile['default_lang']); 
//  }

//  public function testStoreConstructorNoFileName() {
//    $store = new Store;
//    $this->assertArrayHasKey('project_token', $store->settings);
//    $this->assertArrayHasKey('url_pattern_name', $store->settings);
//    $this->assertArrayHasKey('url_pattern_reg', $store->settings);
//    $this->assertArrayHasKey('query', $store->settings);
//    $this->assertArrayHasKey('backend_host', $store->settings);
//    $this->assertArrayHasKey('backend_port', $store->settings);
//    $this->assertArrayHasKey('default_lang', $store->settings);
//    $this->assertArrayHasKey('supported_langs', $store->settings);
//    $this->assertEquals('KEY_FROM_CODE_SNIPPET', $store->settings['project_token']);
//    $this->assertEquals('path', $store->settings['url_pattern_name']);
//    $this->assertEquals('\/(?<lang>[^\/.]+)(\/|\?|$)', $store->settings['url_pattern_reg']);
//    $this->assertSame(array(), $store->settings['query']);
//    $this->assertEquals('rs1.wovn.io', $store->settings['backend_host']);
//    $this->assertEquals('6379', $store->settings['backend_port']);
//    $this->assertEquals('en', $store->settings['default_lang']);
//    $this->assertSame(array('en'), $store->settings['supported_langs']);
//  }
//
//  public function testStoreConstructorCustomFileCorrect() {
//    $file_config = dirname(__FILE__) . '/test_config.ini';
//    if (file_exists($file_config)) {
//      unlink($file_config);
//    }
//    $data = 'project_token = "9ivAX"' . "\n" .
//            'backend_host = "rs1.wovn.io"' . "\n" .
//            'backend_port = "6379"' . "\n" . 
//            'default_lang = "English"' . "\n";
//    file_put_contents($file_config, $data);
//    $store = new Store($file_config);
//    $this->assertArrayHasKey('project_token', $store->settings);
//    $this->assertArrayHasKey('url_pattern_name', $store->settings);
//    $this->assertArrayHasKey('url_pattern_reg', $store->settings);
//    $this->assertArrayHasKey('query', $store->settings);
//    $this->assertArrayHasKey('backend_host', $store->settings);
//    $this->assertArrayHasKey('backend_port', $store->settings);
//    $this->assertArrayHasKey('default_lang', $store->settings);
//    $this->assertArrayHasKey('supported_langs', $store->settings);
//    $this->assertEquals('9ivAX', $store->settings['project_token']);
//    $this->assertEquals('query', $store->settings['url_pattern_name']);
//    $this->assertEquals('((\?.*&)|\?)wovn=(?<lang>[^&]+)(&|$)', $store->settings['url_pattern_reg']);
//    $this->assertSame(array('p='), $store->settings['query']);
//    $this->assertEquals('rs1.wovn.io', $store->settings['backend_host']);
//    $this->assertEquals('6379', $store->settings['backend_port']);
//    $this->assertEquals('en', $store->settings['default_lang']);
//    $this->assertSame(array('en','ja'), $store->settings['supported_langs']);
//    if (file_exists($file_config)) {
//      unlink($file_config);
//    }
//  }

//  public function testStoreConstructorCustomFileIncorrectUser() {
//    $file_config = dirname(__FILE__) . '/test_config.ini';
//    if (file_exists($file_config)) {
//      unlink($file_config);
//    }
//    $data = 'project_token = "KEY_FROM_CODE_SNIPPET"' . "\n" .
//            'backend_host = "rs1.wovn.io"' . "\n" .
//            'backend_port = "6379"' . "\n" . 
//            'default_lang = "English"' . "\n";
//    file_put_contents($file_config, $data);
//    $store = new Store($file_config);
//    $this->assertArrayHasKey('project_token', $store->settings);
//    $this->assertArrayHasKey('url_pattern_name', $store->settings);
//    $this->assertArrayHasKey('url_pattern_reg', $store->settings);
//    $this->assertArrayHasKey('query', $store->settings);
//    $this->assertArrayHasKey('backend_host', $store->settings);
//    $this->assertArrayHasKey('backend_port', $store->settings);
//    $this->assertArrayHasKey('default_lang', $store->settings);
//    $this->assertArrayHasKey('supported_langs', $store->settings);
//    $this->assertEquals('KEY_FROM_CODE_SNIPPET', $store->settings['project_token']);
//    $this->assertEquals('path', $store->settings['url_pattern_name']);
//    $this->assertEquals('\/(?<lang>[^\/.]+)(\/|\?|$)', $store->settings['url_pattern_reg']);
//    $this->assertSame(array(), $store->settings['query']);
//    $this->assertEquals('rs1.wovn.io', $store->settings['backend_host']);
//    $this->assertEquals('6379', $store->settings['backend_port']);
//    $this->assertEquals('en', $store->settings['default_lang']);
//    $this->assertSame(array('en'), $store->settings['supported_langs']);
//    if (file_exists($file_config)) {
//      unlink($file_config);
//    }
//  }

//  public function testSettingsKeysValuesWithUser() {
//    $store = new Store;
//    $store->settings['project_token'] = '9ivAX';
//    $currentSettingsFile = dirname(__FILE__) . '/current_settings.ini';
//    if (file_exists($currentSettingsFile)) {
//      unlink($currentSettingsFile);
//    }
//    $store->refreshSettings();
//    $this->assertArrayHasKey('project_token', $store->settings);
//    $this->assertArrayHasKey('url_pattern_name', $store->settings);
//    $this->assertArrayHasKey('url_pattern_reg', $store->settings);
//    $this->assertArrayHasKey('query', $store->settings);
//    $this->assertArrayHasKey('backend_host', $store->settings);
//    $this->assertArrayHasKey('backend_port', $store->settings);
//    $this->assertArrayHasKey('default_lang', $store->settings);
//    $this->assertArrayHasKey('supported_langs', $store->settings);
//    $this->assertEquals('9ivAX', $store->settings['project_token']);
//    $this->assertEquals('query', $store->settings['url_pattern_name']);
//    $this->assertEquals('((\?.*&)|\?)wovn=(?<lang>[^&]+)(&|$)', $store->settings['url_pattern_reg']);
//    $this->assertSame(array('p='), $store->settings['query']);
//    $this->assertEquals('rs1.wovn.io', $store->settings['backend_host']);
//    $this->assertEquals('6379', $store->settings['backend_port']);
//    $this->assertEquals('en', $store->settings['default_lang']);
//    $this->assertSame(array('en','ja'), $store->settings['supported_langs']);
//  }
//  
//  public function testGetValuesCorrectUrl() {
//    $store = new Store;
//    $store->settings['project_token'] = '9ivAX';
//    $currentSettingsFile = dirname(__FILE__) . '/current_settings.ini';
//    if (file_exists($currentSettingsFile)) {
//      unlink($currentSettingsFile);
//    }
//    $store->refreshSettings();
//    $values = $store->getValues('t.wovn.io/enchiladas');
//
//    $this->assertArrayHasKey('text_vals', $values);
//    $this->assertArrayHasKey('img_vals', $values);
//    $this->assertArrayHasKey('layout_vals', $values);
//  }
//  public function testGetValuesEmptyUrl() {
//    $store = new Store;
//    $store->settings['project_token'] = '9ivAX';
//    $store->refreshSettings();
//    $values = $store->getValues('');
//    $this->assertSame(array(), $values);
//  }
//
//  public function testGetValuesNullUrl() {
//    $store = new Store;
//    $store->settings['project_token'] = '9ivAX';
//    $store->refreshSettings();
//    $values = $store->getValues(null);
//    $this->assertSame(array(), $values);
//  }
//
//  public function testGetValuesWrongUrl() {
//    $store = new Store;
//    $store->settings['project_token'] = '9ivAX';
//    $store->refreshSettings();
//    $values = $store->getValues('t.wovn.io/enchilodos');
//    $this->assertSame(array(), $values);
//  }
//
//  public function testGetValuesUrlWithHttp() {
//    $store = new Store;
//    $store->settings['project_token'] = '9ivAX';
//    $currentSettingsFile = dirname(__FILE__) . '/current_settings.ini';
//    if (file_exists($currentSettingsFile)) {
//      unlink($currentSettingsFile);
//    }
//    $store->refreshSettings();
//    $values = $store->getValues('http://t.wovn.io/enchiladas');
//
//    $this->assertArrayHasKey('text_vals', $values);
//    $this->assertArrayHasKey('img_vals', $values);
//    $this->assertArrayHasKey('layout_vals', $values);
//  }

//  public function testGetValuesUrlWithQuery() {
//    $store = new Store;
//    $store->settings['project_token'] = '9ivAX';
//    $currentSettingsFile = dirname(__FILE__) . '/current_settings.ini';
//    if (file_exists($currentSettingsFile)) {
//      unlink($currentSettingsFile);
//    }
//    $store->refreshSettings();
//    $values = $store->getValues('t.wovn.io/enchiladas?hey=yo');
//
//    $this->assertArrayHasKey('text_vals', $values);
//    $this->assertArrayHasKey('img_vals', $values);
//    $this->assertArrayHasKey('layout_vals', $values);
//  }
//  public function testRefreshSettings() {
//    $store = new Store;
//    $this->assertEquals('KEY_FROM_CODE_SNIPPET', $store->settings['project_token']);
//    $this->assertEquals('path', $store->settings['url_pattern_name']);
//    $this->assertEquals('\/(?<lang>[^\/.]+)(\/|\?|$)', $store->settings['url_pattern_reg']);
//    $this->assertSame(array(), $store->settings['query']);
//    $this->assertEquals('rs1.wovn.io', $store->settings['backend_host']);
//    $this->assertEquals('6379', $store->settings['backend_port']);
//    $this->assertEquals('en', $store->settings['default_lang']);
//    $this->assertSame(array('en'), $store->settings['supported_langs']);
//    $store->settings['project_token'] = '9ivAX';
//    $currentSettingsFile = dirname(__FILE__) . '/current_settings.ini';
//    if (file_exists($currentSettingsFile)) {
//      unlink($currentSettingsFile);
//    }
//    $store->refreshSettings();
//    $this->assertEquals('9ivAX', $store->settings['project_token']);
//    $this->assertEquals('query', $store->settings['url_pattern_name']);
//    $this->assertEquals('((\?.*&)|\?)wovn=(?<lang>[^&]+)(&|$)', $store->settings['url_pattern_reg']);
//    $this->assertSame(array('p='), $store->settings['query']);
//    $this->assertEquals('rs1.wovn.io', $store->settings['backend_host']);
//    $this->assertEquals('6379', $store->settings['backend_port']);
//    $this->assertEquals('en', $store->settings['default_lang']);
//    $this->assertSame(array('en','ja'), $store->settings['supported_langs']);
//  }
//
//  public function testRefreshSettingsWrongToken() {
//    $store = new Store;
//    $this->assertEquals('KEY_FROM_CODE_SNIPPET', $store->settings['project_token']);
//    $this->assertEquals('path', $store->settings['url_pattern_name']);
//    $this->assertEquals('\/(?<lang>[^\/.]+)(\/|\?|$)', $store->settings['url_pattern_reg']);
//    $this->assertSame(array(), $store->settings['query']);
//    $this->assertEquals('rs1.wovn.io', $store->settings['backend_host']);
//    $this->assertEquals('6379', $store->settings['backend_port']);
//    $this->assertEquals('en', $store->settings['default_lang']);
//    $this->assertSame(array('en'), $store->settings['supported_langs']);
//    $store->settings['project_token'] = '9ivA';
//    $currentSettingsFile = dirname(__FILE__) . '/current_settings.ini';
//    if (file_exists($currentSettingsFile)) {
//      unlink($currentSettingsFile);
//    }
//    $store->refreshSettings();
//    $this->assertEquals('9ivA', $store->settings['project_token']);
//    $this->assertEquals('path', $store->settings['url_pattern_name']);
//    $this->assertEquals('\/(?<lang>[^\/.]+)(\/|\?|$)', $store->settings['url_pattern_reg']);
//    $this->assertSame(array(), $store->settings['query']);
//    $this->assertEquals('rs1.wovn.io', $store->settings['backend_host']);
//    $this->assertEquals('6379', $store->settings['backend_port']);
//    $this->assertEquals('en', $store->settings['default_lang']);
//    $this->assertSame(array('en'), $store->settings['supported_langs']);
//  }

//  public function testRequestSettings() {
//    $store = new Store;
//    $store->settings['project_token'] = '9ivAX';
//    $vals = $store->requestSettings();
//    $this->assertArrayHasKey('supported_langs', $vals);
//    $this->assertArrayHasKey('url_pattern_name', $vals);
//    $this->assertArrayHasKey('url_pattern_reg', $vals);
//    $this->assertSame(array('en', 'ja'), $vals['supported_langs']);
//    $this->assertEquals('query', $vals['url_pattern_name']);
//    $this->assertEquals('?.*wovn=(?<lang>[^&]+)(&|$)', $vals['url_pattern_reg']);
//  }
//
//  public function testRequestSettingsReturnEmptyArray() {
//    $store = new Store;
//    $vals = $store->requestSettings();
//    $this->assertSame(array(), $vals);
//  }
//
//  public function testRequestSettingsUpdatedPages() {
//    $store = new Store;
//    $store->settings['project_token'] = 'aJWn3';
//    $store->settings['backend_host'] = 'localhost';
//    $vals = $store->requestSettings();
//  }
//  public function testUpdateSettingsNoValues() {
//    $store = new Store;
//    $before_update = $store->settings;
//    $store->updateSettings();
//    $after_update = $store->settings;
//    $this->assertSame($before_update, $after_update);
//  }
//
//  public function testUpdateSettingsEmptyArray() {
//    $store = new Store;
//    $before_update = $store->settings;
//    $store->updateSettings(array());
//    $after_update = $store->settings;
//    $this->assertSame($before_update, $after_update);
//  }
//
//  public function testUpdateSettingsNull() {
//    $store = new Store;
//    $before_update = $store->settings;
//    $store->updateSettings(null);
//    $after_update = $store->settings;
//    $this->assertSame($before_update, $after_update);
//  }
//
//  public function testUpdateSettingsRightValues() {
//    $store = new Store;
//    $store->settings['project_token'] = '9ivAX';
//    $before_update = $store->settings;
//    $currentSettingsFile = dirname(__FILE__) . '/current_settings.ini';
//    if (file_exists($currentSettingsFile)) {
//      unlink($currentSettingsFile);
//    }
//    $vals = $store->refreshSettings();
//    $store->updateSettings($vals);
//    $after_update = $store->settings;
//    $this->assertNotSame($before_update, $after_update);
//  }

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
}
