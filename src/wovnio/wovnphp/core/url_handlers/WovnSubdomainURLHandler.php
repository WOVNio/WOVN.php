<?php


namespace Wovnio\Wovnphp\Core\UrlHandler;

use Wovnio\Wovnphp\Core\WovnLangException;

class WovnSubdomainURLHandler extends WovnURLHandler
{
    public function __construct($original, $langDirectory, $options)
    {
        parent::__construct($original, $langDirectory, $options);
    }

    protected function detectLang()
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

    protected function toDefaultLang()
    {
        // TODO: Implement removeLang() method.
    }

    protected function getFullyQualifiedUrl()
    {
        // TODO: Implement getFullyQualifiedUrl() method.
    }

    protected function toTargetLang()
    {
        // TODO: Implement addLang() method.
    }
}
