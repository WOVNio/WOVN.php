<?php
namespace Wovnio\Wovnphp;

class Utils
{
    const IMAGE_FILE_PATTERN = "/^(https?:\/\/)?.*(\.((?!jp$)jpe?g?|bmp|gif|png|btif|tiff?|psd|djvu?|xif|wbmp|webp|p(n|b|g|p)m|rgb|tga|x(b|p)m|xwd|pic|ico|fh(c|4|5|7)?|xif|f(bs|px|st)))/i";
    const AUDIO_FILE_PATTERN = "/^(https?:\/\/)?.*(\.(mp(3|2)|m(p?2|3|p?4|pg)a|midi?|kar|rmi|web(m|a)|aif(f?|c)|w(ma|av|ax)|m(ka|3u)|sil|s3m|og(a|g)|uvv?a))/i";
    const VIDEO_FILE_PATTERN = "/^(https?:\/\/)?.*(\.(m(x|4)u|fl(i|v)|3g(p|2)|jp(gv|g?m)|mp(4v?|g4|e?g)|m(1|2)v|ogv|m(ov|ng)|qt|uvv?(h|m|p|s|v)|dvb|mk(v|3d|s)|f4v|as(x|f)|w(m(v|x)|vx)))/i";
    const DOC_FILE_PATTERN = "/^(https?:\/\/)?.*(\.((7|g)?zip|tar|rar|7z|gz|ez|aw|atom(cat|svc)?|(cc)?xa?ml|cdmi(a|c|d|o|q)?|epub|g(ml|px|xf)|jar|js|ser|class|json(ml)?|do(c|t)(m|x)?|xls(m|x)?|xps|pp(a|tx?|s)m?|potm?|sldm|mp(p|t)|bin|dms|lrf|mar|so|dist|distz|m?pkg|bpk|dump|rtf|tfi|pdf|pgp|apk|o(t|d)(b|c|ft?|g|h|i|p|s|t)))/i";

    // will return the store and headers objects
    public static function getStoreAndHeaders(&$env)
    {
        $file = DIRNAME(__FILE__) . '/../../../../wovn.ini';
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

    public static function isHtml($response_headers, $buffer)
    {
        // Because Apache sets header automatically when Content-Type is not set,
        // check the header only when it exists.
        foreach ($response_headers as $response_header) {
            $header_data = explode(':', $response_header);
            if (count($header_data) < 2) {
                continue;
            }
            if (strtolower(trim($header_data[0])) == 'content-type') {
                if (!preg_match('/html/', $header_data[1])) {
                    return false;
                }
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
     * Return true if $uri is an image, audio, video, or some doc filepath.
     * Return false otherwise.
     */
    public static function isFilePathURI($uri, $store)
    {
        if (!$uri) {
            return false;
        }

        $uri = self::removeQueryAndHash($uri);

        return (
            preg_match(self::IMAGE_FILE_PATTERN, $uri) ||
            preg_match(self::AUDIO_FILE_PATTERN, $uri) ||
            preg_match(self::VIDEO_FILE_PATTERN, $uri) ||
            preg_match(self::DOC_FILE_PATTERN, $uri)
        );
    }

    /*
     * Return true if $uri should be ignored according to `ignore_paths` or `ignore_regex`.
     * Return false otherwise.
     */
    public static function isIgnoredPath($uri, $store)
    {
        return $uri && (
            self::checkIgnorePaths($uri, $store) ||
            self::checkIgnoreRegex($uri, $store)
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

    private static function getEnv($env, $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $env)) {
                return $env[$key];
            }
        }
        return '';
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
