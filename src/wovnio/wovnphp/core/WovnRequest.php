<?php


namespace Wovnio\Wovnphp\Core;


class WovnRequest
{
    private $_scheme; // e.g. HTTP
    private $_host; // e.g. 20.18.166.31, news.network.com, news.com
    private $_port; // e.g. 80, 8080, 443, 3001
    private $_path; // e.g. /books/bestseller/1.html, /
    private $_url; // The fully qualified request URL, as-is, e.g. https://www.new.com/breaking/top.html?start=1&wovn=en
    private $_wovnUrl; // The WovnURL object
    private $_query; // The request's query, as an associative array
    private $_lang; // The request's target language code

    public function __constructor($serverSuperGlobal)
    {

    }

    /**
     * Swaps the HTML either locally or via API, depending on the situation
     */
    public function swap() {

    }

    /**
     * Sends out the HTTP response.
     */
    public function sendResponse() {

    }

    public function wovnUrl() {
        return $this->_wovnUrl;
    }

    public function lang() {
        return $this->_lang;
    }

    private function shouldUseAPITranslation()
    {
        // return $headers->lang() != $store->settings['default_lang'] || !$store->settings['disable_api_request_for_default_lang'];
    }

}
