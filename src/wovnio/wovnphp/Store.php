<?php
  namespace Wovnio\Wovnphp;
  use Wovnio\Html\HtmlConverter;

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
     * @param string $settingFileName
     * @return Store
     */
    public static function createFromFile($settingFileName) {
      if (file_exists($settingFileName)) {
        $userSettings = parse_ini_file($settingFileName, true);
      }
      else {
        $userSettings = null;
      }

      return new Store($userSettings);
    }

    /**
     *  Constructor of the Store class
     *  
     *  @param array $userSettings
     *  @return void
     */
    public function __construct($userSettings) {
      $defaultSettings = $this->defaultSettings();
      $userSettings = $this->updateSettings($userSettings);

      $this->settings = array_merge($defaultSettings, $userSettings);

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
        'url_pattern_name' => 'query',
        'url_pattern_reg' => '((\?.*&)|\?)wovn=(?P<lang>[^&]+)(&|$)',
        'query' => array(),
        'api_url' => 'https://wovn.global.ssl.fastly.net/v0/',
        'api_error_host' => 'api.wovn.io',
        'api_error_port' => 443,
        'api_error_path' => '/v0/errors',
        'api_timeout' => 1.0,
        'default_lang' => 'en',
        'encoding' => null,
        'supported_langs' => array('en'),
        'custom_lang_aliases' => array(),
        'test_mode' => false,
        'test_url' => '',
        'use_proxy' => false,
        'override_content_length' => false,
        'clean_unprocessable_characters' => false,
        'include_dir' => '',
        'directory_index' => '',
        'wovn_dev_mode' => false,
        'use_server_error_settings' => false,
        'disable_api_request_for_default_lang' => false,

        // without knowing much about this feature, no one should use this.
        'save_memory_by_sending_wovn_ignore_content' => false
      );
    }
  /**
     * Updates the current settings of the user in the class \n
     *
     * @param array $vals The vals to update in the settings
     * @return array The new settings of the user
     */
    public function updateSettings($vals=array()) {
      if (isset($vals['default_lang']) === false) {
        return array();
      }

      // GETTING THE LANGUAGE AND SETTING IT AS CODE
      $vals['default_lang'] = Lang::getCode($vals['default_lang']);

      // Gettting the query params array, adding = if missing and sorting
      if (isset($vals['query']) && !empty($vals['query'])) {
        foreach($vals['query'] as $k => $q) {
          if (!preg_match('/=$/', $q)) {
            $vals['query'][$k] = $q . '=';
          }
        }
        sort($vals['query'], SORT_STRING);
      }

      // getting the url pattern
      if (isset($vals['url_pattern_name']) && $vals['url_pattern_name'] === 'query') {
        $vals['url_pattern_reg'] = '((\?.*&)|\?)wovn=(?P<lang>[^&]+)(&|$)';
      }
      elseif (isset($vals['url_pattern_name']) && $vals['url_pattern_name'] === 'subdomain') {
        $vals['url_pattern_reg'] = '^(?P<lang>[^.]+)\.';
      }
      else {
        $vals['url_pattern_name'] = 'path';
        $vals['url_pattern_reg'] = '\/(?P<lang>[^\/.]+)(\/|\?|$)';
      }

      if (isset($vals['encoding']) && in_array($vals['encoding'], HtmlConverter::$supported_encodings) == false) {
        error_log('****** WOVN++ LOGGER :: Invalid encoding setting: ' . $vals['encoding'] . ' ******');
        $vals['encoding'] = null;
      }

      if (isset($vals['custom_lang_aliases']) && !is_array($vals['custom_lang_aliases'])) {
        $vals['custom_lang_aliases'] = array();
      }

      // update settings if wovn dev mode is activated
      $defaultSettings = $this->defaultSettings();
      if ($this->isWovnDevModeActivated($vals) && (!array_key_exists('api_url', $vals) || $vals['api_url'] === $defaultSettings['api_url'])) {
        $vals['api_url'] = $this->wovnProtocol($vals) . '://api.' . $this->wovnHost($vals) . '/v0/';
      }

      $this->config_loaded = true;

      return $vals;
    }

    private function isWovnDevModeActivated($settings=null) {
      if ($settings === null) $settings = $this->settings;

      return array_key_exists('wovn_dev_mode', $settings) && $settings['wovn_dev_mode'];
    }

    public function wovnProtocol($settings=null) {
      if ($settings === null) $settings = $this->settings;

      return ($this->isWovnDevModeActivated($settings)) ? 'http' : 'https';
    }

    public function wovnHost($settings=null) {
      if ($settings === null) $settings = $this->settings;

      return ($this->isWovnDevModeActivated($settings)) ? 'dev-wovn.io:3000' : 'wovn.io';
    }

    public function convertToCustomLangCode($lang_code) {
      if (isset($this->settings['custom_lang_aliases'][$lang_code])) {
        return $this->settings['custom_lang_aliases'][$lang_code];
      }

      return $lang_code;
    }

    public function convertToOriginalCode($lang_code) {
      foreach($this->settings['custom_lang_aliases'] as $lang => $custom_lang) {
        if ($lang_code == $custom_lang) {
          return $lang;
        }
      }

      return $lang_code;
    }
  }
