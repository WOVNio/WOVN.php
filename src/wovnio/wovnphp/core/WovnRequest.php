<?php


namespace Wovnio\Wovnphp\Core;

require_once(__DIR__ . '/WovnLangDirectory.php');
require_once(__DIR__ . '/WovnLang.php');
require_once(__DIR__ . '/WovnOption.php');
require_once(__DIR__ . '/WovnURL.php');


class WovnRequest
{
    private $scheme; // e.g. HTTP
    private $host; // e.g. 20.18.166.31, news.network.com, news.com
    private $path; // e.g. /books/bestseller/1.html, /
    private $url; // The fully qualified request URL, as-is, e.g. https://www.new.com/breaking/top.html?start=1&wovn=en
    private $wovnUrl; // The WovnURL object
    private $query; // The request's query, as an associative array
    private $lang; // The request's target language code

    private $langDirectory;
    private $options;

    public function __construct($serverSuperGlobal, $optionConfig, $useStrictMode = true)
    {
        $this->options = new WovnOption($optionConfig, $useStrictMode); // Do Not handle exceptions here, let them fail.
        $this->scheme = $this->parseScheme($serverSuperGlobal);
        $this->host = $serverSuperGlobal['HTTP_HOST'];
        $this->path = $this->parseURI($serverSuperGlobal);
        $this->query = isset($serverSuperGlobal['QUERY_STRING']) ? $serverSuperGlobal['QUERY_STRING'] : '';
        $this->url = $this->scheme . '://' . $this->host . $this->path;
        if ($this->query) {
            $this->url = $this->url . '?' . $this->query;
        }
        $this->langDirectory = new WovnLangDirectory(
            $this->options->get(WovnOption::OPT_SUPPORTED_LANGS),
            $this->options->get(WovnOption::OPT_DEFAULT_LANG),
            $this->options->get(WovnOption::OPT_CUSTOM_LANG_ALIASES)
        );
        $this->wovnUrl = new WovnURL($this->url, $this->langDirectory, $this->options);
        $this->lang = $this->wovnUrl->lang();
    }

    /**
     * Translates the HTML either locally or via API, depending on the situation
     */
    public function translate()
    {
    }

    public function wovnUrl()
    {
        return $this->wovnUrl;
    }

    public function lang()
    {
        return $this->lang;
    }

    private function parseScheme($serverSuperGlobal)
    {
        if ($this->options->get(WovnOption::OPT_USE_PROXY) && isset($env['HTTP_X_FORWARDED_PROTO'])) {
            return $serverSuperGlobal['HTTP_X_FORWARDED_PROTO'];
        } else {
            if (isset($serverSuperGlobal['HTTPS']) && !empty($serverSuperGlobal['HTTPS']) && $serverSuperGlobal['HTTPS'] !== 'off') {
                return 'https';
            } else {
                return 'http';
            }
        }
    }

    private function parseURI($serverSuperGlobal)
    {
        if ($this->options->get(WovnOption::OPT_USE_PROXY) && isset($serverSuperGlobal['HTTP_X_FORWARDED_REQUEST_URI'])) {
            $components = explode('?', $serverSuperGlobal['HTTP_X_FORWARDED_REQUEST_URI']);
            return $components[0];
        }

        return $serverSuperGlobal['PATH_INFO'];
    }

    private function shouldUseAPITranslation()
    {
        // return $headers->lang() != $store->settings['default_lang'] || !$store->settings['disable_api_request_for_default_lang'];
    }

    private function updateHeaderContentLength()
    {
    }
}
