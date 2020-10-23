<?php


namespace Wovnio\Wovnphp;

class CookieLang
{
    const COOKIE_LANG_NAME = 'wovn_selected_lang';

    private $header;

    public function __construct($header, $store)
    {
        $this->header = $header;
    }

    public function getCookieLang()
    {
        $cookies = $this->header->getCookies();
        if (array_key_exists(CookieLang::COOKIE_LANG_NAME, $cookies)) {
            return $cookies[CookieLang::COOKIE_LANG_NAME];
        }
        return null;
    }
}
