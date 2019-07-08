<?php
namespace Wovnio\Wovnphp;

use \Wovnio\Wovnphp\Logger;
use \Wovnio\Html\HtmlConverter;

/**
 * The Store class contains the user settings
 */
class Store
{
    public $settings;
    // FIXME: could be private (unused outside this scope???)
    public $configLoaded = false;
    // FIXME: could remove (unused???)
    public static $configDir;
    // FIXME: could remove (unused???)
    public $returnValueOnErrorHandle = false;
    // FIXME: wovnphp remaining code???
    private $values = null;

    /**
     * @param string $settingFileName
     * @return Store
     */
    public static function createFromFile($settingFileName)
    {
        if (file_exists($settingFileName)) {
            $userSettings = parse_ini_file($settingFileName, true);
        } else {
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
    public function __construct($userSettings)
    {
        $this->settings = $this->defaultSettings();
        if ($userSettings) {
            $this->updateSettings($userSettings);
        }
    }

    private function defaultSettings()
    {
        return array(
            'project_token' => '',
            'url_pattern_name' => 'query',
            'lang_param_name' => 'wovn',
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
            'ignore_paths' => array(),
            'ignore_regex' => array(),
            'ignore_class' => array(),

            // Set to true to check if intercepted file is an AMP file.
            // Because WOVN.php interception is explicit, in most cases AMP files
            // are not intercepted, so this option is false by default -- always
            // checking for AMP takes time we can spare.
            'check_amp' => false,

            // without knowing much about this feature, no one should use this.
            'save_memory_by_sending_wovn_ignore_content' => false
        );
    }

    /**
     * Updates the current settings of the user in the class \n
     *
     * @param array $updatedOptions The options to update in the settings
     * @return array The new settings of the user
     */
    public function updateSettings($updatedOptions)
    {
        $defaultSettings = $this->defaultSettings();

        $this->settings = array_merge($this->settings, $updatedOptions);

        // GETTING THE LANGUAGE AND SETTING IT AS CODE
        $this->settings['default_lang'] = Lang::getCode($this->settings['default_lang']);

        // Gettting the query params array, adding = if missing and sorting
        if (!empty($this->settings['query'])) {
            foreach ($this->settings['query'] as $k => $q) {
                if (!preg_match('/=$/', $q)) {
                    $this->settings['query'][$k] = $q . '=';
                }
            }
            sort($this->settings['query'], SORT_STRING);
        }

        // getting the url pattern
        if ($this->settings['url_pattern_name'] === 'query') {
            $this->settings['url_pattern_reg'] = '((\?.*&)|\?)' . $this->settings['lang_param_name'] . '=(?P<lang>[^&]+)(&|$)';
        } elseif ($this->settings['url_pattern_name'] === 'subdomain') {
            $this->settings['url_pattern_reg'] = '^(?P<lang>[^.]+)\.';
        } else {
            $this->settings['url_pattern_name'] = 'path';
            $this->settings['url_pattern_reg'] = '\/(?P<lang>[^\/.]+)(\/|\?|$)';
        }

        if (in_array($this->settings['encoding'], HtmlConverter::$supportedEncodings) == false) {
            Logger::get()->warning('Invalid encoding setting: {encoding}.', $this->settings);
            $this->settings['encoding'] = null;
        }

        if (!is_array($this->settings['custom_lang_aliases'])) {
            $this->settings['custom_lang_aliases'] = array();
        } else {
            if (isset($this->settings['supported_langs'])) {
                $this->ensureValidSupportedLanguages();
            }
        }

        if (!is_array($this->settings['ignore_paths'])) {
            $this->settings['ignore_paths'] = array();
        }

        if (!is_array($this->settings['ignore_regex'])) {
            $this->settings['ignore_regex'] = array();
        }

        // update settings if wovn dev mode is activated
        if ($this->isWovnDevModeActivated() && (!array_key_exists('api_url', $this->settings) || $this->settings['api_url'] === $defaultSettings['api_url'])) {
            $this->settings['api_url'] = $this->wovnProtocol() . '://api.' . $this->wovnHost() . '/v0/';
        }

        // Use default api_url property when user api_url property is empty.
        if ($this->settings['api_url'] === '') {
            $this->settings['api_url'] = $defaultSettings['api_url'];
        }

        // Use default timeout if not set
        if ($this->settings['api_timeout'] === '') {
            $this->settings['api_timeout'] = $defaultSettings['api_timeout'];
        }

        $this->configLoaded = true;

        return $this->settings;
    }

    private function ensureValidSupportedLanguages()
    {
        foreach ($this->settings['supported_langs'] as $index => $langCode) {
            $this->settings['supported_langs'][$index] = $this->convertToOriginalCode($langCode);
        }
    }

    private function isWovnDevModeActivated($settings = null)
    {
        if ($settings === null) {
            $settings = $this->settings;
        }

        return array_key_exists('wovn_dev_mode', $settings) && $settings['wovn_dev_mode'];
    }

    public function wovnProtocol($settings = null)
    {
        if ($settings === null) {
            $settings = $this->settings;
        }

        return ($this->isWovnDevModeActivated($settings)) ? 'http' : 'https';
    }

    public function wovnHost($settings = null)
    {
        if ($settings === null) {
            $settings = $this->settings;
        }

        return ($this->isWovnDevModeActivated($settings)) ? 'dev-wovn.io:3000' : 'wovn.io';
    }

    public function convertToCustomLangCode($lang_code)
    {
        if (isset($this->settings['custom_lang_aliases'][$lang_code])) {
            return $this->settings['custom_lang_aliases'][$lang_code];
        }

        return $lang_code;
    }

    public function convertToOriginalCode($lang_code)
    {
        foreach ($this->settings['custom_lang_aliases'] as $lang => $custom_lang) {
            if ($lang_code == $custom_lang) {
                return $lang;
            }
        }

        return $lang_code;
    }

    public function defaultLangAlias()
    {
        $defaultLang = $this->defaultLang();
        return array_key_exists($defaultLang, $this->settings['custom_lang_aliases']) &&
            $this->settings['custom_lang_aliases'][$defaultLang];
    }

    public function defaultLang()
    {
        return $this->settings['default_lang'];
    }
}
