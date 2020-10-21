<?php


namespace Wovnio\Wovnphp;


class CookieLang
{
    const COOKIE_LANG_NAME = 'wovn_selected_lang';

    private $header;
    private $store;

    public function __construct($header, $store)
    {
        $this->header = $header;
        $this->store = $store;
        $this->setCookieIfNeeded();
    }

    public function setLangCookie($langCode)
    {
        if (!array_key_exists($langCode, Lang::$index)) {
            return;
        }

        setcookie(CookieLang::COOKIE_LANG_NAME, $langCode, time()+60*60*24*30, '/', $this->header->host, true);
    }

    public static function getCookieLang()
    {
        if (array_key_exists(CookieLang::COOKIE_LANG_NAME, $_COOKIE)) {
            return $_COOKIE[CookieLang::COOKIE_LANG_NAME];
        }
        return null;
    }

    public function shouldRedirect() {
        return self::getCookieLang() && ($this->header->lang() != self::getCookieLang());
    }

    public function computeRedirectUrl()
    {
        $url = $this->header->urlKeepTrailingSlash;
        if ($this->store->hasDefaultLangAlias()) {
            $url = $this->header->removeLang($url, $this->store->defaultLang());
            $url = Url::addLangCode($url, $this->store, self::getCookieLang(), $this->header);
        } elseif (self::getCookieLang() !== $this->store->defaultLang() || $this->store->settings['url_pattern_name'] === 'custom_domain') {
            $url = Url::addLangCode($url, $this->store, self::getCookieLang(), $this->header);
        }
        return htmlentities($url);
    }

    private function setCookieIfNeeded()
    {
        if (self::getCookieLang()) {
            return;
        }
        self::setLangCookie($this->header->computePathLang());
    }
}
