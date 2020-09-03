<?php


namespace Wovnio\Wovnphp\Core\UrlHandler;


use Wovnio\Wovnphp\Core\WovnLangException;
use Wovnio\Wovnphp\Core\WovnOption;

class WovnPathURLHandler extends WovnURLHandler
{
    public function __construct($original, $langDirectory, $options)
    {
        parent::__construct($original, $langDirectory, $options);
    }

    protected function detectLang()
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

    protected function removeLang()
    {
        // TODO: Implement removeLang() method.
    }

    protected function getFullyQualifiedUrl()
    {
        // TODO: Implement getFullyQualifiedUrl() method.
    }

    protected function addLang()
    {
        // TODO: Implement addLang() method.
    }
}
