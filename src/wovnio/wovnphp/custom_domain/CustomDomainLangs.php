<?php
namespace Wovnio\Wovnphp;

class CustomDomainLanguages
{
    public function __construct($customDomainLangsSettingsArray)
    {
        $this->$custom_domain_langs = array();
        foreach (customDomainLangsSettingsArray as $lang_url => $lang) {
            $parsed_url = parse_url($lang_url);
            $this->$custom_domain_langs->array_push(new CustomDomainLanguage($parsed_url['host'], $parsed_url['path'], $lang));
        }
    }

    public function get_custom_domain_lang_by_lang($langCode) {
        // TODO: convert to php
        return $this->$custom_domain_langs.FirstOrDefault(c => c.Language == langCode);
    }

    public function get_custom_domain_lang_by_url($langCode) {
        // TODO: convert to php
            // "/" path will naturally match every URL, so by comparing longest paths first we will get the best match
            return CustomDomainLanguages
                .OrderByDescending(c => c.Path.Length)
                .FirstOrDefault(c => c.IsMatch(url));
    }
}

class CustomDomainLanguage {
    public function __construct($domain, $path, $language)
    {
        $this->$domain = $domain;
        $this->$path = $path.ends_with('/') ? $path : $path . '/'; // TODO: convert to php
        $this->$language = $language;
    }

    public function is_match($parsed_url) {
        // convert to php
        return url.Host.Equals(Domain, StringComparison.OrdinalIgnoreCase)
        && PathIsEqualOrSubsetOf(Path, url.AbsolutePath);
    }

    private function path_is_equal_or_subset_of($path1, $path2)
    {
        // TODO convert to php
        string[] path1Segments = path1.Split(new[] { '/' }, StringSplitOptions.RemoveEmptyEntries);
        string[] path2Segments = path2.Split(new[] { '/' }, StringSplitOptions.RemoveEmptyEntries);

        return path1Segments.SequenceEqual(path2Segments.Take(path1Segments.Length));
    }
}
