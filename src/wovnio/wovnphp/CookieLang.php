<?php


namespace Wovnio\Wovnphp;


class CookieLang
{
    const COOKIE_LANG_NAME = 'wovn_selected_lang';
    const ONE_YEAR = 31536000;

    private $header;
    private $store;

    public function __construct($header, $store)
    {
        $this->header = $header;
        $this->store = $store;
    }

    public function getCookieLang()
    {
        $cookies = $this->header->getCookies();
        if (array_key_exists(CookieLang::COOKIE_LANG_NAME, $cookies)) {
            return $cookies[CookieLang::COOKIE_LANG_NAME];
        }
        return null;
    }

    public function shouldRedirect() {
        if (!$this->store->settings['use_cookie_lang']) {
            return false;
        }
        return self::getCookieLang() && ($this->header->lang() != self::getCookieLang()) && $this->header->lang() === $this->store->defaultLang();
    }
}
