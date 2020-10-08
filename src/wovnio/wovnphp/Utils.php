<?php
namespace Wovnio\Wovnphp;

use finfo;

class Utils
{
    // will return the store and headers objects
    public static function getStoreAndHeaders(&$env)
    {
        $file = isset($env['WOVN_CONFIG']) ? $env['WOVN_CONFIG'] : DIRNAME(__FILE__) . '/../../../../wovn.ini';
        $store = Store::createFromFile($file);
        $headers = new Headers($env, $store);
        return array($store, $headers);
    }

    /**
     * @param $parentPath [String]
     * @param $childPath [String]
     * @return [String]
     * @example
     *  joinPath('/hello/', '/world/') #=> '/hello/world/'
     */
    public static function joinPath($parentPath, $childPath)
    {
        return preg_replace('/\/+/', '/', $parentPath . '/'. $childPath);
    }

    public static function changeHeaders($buffer, $store)
    {
        if ($store->settings['override_content_length']) {
            $buffer_length = strlen($buffer);
            //header cannot get at phpunit, so this code doesn't have any test..
            header('Content-Length: '.$buffer_length);
        }
    }

    public static function isHtml($buffer)
    {
        $finfo = new finfo(FILEINFO_MIME);
        $contentType = $finfo->buffer($buffer);

        if ($contentType) {
            if (preg_match('/html|php/', strtolower($contentType))) {
                return true;
            } elseif (preg_match('/text/', strtolower($contentType))) {
                return $buffer != strip_tags($buffer);
            } else {
                return false;
            }
        }
        return $buffer != strip_tags($buffer);
    }

    public static function isAmp($buffer)
    {
        if (preg_match('/<html\s[^>]*(amp|\x{26a1})/siu', $buffer) === 1) {
            // remove comments to avoid looking at commented html tags (regex must be non-greedy)
            $uncommentedHtml = preg_replace('/<!--.*?-->/s', '', $buffer);
            preg_match_all('/<html(?P<args>[^>]*)>/si', $uncommentedHtml, $htmlTags);
            $htmlMatchCount = count($htmlTags['args']);

            if ($htmlMatchCount > 0) {
                return preg_match('/(^|\s)(amp|\x{26a1})(=|\s|$)/u', $htmlTags['args'][0]) === 1;
            }
        }

        return false;
    }

    /*
     * Return true if $uri should be ignored according to `ignore_paths` or `ignore_regex`.
     * Return false otherwise.
     */
    public static function isIgnoredPath($uriWithQuery, $store)
    {
        return $uriWithQuery && (
            self::checkIgnorePaths($uriWithQuery, $store) ||
            self::checkIgnoreRegex($uriWithQuery, $store) ||
            Url::shouldIgnoreBySitePrefixPath($uriWithQuery, $store->settings)
        );
    }

    /*
     * Return true if $uri path matches one or more values in `ignore_paths`, false otherwise.
     * An `ignore_path` is only matched at the beginning of the path, and it is always interpreted
     * as if starting with a leading slash and end with a trailing slash.
     * I.e. `global`, `/global`, `global/, and `/global/` will all have the same result.
     */
    private static function checkIgnorePaths($uri, $store = null)
    {
        if (null === $store || !isset($store->settings['ignore_paths']) || isset($store->settings['ignore_paths']) && empty($store->settings['ignore_paths'])) {
            return false;
        }

        $path = self::getPath($uri);
        foreach ($store->settings['ignore_paths'] as $ignored_path) {
            // make sure ignored path has leading slash and not trailing slash
            $ignored_path = "/" . trim($ignored_path, "/");
            $ignored_path_trailing_slash = $ignored_path . "/";

            // ignore URI if path matches an ignored path exactly (i.e. a filename like "/img/dog.png")
            $ignore_path_is_exact_match = strcasecmp($path, $ignored_path) === 0;
            // ignore URI if its path starts with the ignored path with trailing slash (i.e. directory like "/global/images/")
            $uri_starts_with_ignore_path = strcasecmp(substr($path, 0, strlen($ignored_path_trailing_slash)), $ignored_path_trailing_slash) === 0;

            if ($ignore_path_is_exact_match || $uri_starts_with_ignore_path) {
                return true;
            }
        }
        return false;
    }

    /*
     * Return true if $uri path matches one or more regexes in `ignore_regex`, false otherwise.
     */
    private static function checkIgnoreRegex($uri, $store = null)
    {
        if (null === $store || !isset($store->settings['ignore_regex']) || isset($store->settings['ignore_regex']) && empty($store->settings['ignore_regex'])) {
            return false;
        }

        foreach ($store->settings['ignore_regex'] as $glob) {
            if (preg_match($glob, $uri)) {
                return true;
            }
        }
        return false;
    }

    public static function wovnDiagnosticsEnabled($store, $header)
    {
        if (strpos($header->url, 'wovn_diagnostics=1') == false) {
            return false;
        }
        if (null === $store || !isset($store->settings['enable_wovn_diagnostics'])) {
            return false;
        }
        if ($store->settings['enable_wovn_diagnostics']) {
            return true;
        }
        return false;
    }
    /*
     * Return path component of $uri
     */
    private static function getPath($uri)
    {
        // strip schema
        $uri_path = preg_replace("/^(https?:\/\/)?/", "", $uri);
        // strip host
        $uri_path = preg_replace("/^[^\/]*/", "", $uri_path);

        $uri_path = self::removeQueryAndHash($uri_path);

        return $uri_path;
    }

    private static function removeQueryAndHash($uri)
    {
        return preg_replace("/[\#\?].*/", "", $uri);
    }
}
