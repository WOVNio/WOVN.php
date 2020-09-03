<?php


namespace Wovnio\Wovnphp\Core;

use Wovnio\Wovnphp\Core\UrlHandler\WovnPathURLHandler;
use Wovnio\Wovnphp\Core\UrlHandler\WovnQueryURLHandler;
use Wovnio\Wovnphp\Core\UrlHandler\WovnSubdomainURLHandler;

class WovnURL
{
    private $urlHandler;

    // Note: Always keep trailing slash

    public function __construct($original, $langDirectory, $options)
    {
        $this->urlHandler = $this->getUrlHandler($original, $langDirectory, $options);
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
        return $this->urlHandler->lang();
    }

    private function getUrlHandler($original, $langDirectory, $options)
    {
        switch ($options->get(WovnOption::OPT_URL_PATTERN_NAME)) {
            case 'path':
                return new WovnPathURLHandler($original, $langDirectory, $options);
            case 'query':
                return new WovnQueryURLHandler($original, $langDirectory, $options);
            case 'subdomain':
                return new WovnSubdomainURLHandler($original, $langDirectory, $options);
            default:
                return null;
        }
    }
}
