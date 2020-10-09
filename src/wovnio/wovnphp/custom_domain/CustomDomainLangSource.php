<?php


namespace Wovnio\Wovnphp;

class CustomDomainLangSource extends CustomDomainLang
{
    public function __construct($url, $lang)
    {
        $url = preg_match("/https?:\/\//", $url, $matches) ? $url : 'http://' . $url;
        $parsedUrl = parse_url($url);
        parent::__construct($parsedUrl['host'], $parsedUrl['path'], $lang);
    }
}
