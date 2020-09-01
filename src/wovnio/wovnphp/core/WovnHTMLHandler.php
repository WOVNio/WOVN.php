<?php


namespace Wovnio\Wovnphp\Core;


class WovnHTMLHandler
{
    private $raw; // The raw HTML, as-is
    private $request; // The WovnRequest object

    public function __constructor($rawHtml, $request) {
        $this->raw = $rawHtml;
        $this->request = $request;
    }

    public function getWovnized() {

    }

    public function getAPIPrepared() {

    }

    private function insertSnippet() {

    }

    private function insertHrefLang() {

    }

}
