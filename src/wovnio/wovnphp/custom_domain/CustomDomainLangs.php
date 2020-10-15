<?php
namespace Wovnio\Wovnphp;

use PHP_CodeSniffer\Tests\Standards\AllSniffs;

require_once 'CustomDomainLang.php';

class CustomDomainLangs
{
    private $customDomainLangs;


    public function __construct($customDomainLangsSettingsArray, $defaultLang)
    {
        $defaultLangCustomDomain = CustomDomainLangs::getDefaultLangCustomDomain($customDomainLangsSettingsArray, $defaultLang);
        $this->customDomainLangs = array();
        foreach ($customDomainLangsSettingsArray as $langUrl => $config) {
            $parsedUrl = parse_url($this->addProtocolIfNeeded($langUrl));
            if (is_array($config)) {
                $source = array_key_exists('source', $config) ? $config['source'] : $defaultLangCustomDomain;
            } else {
                $config = array('lang' => $config);
                $source = $defaultLangCustomDomain;
            }

            // Disable notice error by adding @, when path is not defined
            $this->customDomainLangs[$config['lang']] = new CustomDomainLang($parsedUrl['host'], @$parsedUrl['path'], $config['lang'], $source);
        }
    }

    public function getSourceCustomDomainByLang($langCode)
    {
        $customDomainLang = $this->getCustomDomainLangByLang($langCode);
        if ($customDomainLang === null) {
            return null;
        } else {
            return $customDomainLang->getSource();
        }
    }

    public function getCustomDomainLangByLang($langCode)
    {
        if (array_key_exists($langCode, $this->customDomainLangs)) {
            return $this->customDomainLangs[$langCode];
        } else {
            return null;
        }
    }

    public function getCustomDomainLangByUrl($url)
    {
        $sortedCustomDomainLangs = array_values($this->customDomainLangs);
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

    public function toHtmlSwapperHash()
    {
        $result = array();
        foreach ($this->customDomainLangs as $lang) {
            $result[$lang->getHostAndPathWithoutTrailingSlash()] = $lang->getLang();
        }
        return $result;
    }

    /**
     * Returns the computed (virtual) uri representation for a given physical location uri.
     * Used when communicating with html-swapper.
     *
     * @param $physicalUri string the current uri - pointing to physical location of current lang
     * @param $lang string lang code of the current uri
     * @param $defaultLang string lang code of the default (source) language
     * @return string|null
     */
    public function computeSourceVirtualUrl($physicalUri, $lang, $defaultLang)
    {
        $currentLangDomainLang = $this->getSourceCustomDomainByLang($lang);
        $defaultCustomDomainLang = $this->getCustomDomainLangByLang($defaultLang);
        return CustomDomainLangUrlHandler::changeToNewCustomDomainLang($physicalUri, $currentLangDomainLang, $defaultCustomDomainLang);
    }

    // parse_url needs protocol to parse URL.
    private function addProtocolIfNeeded($url)
    {
        return preg_match("/https?:\/\//", $url, $matches) ? $url : 'http://' . $url;
    }

    private static function getDefaultLangCustomDomain($customDomainLangsSettingsArray, $defaultLang)
    {
        foreach ($customDomainLangsSettingsArray as $langUrl => $config) {
            if (is_array($config)) {
                $source = array_key_exists('source', $config) ? $config['source'] : $langUrl;
                $lang = $config['lang'];
            } else {
                $lang = $config;
                $source = $langUrl;
            }

            if ($lang === $defaultLang) {
                return $source;
            }
        }
        return null;
    }
}
