<?php

namespace Wovnio\Wovnphp\Core\UrlHandler;

use Wovnio\Wovnphp\Core\WovnLangDirectory;
use Wovnio\Wovnphp\Core\WovnOption;
use Wovnio\Wovnphp\Core\WovnURL;

abstract class WovnURLHandler
{
    protected $original; // The original fully qualified URL, as-is, or reconstructed.
    protected $components; // The original URL's components, as-is, or reconstructed.
    protected $baseUrl; // The fully qualified URL, without language (in default language).
    protected $baseComponents; // The fully qualified URL's components, without language (in default language).
    protected $_lang; // The URL's original language.
    protected $defaultLang; // The webpage's default language.
    protected $pattern; // The webpage's wovn url pattern name.
    protected $langDirectory; // The WovnLangDirectory object
    protected $options; // The WovnOption object

    public function __construct($original, $langDirectory, $options)
    {
        $this->original = $original;
        $this->defaultLang = $options->get(WovnOption::OPT_DEFAULT_LANG);
        $this->pattern = $options->get(WovnOption::OPT_URL_PATTERN_NAME);
        $this->langDirectory = $langDirectory;
        $this->components = parse_url($original);
        $this->addDefaultComponentValues();
        $this->options = $options;
        $this->baseUrl = null;
        $this->baseComponents = null;
        $this->_lang = $this->detectLang();
    }

    public function lang()
    {
        return $this->_lang;
    }

    private function addDefaultComponentValues()
    {
        if (!array_key_exists('path', $this->components)) {
            $this->components['path'] = '/';
        }
    }

    abstract protected function detectLang();
    abstract protected function removeLang();
    abstract protected function getFullyQualifiedUrl();
    abstract protected function addLang();
}
