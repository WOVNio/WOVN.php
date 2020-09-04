<?php


namespace Wovnio\Wovnphp\Core;

use Wovnio\Wovnphp\Logger;

class WovnOption
{
    // TODO: PHP doesn't have support for enums, but it would be nice to have a enum type here.
    const STRING_TYPE = 1;
    const BOOLEAN_TYPE = 2;
    const ARRAY_TYPE = 3;
    const ASSOCIATIVE_ARRAY_TYPE = 4;
    const INTEGER_TYPE = 5;

    const OPT_PROJECT_TOKEN = 'project_token';
    const OPT_DEFAULT_LANG = 'default_lang';
    const OPT_SUPPORTED_LANGS = 'supported_langs';
    const OPT_URL_PATTERN_NAME = 'url_pattern_name';
    const OPT_LANG_PARAM_NAME = 'lang_param_name';
    const OPT_CUSTOM_LANG_ALIASES = 'custom_lang_aliases';
    const OPT_KEPT_QUERY = 'query';
    const OPT_IGNORE_PATHS = 'ignore_paths';
    const OPT_IGNORE_REGEXES = 'ignore_regex';
    const OPT_IGNORE_CLASSES = 'ignore_class';
    const OPT_NO_INDEX_LANGS = 'no_index_langs';
    const OPT_ENCODING = 'encoding';
    const OPT_API_TIMEOUT = 'api_timeout';
    const OPT_DISABLE_API_REQUEST_FOR_DEFAULT_LANG = 'disable_api_request_for_default_lang';
    const OPT_USE_PROXY = 'use_proxy';
    const OPT_OVERRIDE_CONTENT_LENGTH = 'override_content_length';
    const OPT_BYPASS_AMP = 'check_amp';
    const OPT_SITE_PREFIX_PATH = 'site_prefix_path';
    const OPT_ENABLE_WOVN_DIAGNOSTICS = 'enable_wovn_diagnostics';
    const OPT_WOVN_DIAGNOSTICS_USERNAME = 'wovn_diagnostics_username';
    const OPT_WOVN_DIAGNOSTICS_PASSWORD = 'wovn_diagnostics_password';

    public static $OPTIONS = array(
        self::OPT_PROJECT_TOKEN => array('name' => 'project_token', 'dependency' => array(), 'required' => true, 'type' => self::STRING_TYPE, 'default' => null),
        self::OPT_DEFAULT_LANG => array('name' => 'default_lang', 'dependency' => array(), 'required' => true, 'type' => self::STRING_TYPE, 'default' => null),
        self::OPT_SUPPORTED_LANGS => array('name' => 'supported_langs', 'dependency' => array(), 'required' => true, 'type' => self::ARRAY_TYPE, 'default' => null),
        self::OPT_URL_PATTERN_NAME => array('name' => 'url_pattern_name', 'dependency' => array(), 'required' => false, 'type' => self::STRING_TYPE, 'default' => 'query'),
        self::OPT_LANG_PARAM_NAME => array('name' => 'lang_param_name', 'dependency' => array('url_pattern_name' => 'query'), 'required' => false, 'type' => self::STRING_TYPE, 'default' => 'wovn'),
        self::OPT_CUSTOM_LANG_ALIASES => array('name' => 'custom_lang_aliases', 'dependency' => array(), 'required' => false, 'type' => self::ASSOCIATIVE_ARRAY_TYPE, 'default' => array()),
        self::OPT_KEPT_QUERY => array('name' => 'query', 'dependency' => array(), 'required' => false, 'type' => self::ARRAY_TYPE, 'default' => null),
        self::OPT_IGNORE_PATHS => array('name' => 'ignore_paths', 'dependency' => array(), 'required' => false, 'type' => self::ARRAY_TYPE, 'default' => null),
        self::OPT_IGNORE_REGEXES => array('name' => 'ignore_regex', 'dependency' => array(), 'required' => false, 'type' => self::ARRAY_TYPE, 'default' => null),
        self::OPT_IGNORE_CLASSES => array('name' => 'ignore_class', 'dependency' => array(), 'required' => false, 'type' => self::ARRAY_TYPE, 'default' => null),
        self::OPT_NO_INDEX_LANGS => array('name' => 'no_index_langs', 'dependency' => array(), 'required' => false, 'type' => self::ARRAY_TYPE, 'default' => null),
        self::OPT_ENCODING => array('name' => 'encoding', 'dependency' => array(), 'required' => false, 'type' => self::STRING_TYPE, 'default' => null),
        self::OPT_API_TIMEOUT => array('name' => 'api_timeout', 'dependency' => array(), 'required' => false, 'type' => self::INTEGER_TYPE, 'default' => 1),
        self::OPT_DISABLE_API_REQUEST_FOR_DEFAULT_LANG => array('name' => 'disable_api_request_for_default_lang', 'dependency' => array(), 'required' => false, 'type' => self::BOOLEAN_TYPE, 'default' => false),
        self::OPT_USE_PROXY => array('name' => 'use_proxy', 'dependency' => array(), 'required' => false, 'type' => self::BOOLEAN_TYPE, 'default' => false),
        self::OPT_OVERRIDE_CONTENT_LENGTH => array('name' => 'override_content_length', 'dependency' => array(), 'required' => false, 'type' => self::BOOLEAN_TYPE, 'default' => false),
        self::OPT_BYPASS_AMP => array('name' => 'check_amp', 'dependency' => array(), 'required' => false, 'type' => self::BOOLEAN_TYPE, 'default' => false),
        self::OPT_SITE_PREFIX_PATH => array('name' => 'site_prefix_path', 'dependency' => array('url_pattern_name' => 'path'), 'required' => false, 'type' => self::STRING_TYPE, 'default' => ''),
        self::OPT_ENABLE_WOVN_DIAGNOSTICS => array('name' => 'enable_wovn_diagnostics', 'dependency' => array(), 'required' => false, 'type' => self::BOOLEAN_TYPE, 'default' => false),
        self::OPT_WOVN_DIAGNOSTICS_PASSWORD => array('name' => 'wovn_diagnostics_password', 'dependency' => array(), 'required' => false, 'type' => self::STRING_TYPE, 'default' => null),
        self::OPT_WOVN_DIAGNOSTICS_USERNAME => array('name' => 'wovn_diagnostics_username', 'dependency' => array(), 'required' => false, 'type' => self::STRING_TYPE, 'default' => null)
    );

