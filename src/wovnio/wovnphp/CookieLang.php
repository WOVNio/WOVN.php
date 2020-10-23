<?php


namespace Wovnio\Wovnphp;

class CookieLang
{
    const COOKIE_LANG_NAME = 'wovn_selected_lang';

    private $header;
    private $cookieLang;

    public function __construct($header, $cookies)
    {
        $this->header = $header;
        $this->loadCookieLang($cookies);
    }

    public function getCookieLang()
    {
        return $this->cookieLang;
    }

    private function loadCookieLang($cookies)
    {
        if (array_key_exists(CookieLang::COOKIE_LANG_NAME, $cookies)) {
            $this->cookieLang = $cookies[CookieLang::COOKIE_LANG_NAME];
        } else {
            $this->cookieLang = null;
        }
    }
}
