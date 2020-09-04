<?php


namespace Wovnio\Wovnphp\Core\UrlHandler;

use Wovnio\Wovnphp\Core\WovnLangException;
use Wovnio\Wovnphp\Core\WovnOption;

require_once(__DIR__ . '/WovnURLHandler.php');

class WovnPathURLHandler extends WovnURLHandler
{
    public function __construct($original, $langDirectory, $options)
    {
        parent::__construct($original, $langDirectory, $options);
    }

    protected function detectLang()
    {
        $path = strval($this->components['path']);
        $prefix = strval($this->options->get(WovnOption::OPT_SITE_PREFIX_PATH)) . '/';
        $match = strpos($path, $prefix);
        if ($match !== false) {
            $path = substr($path, $match + strlen($prefix));
        }
        $exploded  = explode('/', $path);
        if (count($exploded) === 1) {
            return $this->langDirectory->defaultLang();
        }
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
