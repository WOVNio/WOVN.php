<?php
namespace Wovnio\Wovnphp;

require_once 'CustomDomainLang.php';

class CustomDomainLangs
{
    private $customDomainLangs;


    public function __construct($customDomainLangsSettingsArray)
    {
        $this->customDomainLangs = array();
        foreach ($customDomainLangsSettingsArray as $langUrl => $config) {
            $parsedUrl = parse_url($this->addProtocolIfNeeded($langUrl));
            if (is_array($config)) {
                $source = array_key_exists('source', $config) ? $config['source'] : null;
            } else {
                $config = array('lang' => $config);
                $source = null;
            }

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
     * @return string|string[]|null
     */
    public function computeSourceVirtualUrl($physicalUri, $lang, $defaultLang)
    {
        $currentLangDomainLang = $this->getSourceCustomDomainByLang($lang);
        if ($currentLangDomainLang->getSource()) {
            $defaultCustomDomainLang = $currentLangDomainLang->getSource();
        } else {
            $defaultCustomDomainLang = $this->getCustomDomainLangByLang($defaultLang);
        }
        return CustomDomainLangUrlHandler::changeToNewCustomDomainLang($physicalUri, $currentLangDomainLang, $defaultCustomDomainLang);
    }

    public function hasSource($langCode)
    {
        $customDomainLang = $this->getCustomDomainLangByLang($langCode);
        if ($customDomainLang === null) {
            return false;
        }
        return $customDomainLang->getSource() !== null;
    }

    // parse_url needs protocol to parse URL.
    private function addProtocolIfNeeded($url)
    {
        return preg_match("/https?:\/\//", $url, $matches) ? $url : 'http://' . $url;
    }
}
