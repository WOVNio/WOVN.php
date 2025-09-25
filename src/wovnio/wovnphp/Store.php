<?php
namespace Wovnio\Wovnphp;

require_once 'custom_domain/CustomDomainLangs.php';

use \Wovnio\Html\HtmlConverter;

/**
 * The Store class contains the user settings
 */
class Store
{
    public $settings;
    // FIXME: could be private (unused outside this scope???)
    public $configLoaded = false;
    private $customDomainLangs;

    /**
     * @param string $settingFileName
     * @return Store
     */
    public static function createFromFile($settingFileName)
    {
        if (substr($settingFileName, -5) === '.json') {
            $settingsFile = file_get_contents($settingFileName);
            $userSettings = json_decode($settingsFile, true);
            return new Store($userSettings);
        }

        if (file_exists($settingFileName)) {
            $userSettings = parse_ini_file($settingFileName, true);
        } else {
            Logger::get()->critical('WOVN Configuration not found: {filename}.', array('filename' => $settingFileName));
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

    /**
     *  Configuration validate
     *
     *  @return boolean
     */
    public function isValid()
    {
        return (
            $this->settings['project_token']
            && $this->settings['url_pattern_name']
            && $this->settings['default_lang']
            && $this->settings['supported_langs']
        );
    }

    private function defaultSettings()
    {
        return array(
            'project_token' => '',
            'url_pattern_name' => 'query',
            'lang_param_name' => 'wovn',
            'url_pattern_reg' => '((\?.*&)|\?)wovn=(?P<lang>[^&]+)(&|$)',
            'widget_url' => '//j.wovn.io/1',
            'api_url' => 'https://wovn.global.ssl.fastly.net',
            'api_timeout' => 1.0,
            'api_timeout_search_engine_bots' => 5,
            'default_lang' => 'ja',
            'encoding' => null,
            'supported_langs' => array('en', 'ja'),
            'custom_lang_aliases' => array(),
            'use_proxy' => true,
            'override_content_length' => false,
            'disable_api_request_for_default_lang' => true,
            'compress_api_requests' => true,
            'ignore_paths' => array(),
            'ignore_regex' => array(),
            'ignore_class' => array(),
            'no_index_langs' => array(),
            'no_hreflang_langs' => array(),
            'insert_hreflangs' => true,
            'hreflang_x_default_lang' => null,
            'translate_canonical_tag' => true,
            'site_prefix_path' => null,
            'custom_domain_langs' => array(),
            'preserve_relative_urls' => false,

            // HTTP proxy used for outbound WOVN requests
            'outbound_proxy_host' => null,
            'outbound_proxy_port' => null,

            // Set to true to check if intercepted file is an AMP file.
            // Because WOVN.php interception is explicit, in most cases AMP files
            // are not intercepted, so this option is false by default -- always
            // checking for AMP takes time we can spare.
            'check_amp' => false,

            // without knowing much about this feature, no one should use this.
            'save_memory_by_sending_wovn_ignore_content' => false,
            'enable_wovn_diagnostics' => false,
            'use_cookie_lang' => false,
            'debug_mode' => false
        );
    }

    /**
     * Updates the current settings of the user in the class \n
     *
     * @param array $updatedOptions The options to update in the settings
     * @return array The new settings of the user
     */
    private function updateSettings($updatedOptions)
    {
        $defaultSettings = $this->defaultSettings();

        $this->settings = array_merge($this->settings, $updatedOptions);

        // GETTING THE LANGUAGE AND SETTING IT AS CODE
        $this->settings['default_lang'] = Lang::getCode($this->settings['default_lang']);

        if (!empty($this->settings['site_prefix_path'])) {
            $this->settings['site_prefix_path'] = trim($this->settings['site_prefix_path'], '/');
        }

        if (!empty($this->settings['custom_domain_langs']) && is_array($this->settings['custom_domain_langs'])) {
            $this->customDomainLangs = new CustomDomainLangs($this->settings['custom_domain_langs'], $this->settings['default_lang']);
        }

        // getting the url pattern
        if ($this->settings['url_pattern_name'] === 'query') {
            $this->settings['url_pattern_reg'] = '((\?.*&)|\?)' . $this->settings['lang_param_name'] . '=(?P<lang>[^&]+)(&|$)';
        } elseif ($this->settings['url_pattern_name'] === 'subdomain') {
            $this->settings['url_pattern_reg'] = '^(?P<lang>[^.]+)\.';
        } elseif ($this->settings['url_pattern_name'] === 'custom_domain') {
            // not use regex
        } else {
            $this->settings['url_pattern_name'] = 'path';
            $prefix = empty($this->settings['site_prefix_path']) ? '' : str_replace('/', '\/', '/' . $this->settings['site_prefix_path']);
            $this->settings['url_pattern_reg'] = $prefix . '\/(?P<lang>[^\/.]+)(\/|\?|$)';
        }

        if (in_array($this->settings['encoding'], HtmlConverter::$supportedEncodings) == false) {
            Logger::get()->warning('Invalid encoding setting: {encoding}.', $this->settings);
            $this->settings['encoding'] = null;
        }

        if (!is_array($this->settings['custom_lang_aliases'])) {
            $this->settings['custom_lang_aliases'] = array();
        } else {
            if (isset($this->settings['supported_langs'])) {
                $this->settings['supported_langs'] = $this->convertLangListToOriginalCodes($this->settings['supported_langs']);
            }
            if (isset($this->settings['no_index_langs'])) {
                $this->settings['no_index_langs'] = $this->convertLangListToOriginalCodes($this->settings['no_index_langs']);
            }
            if (isset($this->settings['no_hreflang_langs'])) {
                $this->settings['no_hreflang_langs'] = $this->convertLangListToOriginalCodes($this->settings['no_hreflang_langs']);
            }
        }

        if (!is_array($this->settings['ignore_paths'])) {
            $this->settings['ignore_paths'] = array();
        }

        if (!is_array($this->settings['ignore_regex'])) {
            $this->settings['ignore_regex'] = array();
        }

        // Use default api_url property when user api_url property is empty.
        if ($this->settings['api_url'] === '') {
            $this->settings['api_url'] = $defaultSettings['api_url'];
        }

        // Use default timeout if not set
        if ($this->settings['api_timeout'] === '') {
            $this->settings['api_timeout'] = $defaultSettings['api_timeout'];
        }

        if ($this->settings['api_timeout_search_engine_bots'] === '') {
            $this->settings['api_timeout_search_engine_bots'] = $defaultSettings['api_timeout'];
        }

        // Configure WOVN logging
        Logger::set(new Logger($this->settings['project_token']));

        if (!empty($this->settings['logging'])) {
            if ($this->settings['logging']['destination'] == 'file') {
                Logger::get()->setLogFilePath($this->settings['logging']['path']);
            }
            if (!empty($this->settings['logging']['max_line_length'])) {
                Logger::get()->setMaxLogLineLength($this->settings['logging']['max_line_length']);
            }
            Logger::get()->setQuiet(false);
        }

        if (!is_bool($this->settings['insert_hreflangs'])) {
            $this->settings['insert_hreflangs'] = !!$this->settings['insert_hreflangs'];
        }

        if (isset($this->settings['hreflang_x_default_lang'])) {
            $this->settings['hreflang_x_default_lang'] = Lang::getCode($this->settings['hreflang_x_default_lang']);
        }

        if (!is_bool($this->settings['translate_canonical_tag'])) {
            $this->settings['translate_canonical_tag'] = !!$this->settings['translate_canonical_tag'];
        }

        if (!is_bool($this->settings['preserve_relative_urls'])) {
            $this->settings['preserve_relative_urls'] = !!$this->settings['preserve_relative_urls'];
        }

        $this->configLoaded = true;

        return $this->settings;
    }

    private function convertLangListToOriginalCodes($langListArray)
    {
        foreach ($langListArray as $index => $langCode) {
            $langListArray[$index] = $this->convertToOriginalCode($langCode);
        }
        return $langListArray;
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

    public function hasDefaultLangAlias()
    {
        $defaultLang = $this->defaultLang();
        return array_key_exists($defaultLang, $this->settings['custom_lang_aliases']) &&
            $this->settings['custom_lang_aliases'][$defaultLang];
    }

    public function defaultLang()
    {
        return $this->settings['default_lang'];
    }

    public function getCustomDomainLangs()
    {
        return $this->customDomainLangs;
    }

    public function getHreflangXDefaultLangOrDefault()
    {
        if (isset($this->settings['hreflang_x_default_lang'])) {
            return $this->settings['hreflang_x_default_lang'];
        } else {
            return $this->defaultLang();
        }
    }

    public function compressApiRequests()
    {
        return $this->settings['compress_api_requests'];
    }

    public function outboundProxyHost()
    {
        return isset($this->settings['outbound_proxy_host'])
            ? $this->settings['outbound_proxy_host']
            : null;
    }

    public function outboundProxyPort()
    {
        return isset($this->settings['outbound_proxy_port'])
            ? $this->settings['outbound_proxy_port']
            : null;
    }
}
