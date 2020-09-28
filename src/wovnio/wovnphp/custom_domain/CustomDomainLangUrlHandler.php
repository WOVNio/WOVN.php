<?php
namespace Wovnio\Wovnphp;

class CustomDomainLanguageUrlHandler
{
    // TODO: convert to php
    public static function AddCustomDomainLanguageToAbsoluteUrl(Uri url, string lang, CustomDomainLanguagesModel customDomainLanguages)
    {
        var currentCustomDomain = customDomainLanguages.GetCustomDomainLanguage(url);
        var newCustomDomain = customDomainLanguages.GetCustomDomainLanguage(lang);
        if (currentCustomDomain != null && newCustomDomain != null && currentCustomDomain.Language != newCustomDomain.Language)
        {
            return ChangeToNewCustomDomainLanguage(url, currentCustomDomain, newCustomDomain);
        }

        return url;
    }

    public static function ChangeToNewCustomDomainLanguage(Uri uri, CustomDomainLanguage currentLanguage, CustomDomainLanguage newLanguage)
    {
        string newAuthority = uri.Authority.Replace(currentLanguage.Domain, newLanguage.Domain);
        string currentPath = uri.AbsolutePath;
        Regex matchCurrentCustomPath = new Regex($@"^{currentLanguage.Path.TrimEnd('/')}(/|$)");

        string newPath = matchCurrentCustomPath.Replace(currentPath, newLanguage.Path.TrimEnd('/') + "$1");
        return new Uri($"{uri.Scheme}://{newAuthority}{newPath}{uri.Query}");
    }
}