    private $options;

    /**
     * WovnOption constructor.
     * @param $configurations array An associative array of configurations.
     * @throws WovnConfigurationException Thrown when a required option is not set correctly.
     */
    public function __construct($configurations)
    {
        $this->options = array();
        $this->loadOptions($configurations);
    }

    /**
     * @param $name string The option name.
     * @return mixed The option value.
     * @throws WovnConfigurationException Thrown when $name is an invalid name.
     */
    public function get($name)
    {
        if (array_key_exists($name, self::$OPTIONS)) {
            return $this->options[$name];
        }
        throw new WovnConfigurationException("Invalid option name: {$name}");
    }

    public function getMD5Hash()
    {
    }

    /**
     * Loads and initialize options
     *
     * @param $configurations
     * @throws WovnConfigurationException
     */
    private function loadOptions($configurations)
    {
        foreach ($configurations as $name => $value) {
            if (!array_key_exists($name, self::$OPTIONS)) {
                // Invalid configuration names are ignored silently.
                continue;
            }

            try {
                $validatedValue = $this->validateOption($name, $value);
            } catch (WovnConfigurationException $e) {
                Logger::get()->error("Invalid option: {$e->getMessage()}");
                continue;
            }
            $this->options[$name] = $validatedValue;
        }
        $this->checkDependency();
        $this->loadDefaults();
        $this->checkRequired();
    }

    /**
     * Loads default values for options if not set by user.
     */
    private function loadDefaults()
    {
        foreach (self::$OPTIONS as $name => $option) {
            if ($option['default'] !== null) {
                if (!array_key_exists($name, $this->options)) {
                    $this->options[$name] = $option['default'];
                }
            }
        }
    }

    /**
     * Checks required options.
     *
     * @throws WovnConfigurationException Thrown when a required option is not set.
     */
    private function checkRequired()
    {
        foreach (self::$OPTIONS as $name => $option) {
            if ($option['required'] === true) {
                if (!array_key_exists($name, $this->options)) {
                    throw new WovnConfigurationException("Option {$name} is required by WOVN.php core.");
                }
            }
        }
    }

    /**
     * Checks if dependency options are set.
     *
     * @throws WovnConfigurationException Thrown when a dependency option is not set.
     */
    private function checkDependency()
    {
        foreach ($this->options as $name => $value) {
            foreach (self::$OPTIONS[$name]['dependency'] as $dependency => $dependencyValue) {
                if (!array_key_exists($dependency, $this->options)) {
                    throw new WovnConfigurationException("Option {$name} requires dependency option {$dependency} to be set!");
                } else {
                    if ($dependencyValue !== $this->options[$dependency]) {
                        throw new WovnConfigurationException("Option {$name} requires dependency option {$dependency} to be set to {$dependencyValue}!");
                    }
                }
            }
        }
    }

    /**
     * @param $name string Name of the option.
     * @param $value array|bool|int|string Value of the option.
     * @return array|bool|int|string
     * @throws WovnConfigurationException Thrown when the type of $value is incorrect.
     */
    private function validateOption($name, $value)
    {
        switch (self::$OPTIONS[$name]['type']) {
            case self::INTEGER_TYPE:
                $newValue = intval($value, 10);
                if ($newValue === 0) {
                    throw new WovnConfigurationException("Invalid value {$value} for option {$name}, an integer is required.");
                }
                return $newValue;
            case self::STRING_TYPE:
                return strval($value);
            case self::BOOLEAN_TYPE:
                return (bool)$value;
            case self::ARRAY_TYPE:
                if (is_array($value)) {
                        return $value;
                }
                throw new WovnConfigurationException("Invalid value {$value} for option {$name}, an array is required.");
            case self::ASSOCIATIVE_ARRAY_TYPE:
                if (is_array($value)) {
                        return $value;
                }
                throw new WovnConfigurationException("Invalid value {$value} for option {$name}, an associative array is required.");
        }
        return $value;
    }
}
