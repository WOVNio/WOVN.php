<?php


namespace Wovnio\Wovnphp\Core\UrlHandler;

use Wovnio\Wovnphp\Core\WovnLangException;
use Wovnio\Wovnphp\Core\WovnOption;

require_once(__DIR__ . '/WovnURLHandler.php');

class WovnQueryURLHandler extends WovnURLHandler
{
    public function __construct($original, $langDirectory, $options)
    {
        parent::__construct($original, $langDirectory, $options);
    }

    protected function detectLang()
    {
        $queries = null;

        if (isset($this->components['query'])) {
            parse_str(strval($this->components['query']), $queries);
        }

        if (isset($queries[$this->options->get(WovnOption::OPT_LANG_PARAM_NAME)])) {
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
