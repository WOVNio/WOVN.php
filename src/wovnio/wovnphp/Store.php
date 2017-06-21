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
        // 'url_pattern_name' => 'query',
        // 'url_pattern_reg' => '?.*wovn=(?<lang>[^&]+)(&|$)',
        'url_pattern_name' => 'path',
        'url_pattern_reg' => '\/(?P<lang>[^\/.]+)(\/|\?|$)',
        // 'url_pattern_name' => 'subdomain',
        // 'url_pattern_reg' => '^(?<lang>[^.]+)\.',
        'query' => array(),
        'api_url' => 'https://api.wovn.io/v0/',
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
        //'directory_index' => [$_SERVER['DOCUMENT_ROOT'].'/index.html', $_SERVER['DOCUMENT_ROOT'].'/index.php'],
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

    /**
     * Check if the current user settings are valid \n
     * Will check the project_token, and the default lang \n
     *
     * @return {Boolean} true if settings are valid or false
     */
    public function isSettingsValid($show_log = true) {
      $project_token = $this->getString('project_token');
      $default_lang = $this->getString('default_lang');
      $api_timeout = $this->getString('api_timeout');
      $project_token_length = strlen($project_token);

      $valid = true;
      $valid &= $this->valid($show_log, $project_token_length === 5 || $project_token_length === 6, 'project_token missing or invalid', $project_token);
      $valid &= $this->valid($show_log, strlen($default_lang) > 0                           , 'default_lang missing', $default_lang);
      $valid &= $this->valid($show_log, (bool)Lang::getCode($default_lang)                  , 'default_lang invalid', Lang::getCode($default_lang));
      $valid &= $this->valid($show_log, is_numeric($api_timeout)                            , 'api_timeout is not number', $api_timeout);
      return (bool)$valid;
    }
    private function getString($key) {
      $dict = $this->settings;
      return isset($dict[$key]) ? (string)($dict[$key]) : '';
    }
    private function valid($show_log, $condition, $title, $value) {
      if (!$condition) {
        if ($show_log) {
          error_log('****** WOVN++ LOGGER :: ' . $title . ': ' . $value . ' ******');
        }
      }
      return $condition;
    }

    /**
     * Reads the settings file of the user (when the settings are cached in a file) \n
     * The file name should always be current_settings.ini \n
     *
     * @param {String} $file The name of the file with cached settings
     * @return {Array} The current cached settings of the user
     */
    public function readSettingsFile($file='current_settings.ini') {
      if (file_exists($file)) {
        $current_settings = parse_ini_file($file);
        if (!isset($current_settings['query'])) {
          $current_settings['query'] = array();
        }
        if (!isset($current_settings['updated_pages'])) {
          $current_settings['updated_pages'] = array();
        }
        if (!isset($current_settings['supported_langs'])) {
          $current_settings['supported_langs'] = array();
        }
        return $current_settings;
      }
      else {
        return false;
      }
    }

    /**
     * Creates the settings file with the cached settings after requesting the settings from the
     * predis server \n
     * The settings file will always be current_settings.ini and can be parsed with parse_ini_file \n
     *
     * @return void
     */
    public function createSettingsFile() {
      $file_settings = 'current_settings.ini';
      if(file_exists($file_settings)) {
        unlink($file_settings);
      }
      $timer = microtime(TRUE);
      $this->settings['last_change'] = (string) $timer;
      $data = 'project_token = "' . $this->settings['project_token'] . '"' . "\n" .
              'url_pattern_name = "' . $this->settings['url_pattern_name'] . '"' . "\n" .
              'url_pattern_reg = "' . $this->settings['url_pattern_reg'] . '"' . "\n" .
              'api_url = "' . $this->settings['api_url'] . '"' . "\n" .
              'default_lang = "' . $this->settings['default_lang'] . '"' . "\n" .
              'use_proxy = "'  .$this->settings['use_proxy'] . '"' . "\n" .
              'last_change = "' . $timer . '"' . "\n";
      foreach($this->settings['query'] as $q) {
        $data .= 'query[] = "' . $q . '"' . "\n"; 
      }
      foreach($this->settings['updated_pages'] as $up) {
        $data .= 'updated_pages[] = "' . $up . '"' . "\n"; 
      }
      foreach($this->settings['supported_langs'] as $sl) {
        $data .= 'supported_langs[] = "' . $sl . '"' . "\n"; 
      }
      file_put_contents($file_settings, $data, LOCK_EX);
    }

    /**
     * Updates the current settings of the user in the class \n
     *
     * @param {Array} $vals The vals to update in the settings
     * @return {Array} The new settings of the user
     */
    public function updateSettings($vals=array()) {
      // GETTING THE LANGUAGE AND SETTING IT AS CODE
      $vals['default_lang'] = Lang::getCode($vals['default_lang']);

      // checking if the user wants to use query
     // if (isset($vals['use_query'])) {
     //   $vals['use_query'] = true;
     // } else {
     //   $vals['use_query'] = false;
     // }

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

      // update settings if wovn dev mode is activated
      $defaultSettings = $this->defaultSettings();
      if ($this->isWovnDevModeActivated($vals) && (!array_key_exists('api_url', $vals) || $vals['api_url'] === $defaultSettings['api_url'])) {
        $vals['api_url'] = $this->wovnProtocol($vals) . '://api.' . $this->wovnHost($vals) . '/v0/';
      }

      //$vals['directory_index'] = preg_replace('/\s+/', ' ', $vals['directory_index']);
      //$vals['directory_index'] = explode(' ', $vals['directory_index']);
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

    public function mustUseServerErrorSettings() {
      return array_key_exists('use_server_error_settings', $this->settings) && $this->settings['use_server_error_settings'] === '1';
    }

    public function getConfig($key) {
      if (array_key_exists($key, $this->settings)) {
        return $this->settings[$key];
      }
      return null;
    }

    /**
     * Get the values of the page \n
     * Either requests the values from the redis server or get the cached values \n 
     * The cached values must be updated frequently \n
     *
     * @param {String} $url The url of the page
     * @return {Array} The values in an array 
     */
    public function &getValues($url='', $protocol='') {
      if ($url === '' || $url === null) {
        $values = array();
        return $values;
      }
      elseif (preg_match('/^https?\:\/\//', $url)) {
        $url = preg_replace('/^https?\:\/\//', '', $url);
      }
      // get the values of the url 
      $values = $this->requestValuesFromServer($url, $protocol);
      return $values;
      //if ($this->isPageInCache($url) && $this->isPageUpdateLessOneOur($url)) {
      //  return $this->requestValuesFromCache($url);
      //}
      //else {
      //  $values = $this->requestValuesFromServer($url);
      //  $this->updateAndFlushCache($url, $values);
      //  return $values;
      //}
    }

    /**
     * Request the values from the redis server \n
     * The function needs the url of the page and the project_token of the user to request the values \n
     * Also needs the api_url \n
     * TODO: If request fails, must post \n
     *
     * @param {String} $url The url of the page
     * @return {Array} The values from the server
     */
    public function requestValuesFromServer($url='', $protocol='', $noCurl=false) {
      if($this->values == null) {
        $this->values = array();
        $request_url = $this->settings['api_url'] . 'values'
          . '?token=' . $this->settings['project_token']
          . '&url=' . $url;

        $timeout = $this->settings['api_timeout'];
        try {
          // makes sur the connection is closed when file_get_contents is done
          // downloading the values
          if(!function_exists('curl_version') || $noCurl) {
            $json_data = $this->fileGetContentsWithTimeout($request_url, $timeout);
          } else {
            $curl_session = curl_init($request_url);
            curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_session, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($curl_session, CURLOPT_ENCODING, 'gzip');
            $json_data = curl_exec($curl_session);
            curl_close($curl_session);
          }
          $vals = json_decode($json_data, true);
        }
        catch (Exception $e) {
          $vals = array();
        }

        if($vals
          && is_array($vals)
          && !(isset($vals['code']) && $vals['code'] !== 200) // no error
          && isset($vals['text_vals']) && isset($vals['img_vals'])
        ) {
          $this->values = $vals;
        }
      }

      return $this->values;
    }

    /**
     * Does a file_get_contents on the api url passed in $request_url
     * The request will use a timeout of 1 second
     *
     * @param {String} $request_url The api url to request the data
     * @param {Integer} $timeout Timeout seconds for request
     * @return {String} a Json string containing the data
     */
    public function fileGetContentsWithTimeout($request_url, $timeout) {
      // Create context to set timeout of 10 seconds
      $context = stream_context_create(array(
        'http' => array(
          'timeout' => $timeout,
          'header' => 'Accept-Encoding: gzip\r\n'
          )
        )
      );

      $response = file_get_contents($request_url, false, $context);

      foreach($http_response_header as $c => $h)
      {
        if(stristr($h, 'content-encoding') and stristr($h, 'gzip'))
        {
          $response = gzinflate( substr($response,10,-8) );
        }
      }

      return $response;
    }

    /**
     * Performs a post request without waiting for an answer\n
     * The function needs the host, port and path where to make the request, and send data in dataArray\n
     *
     * @param {String} $host The host for the request
     * @param {Number} $port The port for the request
     * @param {String} $path The path for the request
     * @param {Array} $dataArray The data to send in POST, in array format
     * @return {Void} Doesn't return anything
     */
    public function postRequest($host, $port, $path, $dataArray) {
      $fp = fsockopen($host, $port, $errno, $errstr, 1);
      $content = http_build_query($dataArray);
      fwrite($fp, 'POST ' . $path . " HTTP/1.1\r\n");
      fwrite($fp, 'Host: ' . $host . "\r\n");
      fwrite($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
      fwrite($fp, 'Content-Length: ' . strlen($content) . "\r\n");
      fwrite($fp, "Connection: close\r\n");
      fwrite($fp, "\r\n");
      fwrite($fp, $content);
      fclose($fp);
    }

    /**
     * Check if the page has been updated in the last hour \n
     * The function reads the updated_pages array in the user settings and check if pages was updated \n
     *
     * @param {String} $url The url of the page
     * @return {Boolean} True if the page was updated, false if not
     */
    public function isPageUpdateLessOneOur($url='') {
      $updated_pages = $this->settings['updated_pages'];
      if (isset($updates_pages[$url])) {
        $page_last_update = (int) $updated_pages[$url];
        $time_now = (int) microtime(TRUE);
        if ($time_now - $page_last_update < 3600) {
          return true;
        }
      }
      return false;
    }

    /**
     * Check if the values of the page are stored in the cache \n
     * The cache file is called cache.ini and is in the /values dir \n
     *
     * @param {String} $url The url of the page
     * @return {Boolean} True is the cache contains this page values, false if not
     */
    public function isPageInCache($url='') {
      $file = self::$config_dir . '/values/cache.ini';
      if (file_exists($file)) {
        $allUrls = parse_ini_file($file);
        if (isset($allUrls[$url])) {
          return true;
        }
      }
      return false;
    }

    /**
     * Flush the cache and update it with new values \n
     * We do this to be sure we dont keep too old values in the cache \n
     *
     * @param {String} $url The url of the page
     * @param {Array} $values The new values to store in the cache
     * @return void
     */
    public function updateAndFlushCache($url, $values) {
      $file = self::$config_dir . '/values/cache.ini';
      if (file_exists($file)) {
        unlink($file);
      }

      $data = '; Cached values' . "\n";
      $data .= $url . ' = ' . $values . "\n";
      file_put_contents($file, $data);
    }

    /**
     * Request values of a page from the cache \n
     * The cache file is called cache.ini and is in the /values dir \n
     *
     * @param {String} $url The url of the page
     * @return {Array} The values of the corresponding $url
     */
    public function requestValuesFromCache($url='') {
      $file = self::$config_dir . '/values/cache.ini';
      if (file_exists($file)) {
        $allUrls = parse_ini_file($file);
        if (isset($allUrls[$url])) {
          return json_decode($allUrls[$url]);
        }
        else {
          return array();
        }
      }
    }

    public static function generateIniFileContent($vals) {
      $timer = microtime(TRUE);
      $vals['last_change'] = (string) $timer;
      $fileContents = '';
      $data = '';
      foreach($vals as $key => $val) {
        if (gettype($val) === 'array') {
          foreach($vals[$key] as $arrKey => $arrVal)
            $data .= $key . '[' . $arrKey . '] = ' . $arrVal . "\n";
        }
        else
          $data .= $key . ' = ' . $val . "\n";
      }
      return $data;
    }

    /**
     * Write a .ini file given a filename and an array of values to store \n
     *
     * @param {String} $filename The name of the file
     * @param {Array} $vals The values to store
     */
    public static function writeIniFile($fileName, $data) {
      if (!preg_match('/\.ini$/', $fileName))
        $fileName .= '.ini';

      $fileName = self::$config_dir . '/' . $fileName;
      if(file_exists($fileName)) {
        unlink($fileName);
      }
      if (file_put_contents($fileName, $data, LOCK_EX)) {
        return true;
      }
      else {
        return false;
      }
    }

    /**
     * Cache the values of one page \n
     * The cache file is called cache.ini and is in the /values dir \n
     * The cached values are stored as url = values (where values is json string) \n
     *
     * @param {String} $url The url of the page
     * @param {Array} $values The values of the page
     * @return void
     */
    public function cacheValues($url, $values) {
      $file = self::$config_dir . '/values/cache.ini';
      if (file_exists($file)) {
        $allUrlsValues = parse_ini_file($file);
        $allUrlsValues[$url] = json_encode($values);
        $data = '; Cached values' . "\n";
        foreach($allUrlsValues as $u => $v) {
          $data .= $u . ' = ' . $v . "\n";
        }
        unlink($file);
        file_put_contents($file, $data);
      }
    }
  }

  Store::$config_dir = dirname(dirname(dirname(dirname(__FILE__))));

