<?php
  namespace Wovnio\Wovnphp;

  /**
   * The Store class contains the user settings 
   */
  class Store {
    public $settings;
    public $config_loaded = false;
    public static $config_dir;
    public $return_value_on_error_handle = false;
    private $values = null;

    /**
     *  Constructor of the Store class
     *  
     *  @param string $settingsFile A settings file name 
     *  @return void
     */
    public function __construct($settingsFile='') {
      if ($settingsFile==='') {
        $settingsFile = self::$config_dir . '/config.ini';
      }
      $defaultSettings = $this->defaultSettings();
      if (file_exists($settingsFile)) {
        $userSettings = $this->updateSettings(parse_ini_file($settingsFile));
      }
      else {
        $userSettings = array();
      }
      $installSettings = self::installIniSettings();
      $this->settings = array_merge($defaultSettings, $userSettings, $installSettings);
      //$this->refreshSettings();

      // Use default api_url property when user api_url property is empty.
      if ($this->settings['api_url'] === '') {
        $this->settings['api_url'] = $defaultSettings['api_url'];
      }

      // Use default timeout if not set
      if ($this->settings['api_timeout'] === '') {
        $this->settings['api_timeout'] = $defaultSettings['api_timeout'];
      }
    }

    private function defaultSettings() {
      return array(
        'project_token' => '',
        'url_pattern_name' => 'path',
        'url_pattern_reg' => '\/(?P<lang>[^\/.]+)(\/|\?|$)',
        'query' => array(),
        'api_url' => 'http://api.dev-wovn.io:3000/v0/',
        'api_error_host' => 'api.wovn.io',
        'api_error_port' => 443,
        'api_error_path' => '/v0/errors',
        'api_timeout' => 1.0,
        'default_lang' => 'en',
        'supported_langs' => array('en'),
        'test_mode' => false,
        'test_url' => '',
        'use_proxy' => false,
        'override_content_length' => false,
        'clean_unprocessable_characters' => false,
        'include_dir' => '',
        'directory_index' => '',
        'wovn_dev_mode' => false,
        'use_server_error_settings' => false
      );
    }

    private function installIniSettings() {
      $iniFilepath = self::$config_dir . '/install.ini';

      if (file_exists($iniFilepath)) {
        $installIni = parse_ini_file($iniFilepath);
        $includeDir = array_key_exists('includeDir', $installIni) ? $installIni['includeDir'] : '';
        $directoryIndex = array_key_exists('directoryIndex', $installIni) ? $installIni['directoryIndex'] : 'index.php';

        return array('include_dir' => $includeDir, 'directory_index' => $directoryIndex);
      }

      return array();
    }

    public function isLiveMode() {
      return !$this->settings['test_mode'];
    }

    public function isTestMode() {
      return $this->settings['test_mode'];
    }
  }
  Store::$config_dir = dirname(dirname(dirname(dirname(__FILE__))));
