<?php


namespace Wovnio\Wovnphp;

class CustomDomainLangSource extends CustomDomainLang
{
    public function __construct($url, $lang)
    {
        $url = preg_match("/https?:\/\//", $url, $matches) ? $url : 'http://' . $url;
        $parsedUrl = parse_url($url);
        if (isset($parsedUrl['port'])) {
            $parsedUrl['host'] = $parsedUrl['host'] . ':' . $parsedUrl['port'];
        }
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';
        parent::__construct($parsedUrl['host'], $path, $lang);
    }
}
