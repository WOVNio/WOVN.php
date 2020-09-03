<?php


namespace Wovnio\Wovnphp\Core;


class WovnURL
{
    private $original; // The original fully qualified URL, as-is, or reconstructed.
    private $components; // The original URL's components, as-is, or reconstructed.
    private $baseUrl; // The fully qualified URL, without language (in default language).
    private $baseComponents; // The fully qualified URL's components, without language (in default language).
    private $_lang; // The URL's original language.
    private $defaultLang; // The webpage's default language.
    private $pattern; // The webpage's wovn url pattern name.
    private $langDirectory; // The WovnLangDirectory object
    private $options; // The WovnOption object

    // Note: Always keep trailing slash

    public function __construct($original, $langDirectory, $options)
    {
        $this->original = $original;
        $this->defaultLang = $options->get(WovnOption::OPT_DEFAULT_LANG);
        $this->pattern = $options->get(WovnOption::OPT_URL_PATTERN_NAME);
        $this->langDirectory = $langDirectory;
        $this->components = parse_url($original);
        $this->options = $options;
        $this->baseUrl = null;
        $this->baseComponents = null;
        $this->components = null;
        $this->_lang = $this->detectLang();
    }

    public function convertToLang($language)
    {

    }

    public function toHtmlEntity()
    {

    }

    public function getNoQuery()
    {

    }

    public function lang()
    {
        return $this->_lang;
    }

    private function detectLang()
    {
        switch ($this->pattern) {
            case 'path':
                return $this->detectLangPath();
            case 'query':
                return $this->detectLangQuery();
            case 'subdomain':
                return $this->detectLangSubdomain();
        }
        return $this->langDirectory->defaultLang();
    }

    private function detectLangPath()
    {
        $path = strval($this->components['path']);
        $prefix = '/' . strval($this->options->get(WovnOption::OPT_SITE_PREFIX_PATH));
        if ($prefix) {
            $path = preg_replace("@$prefix(/|$)@", '', $prefix, 1);
        }
        $exploded  = explode('/', $path);
        $langCandidate = $exploded[0];
        try {
            $lang = $this->langDirectory->getLang($langCandidate);
            if (!$lang->isValidLang()) {
                $lang = $this->langDirectory->defaultLang();
            }
        } catch (WovnLangException $e) {
            $lang = $this->langDirectory->defaultLang();
        }
        return $lang;
    }

    private function detectLangQuery()
    {
        parse_str(strval($this->components['query']), $queries);
        if (isset($queries[$this->pattern])) {
            try {
                $lang = $this->langDirectory->getLang($queries[$this->options->get(WovnOption::OPT_LANG_PARAM_NAME)]);
                if (!$lang->isValidLang()) {
                    $lang = $this->langDirectory->defaultLang();
                }
            } catch (WovnLangException $e) {
                $lang = $this->langDirectory->defaultLang();
            }
        } else {
            $lang = $this->langDirectory->defaultLang();
        }
        return $lang;
    }

    private function detectLangSubdomain()
    {
        // if the first segment before . matches a lang code or lang alias, it is the language.
        // otherwise, the link is in the default language.
        $exploded = explode('.', strval($this->components['host']));
        $langCandidate = $exploded[0];
        try {
            $lang = $this->langDirectory->getLang($langCandidate);
            if (!$lang->isValidLang()) {
                $lang = $this->langDirectory->defaultLang();
            }
        } catch (WovnLangException $e) {
            $lang = $this->langDirectory->defaultLang();
        }
        return $lang;
    }

    private function removeLang()
    {

    }

    private function getFullyQualifiedUrl()
    {

    }

    private function removeLangByQuery()
    {

    }

    private function removeLangByPath()
    {

    }

    private function removeLangBySubdomain()
    {

    }

    private function addLangByQuery()
    {

    }

    private function addLangByPath()
    {

    }

    private function addLangBySubdomain()
    {

    }
}
