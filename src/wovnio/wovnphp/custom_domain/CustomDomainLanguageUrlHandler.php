<?php
namespace Wovnio\Wovnphp;

// require_once 'CustomDomainLangs.php';

class CustomDomainLanguageUrlHandler
{
    public static function addCustomDomainLanguageToAbsoluteUrl($url, $targetlang, $customDomainLangs)
    {
        $currentCustomDomain = $customDomainLangs->getCustomDomainLangByUrl($url);
        $newLangCustomDomain = $customDomainLangs->getCustomDomainLangByLang($targetlang);
        $changedUrl = self::changeToNewCustomDomainLang($url, $currentCustomDomain, $newLangCustomDomain);
        return $changedUrl;
    }

    public static function changeToNewCustomDomainLang($uri, $currentCustomDomain, $newLangCustomDomain)
    {
        if (!empty($currentCustomDomain) && !empty($newLangCustomDomain) && $currentCustomDomain->getLang() !== $newLangCustomDomain->getLang()) {
            $currentHostAndPath = $currentCustomDomain->getHostAndPathWithoutTrailingSlash();
            $newHostAndPath = $newLangCustomDomain->getHostAndPathWithoutTrailingSlash();
            $regex = '@'.
                '^(.*://|//)?'. // 1. schema
                "(${currentHostAndPath})". // 2. host and path
                '(/|\?|#|$)' . // 3: path, query, hash or end-of-string like /dir2/?a=b#hash
                '@';

            return  preg_replace($regex, "$1${newHostAndPath}$3", $uri);
        }
        return $uri;
    }
}
