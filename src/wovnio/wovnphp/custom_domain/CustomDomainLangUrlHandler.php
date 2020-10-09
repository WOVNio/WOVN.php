<?php
namespace Wovnio\Wovnphp;

class CustomDomainLangUrlHandler
{
    public static function addCustomDomainLangToAbsoluteUrl($absoluteUrl, $targetlang, $customDomainLangs)
    {
        $currentCustomDomain = $customDomainLangs->getCustomDomainLangByUrl($absoluteUrl);
        $newLangCustomDomain = $customDomainLangs->getCustomDomainLangByLang($targetlang);
        return self::changeToNewCustomDomainLang($absoluteUrl, $currentCustomDomain, $newLangCustomDomain);
    }

    public static function changeToNewCustomDomainLang($absoluteUrl, $currentCustomDomain, $newLangCustomDomain)
    {
        if (!empty($currentCustomDomain) && !empty($newLangCustomDomain)) {
            $currentHostAndPath = $currentCustomDomain->getHostAndPathWithoutTrailingSlash();
            $newHostAndPath = $newLangCustomDomain->getHostAndPathWithoutTrailingSlash();
            $regex = '@'.
                '^(.*://|//)?'. // 1. schema
                "(${currentHostAndPath})". // 2. host and path
                '((?:/|\?|#).*)?$' . // 3: other
                '@';

            return preg_replace($regex, "$1${newHostAndPath}$3", $absoluteUrl);
        }
        return $absoluteUrl;
    }
}
