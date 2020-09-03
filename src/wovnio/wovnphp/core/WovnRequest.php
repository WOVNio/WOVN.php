<?php


namespace Wovnio\Wovnphp\Core;

class WovnRequest
{
    private $_scheme; // e.g. HTTP
    private $_host; // e.g. 20.18.166.31, news.network.com, news.com
    private $_path; // e.g. /books/bestseller/1.html, /
    private $_url; // The fully qualified request URL, as-is, e.g. https://www.new.com/breaking/top.html?start=1&wovn=en
    private $_wovnUrl; // The WovnURL object
    private $_query; // The request's query, as an associative array
    private $_lang; // The request's target language code

    private $langDirectory;
    private $options;

    public function __constructor($serverSuperGlobal, $optionConfig)
    {
        $this->options = new WovnOption($optionConfig); // Do Not handle exceptions here, let them fail.
        $this->_scheme = $this->parseScheme($serverSuperGlobal);
        $this->_host = $serverSuperGlobal['HTTP_HOST'];
        $this->_path = $this->parseURI($serverSuperGlobal);
        $this->_query = isset($serverSuperGlobal['QUERY_STRING']) ? $serverSuperGlobal['QUERY_STRING'] : '';
        $this->_url = $this->_scheme . '://' . $this->_host . $this->_path . $this->_query;
        $this->_lang = $this->detectLang();
        $this->langDirectory = new WovnLangDirectory(
            $this->options->get(WovnOption::OPT_SUPPORTED_LANGS),
            $this->options->get(WovnOption::OPT_DEFAULT_LANG),
            $this->options->get(WovnOption::OPT_CUSTOM_LANG_ALIASES)
        );
        $this->_wovnUrl = new WovnURL($this->_url, $this->langDirectory, $this->options);
        $this->_lang = $this->_wovnUrl->lang();
    }

    /**
     * Swaps the HTML either locally or via API, depending on the situation
     */
    public function swap()
    {
    }

    public function wovnUrl()
    {
        return $this->_wovnUrl;
    }

    public function lang()
    {
        return $this->_lang;
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
            return $serverSuperGlobal['HTTP_X_FORWARDED_REQUEST_URI'];
        }
        if (!isset($serverSuperGlobal['REQUEST_URI'])) {
            return $serverSuperGlobal['PATH_INFO'] . (strlen($serverSuperGlobal['QUERY_STRING']) === 0 ? '' : '?' . $serverSuperGlobal['QUERY_STRING']);
        } else {
            return $serverSuperGlobal['REQUEST_URI'];
        }
    }

    private function shouldUseAPITranslation()
    {
        // return $headers->lang() != $store->settings['default_lang'] || !$store->settings['disable_api_request_for_default_lang'];
    }

    private function updateHeaderContentLength()
    {
    }
}
