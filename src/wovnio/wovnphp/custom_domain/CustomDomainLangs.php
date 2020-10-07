<?php
namespace Wovnio\Wovnphp;

use PHP_CodeSniffer\Tests\Standards\AllSniffs;

require_once 'CustomDomainLang.php';

class CustomDomainLangs
{
    private $customDomainLangs;

    public function __construct($customDomainLangsSettingsArray)
    {
        $this->customDomainLangs = array();
        foreach ($customDomainLangsSettingsArray as $langUrl => $config) {
            $parsedUrl = parse_url($this->addProtocolIfNeeded($langUrl));
            $source = array_key_exists('source', $config) ? $config['source'] : null;
            // Disable notice error by adding @, when path is not defined
            array_push($this->customDomainLangs, new CustomDomainLang($parsedUrl['host'], @$parsedUrl['path'], $config['lang'], $source));
        }
    }

    public function getSourceCustomDomainByLang($langCode)
    {
        $results = array_filter($this->customDomainLangs, function ($customDomain) use ($langCode) {
            return $customDomain->getLang() === $langCode;
        });

        if (count($results) <= 0) {
            return null;
        } else {
            $result = array_shift($results);
            return $result->getSource() ? $result->getSource() : $result;
        }
    }

    public function getCustomDomainLangByLang($langCode)
    {
        $results = array_filter($this->customDomainLangs, function ($customDomain) use ($langCode) {
            return $customDomain->getLang() === $langCode;
        });
        return count($results) <= 0 ? null : array_shift($results);
    }

    public function getCustomDomainLangByUrl($url)
    {
        $sortedCustomDomainLangs = $this->customDomainLangs;
        // "/" path will naturally match every URL, so by comparing longest paths first we will get the best match
        usort($sortedCustomDomainLangs, function ($left, $right) {
            return strlen($left->getPath()) <= strlen($right->getPath());
        });
        $parsedUrl = parse_url($this->addProtocolIfNeeded($url));
        if ($parsedUrl && !array_key_exists('path', $parsedUrl)) {
            $parsedUrl['path'] = '/';
        }

        $results = array_filter($sortedCustomDomainLangs, function ($customDomain) use ($parsedUrl) {
            return $customDomain->isMatch($parsedUrl);
        });
        return count($results) <= 0 ? null : array_shift($results);
    }

    // parse_url needs protocol to parse URL.
    private function addProtocolIfNeeded($url)
    {
        return preg_match("/https?:\/\//", $url, $matches) ? $url : 'http://' . $url;
    }
}
