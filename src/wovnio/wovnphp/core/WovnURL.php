<?php


namespace Wovnio\Wovnphp\Core;


class WovnURL
{
    private $raw; // the URL, as-is
    private $original; // the original WovnURL object, unchanged.
    private $url; // the fully qualified URL, as-is, or reconstructed
    private $baseUrl; // the fully qualified URL, without language (in default language).
    private $lang; // the URL's language
    private $components; // the URL's components

    // Note: Always keep trailing slash

    public function __constructor($raw)
    {
        $this->raw = $raw;
    }

    public function convertToLang($language)
    {

    }

    public function toHtmlEntity()
    {

    }

    public function getNoQuery()
    {

    }

    private function removeLang()
    {

    }

    private function getFullyQualifiedUrl()
    {

    }

    private function removeLangByQuery()
    {

    }

    private function removeLangByPath()
    {

    }

    private function removeLangBySubdomain()
    {

    }

    private function addLangByQuery()
    {

    }

    private function addLangByPath()
    {

    }

    private function addLangBySubdomain()
    {

    }
}
