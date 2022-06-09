<?php
namespace Wovnio\Wovnphp;

require_once 'custom_domain/CustomDomainLangUrlHandler.php';

class Url
{
    /**
     * Escapes a text to be used inside of a regular expression.
     *
     * @param String $text The text to escape.
     *
     * @return String The text escaped to be used inside a regular expression.
     */
    private static function formatForRegExp($text)
    {
        return str_replace('$', '\$', str_replace("\\", "\\\\", $text));
    }

    /**
     * Adds a language code to a uri, converts to custom lang code if necessary.
     * Only urls from the same host as the request are processed.
     *
     * @param String  $uri     The uri to modify.
     * @param Store $store     The Store object.
     * @param String  $lang    The language code to add to the uri.
     * @param Headers $headers The headers.
     *
     * @return String The new uri containing the language code.
     */
    public static function addLangCode($uri, $store, $lang, $headers)
    {
        if (!$lang || strlen($lang) == 0 || self::isAnchorLink($uri)) {
            return $uri;
        }

        $new_uri = $uri;
        $pattern = $store->settings['url_pattern_name'];
        $site_prefix_path = $store->settings['site_prefix_path'];
        $lang_code = $store->convertToCustomLangCode($lang);
        $lang_param_name = $store->settings['lang_param_name'];
        $default_lang = $store->settings['default_lang'];

        if (Utils::isIgnoredPath($uri, $store)) {
            return $uri;
        }

        $no_lang_uri = self::removeLangCode($uri, $lang_code, $store, $headers);
        $no_lang_host = self::removeLangCode($headers->host, $lang_code, $store, $headers);

        if ($store->hasDefaultLangAlias()) {
            $no_lang_uri = self::removeLangCode($no_lang_uri, $store->convertToCustomLangCode($default_lang), $store, $headers);
            $no_lang_host = self::removeLangCode($no_lang_host, $store->convertToCustomLangCode($default_lang), $store, $headers);
        }

        // absolute urls
        if (self::isAbsoluteUri($no_lang_uri)) {
            $parsed_url = parse_url($no_lang_uri);
            // only continue if the host of the url is the same as the headers host
            if (!self::uriFromSameHost($no_lang_uri, $no_lang_host)) {
                return $new_uri;
            }
            switch ($pattern) {
                case 'subdomain':
                    $new_uri = self::addSubdomainLangCode($parsed_url, $lang_code, $no_lang_uri);
                    break;
                case 'query':
                    $new_uri = self::addQueryLangCode($no_lang_uri, $lang_code, $lang_param_name);
                    break;
                case 'path':
                    $new_uri = self::addPathLangCode($no_lang_uri, $lang_code, $site_prefix_path);
                    break;
                case 'custom_domain':
                    $new_uri = CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl($no_lang_uri, $lang_code, $store->getCustomDomainLangs());
                    break;
                default:
                    $new_uri = $uri;
            }
        } else {
            // relative urls

            if (preg_match('/:/', $no_lang_uri)) {
                // do nothing for protocols other than http and https (e.g. tel)
                return $new_uri;
            }

            switch ($pattern) {
                case 'subdomain':
                    $lang_url = $headers->protocol . '://' . strtolower($lang_code) . '.' . $headers->host;
                    $current_dir = preg_replace('/[^\/]*\.[^\.]{2,6}$/', '', $headers->pathname, 1);
                    if (preg_match('/^\.\..*$/', $no_lang_uri)) {
                        // ../path
                        $new_uri = $lang_url . '/' . preg_replace('/^\.\.\//', '', $no_lang_uri);
                    } elseif (preg_match('/^\..*$/', $no_lang_uri)) {
                        // ./path
                        $new_uri = $lang_url . $current_dir . preg_replace('/^\.\//', '', $no_lang_uri);
                    } elseif (preg_match('/^\/.*$/', $no_lang_uri)) {
                        // /path
                        $new_uri = $lang_url . $no_lang_uri;
                    } else {
                        $new_uri = $lang_url . $current_dir . $no_lang_uri;
                    }
                    break;
                case 'query':
                    $new_uri = self::addQueryLangCode($no_lang_uri, $lang_code, $lang_param_name);
                    break;
                case 'custom_domain':
                    if (self::isAbsolutePath($no_lang_uri)) { // absolute path
                        $absoluteUrl = $headers->protocol . '://' . $headers->host . $no_lang_uri;
                        $absoluteUrlWithLang = CustomDomainLangUrlHandler::addCustomDomainLangToAbsoluteUrl($absoluteUrl, $lang_code, $store->getCustomDomainLangs());
                        $segments = self::makeSegmentsFromAbsoluteUrl($absoluteUrlWithLang);
                        $new_uri = $segments['others'];
                    }
                    break;
                default: // path
                    if (preg_match('/^\//', $no_lang_uri)) {
                        $new_uri = self::addPathLangCode($no_lang_uri, $lang_code, $site_prefix_path);
                    } else {
                        $current_dir = preg_replace('/[^\/]*\.[^\.]{2,6}$/', '', $headers->pathname, 1);
                        $new_uri = self::addPathLangCode($current_dir . $no_lang_uri, $lang_code, $site_prefix_path);
                    }
            }
        }
        return $new_uri;
    }

    /**
     * @param $no_lang_uri String URI without lang code
     * @param $no_lang_host String Host without lang code
     * @return bool if the URI is from the same host
     */
    private static function uriFromSameHost($no_lang_uri, $no_lang_host)
    {
        $parsed_url = parse_url($no_lang_uri);
        // On seriously malformed URLs, parse_url() may return FALSE. (php doc)
        if (!$parsed_url) {
            return false;
        }

        $parsed_host = array_key_exists('host', $parsed_url) ? $parsed_url['host'] : null;
        if ($parsed_host !== null && array_key_exists('port', $parsed_url)) {
            $parsed_host = $parsed_host . ':' . $parsed_url['port'];
        }
        return $parsed_host !== null && strtolower($parsed_host) === strtolower($no_lang_host);
    }

    /**
     * Adds a language code to a uri using query pattern.
     *
     * @param String  $uri     The uri to modify.
     * @param String  $lang_code    The language code to add to the uri.
     *
     * @return String The new uri containing the language code.
     */
    private static function addQueryLangCode($uri, $lang_code, $lang_param_name)
    {
        $sep = '?';
        if (preg_match('/\?/', $uri)) {
            $sep = '&';
        }
        return preg_replace('/(#|$)/', $sep . $lang_param_name . '=' . $lang_code . '${1}', $uri, 1);
    }

    /**
     * Adds a lang code to a URL with no lang code, using path pattern.
     * Supports both absolute and relative paths.
     * @param $no_lang_url
     * @param $lang
     * @param string $site_prefix_path
     * @return string|string[]|null
     */
    private static function addPathLangCode($no_lang_url, $lang, $site_prefix_path = '')
    {
        if (empty($lang)) {
            return $no_lang_url;
        }
        return preg_replace(
            self::generateUrlRegex($site_prefix_path),
            "$1$2$3/$lang$4",
            $no_lang_url
        );
    }

    /**
     * Adds lang code to a URI following the subdomain pattern.
     * No custom lang alias conversion happens here.
     *
     * @param $parsed_url array parse_url output array
     * @param $lang_code String language code
     * @param $no_lang_uri String URI without language code
     * @return string|string[]|null
     */
    private static function addSubdomainLangCode($parsed_url, $lang_code, $no_lang_uri)
    {
        // check if subdomain already exists
        if (preg_match('/\./', $parsed_url['host'])) {
            $explodedSubDomain = explode('.', $parsed_url['host']);
            $subDomain = $explodedSubDomain[0];
            $sub_do_lang = Lang::getCode($subDomain);
            if ($sub_do_lang && strtolower($sub_do_lang) === strtolower($lang_code)) {
                $new_uri = preg_replace('/' . $lang_code . '/i', strtolower($lang_code), $no_lang_uri, 1);
            } else {
                $new_uri = preg_replace('/(\/\/)([^\.]*)/', '${1}' . self::formatForRegExp(strtolower($lang_code)) . '.' . '${2}', $no_lang_uri, 1);
            }
        } else {
            $new_uri = preg_replace('/(\/\/)([^\.]*)/', '${1}' . self::formatForRegExp(strtolower($lang_code)) . '.' . '${2}', $no_lang_uri, 1);
        }
        return $new_uri;
    }

    /**
     * Removing the lang of the url, literally.
     * No lang code to custom lang alias conversion happens here.
     *
     * @param String $uri The url with the lang
     * @param String $lang_code The literal lang code to remove
     * @param String $settings The settings object
     * @return String The url without the lang
     */
    public static function removeLangCode($uri, $lang_code, $store, $headers)
    {
        if (!$lang_code || strlen($lang_code) == 0 || self::isAnchorLink($uri)) {
            return $uri;
        }

        $settings = $store->settings;
        $pattern = $settings['url_pattern_name'];
        $lang_param_name = $settings['lang_param_name'];
        $site_prefix_path = $settings['site_prefix_path'];

        switch ($pattern) {
            case 'query':
                return preg_replace('/(\?|&)$/', '', preg_replace('/(^|\?|&)' . $lang_param_name . '=' . $lang_code . '(&|$)/i', '\1', $uri));
            case 'subdomain':
                // limit to one replacement
                return preg_replace('/(\/\/|^)' . $lang_code . '\./i', '\1', $uri, 1);
            case 'path':
                // limit to one replacement
                $prefix = empty($site_prefix_path) ? '' : '/' . $site_prefix_path;
                return preg_replace("@$prefix/$lang_code(/|$)@i", "$prefix/", $uri, 1);
            case 'custom_domain':
                $customDomainLangs = $store->getCustomDomainLangs();
                $customDomainLangToRemove = $customDomainLangs->getCustomDomainLangByLang($lang_code);
                $defaultCustomDomainLang = $customDomainLangToRemove->getSource();
                $newUri = $uri;
                if (self::isAbsoluteUri($uri)) {
                    $newUri = CustomDomainLangUrlHandler::changeToNewCustomDomainLang($uri, $customDomainLangToRemove, $defaultCustomDomainLang);
                } elseif (self::isAbsolutePath($uri)) {
                    $absoluteUrl = $headers->protocol . '://' . $headers->originalHost . $uri;
                    $absoluteUrlWithLang = CustomDomainLangUrlHandler::changeToNewCustomDomainLang($absoluteUrl, $customDomainLangToRemove, $defaultCustomDomainLang);
                    $segments = self::makeSegmentsFromAbsoluteUrl($absoluteUrlWithLang);
                    $newUri = $segments['others'];
                } elseif ($uri === $customDomainLangToRemove->getHost()) {
                    $absoluteUrl = $headers->protocol . '://' . $uri . $headers->originalPath;
                    $absoluteUrlWithLang = CustomDomainLangUrlHandler::changeToNewCustomDomainLang($absoluteUrl, $customDomainLangToRemove, $defaultCustomDomainLang);
                    $segments = self::makeSegmentsFromAbsoluteUrl($absoluteUrlWithLang);
                    $newUri = $segments['host'];
                }
                return $newUri;
            default:
                return $uri;
        }
    }

    public static function shouldIgnoreBySitePrefixPath($uri, $settings)
    {
        if ($settings['site_prefix_path'] && $settings['url_pattern_name'] === 'path'
        ) {
            return !preg_match(self::generateUrlRegex($settings['site_prefix_path']), $uri);
        }
        return false;
    }

    private static function isAnchorLink($uri)
    {
        return preg_match('/^(#.*)?$/', $uri);
    }

    private static function generateUrlRegex($site_prefix_path = '')
    {
        $prefix = $site_prefix_path ? '/' . $site_prefix_path : '';
        return (
            '@' .
            '^(.*://|//)?' . // 1: schema (optional) like https://
            '([^/?]*)?' . // 2: host (optional) like wovn.io
            "($prefix)" . // 3: site prefix path like /dir1
            '(/|\?|#|$)' . // 4: path, query, hash or end-of-string like /dir2/?a=b#hash
            '@'
        );
    }

    private static function isAbsoluteUri($uri)
    {
        return preg_match('/^(https?:)?\/\//i', $uri);
    }

    private static function isAbsolutePath($uri)
    {
        return preg_match('/^\//', $uri);
    }

    // parameters are not assumed to be absolute URLs
    // Fills missing components using request headers
    public static function isSameHostAndPath($a, $b, $headers)
    {
        // This doesn't have to be a perfect conversion of a URL,
        // we just need to make them parsable so we can compare
        if (!Url::isAbsoluteUri($a)) {
            $a = $headers->protocol . '://' . $headers->originalHost . $a;
        }

        if (!Url::isAbsoluteUri($b)) {
            $b = $headers->protocol . '://' . $headers->originalHost . $b;
        }

        $parsed_url_a = parse_url($a);
        $parsed_url_b = parse_url($b);

        if ($parsed_url_a && $parsed_url_b) {
            $path_a = isset($parsed_url_a['path']) ? $parsed_url_a['path'] : '';
            $path_b = isset($parsed_url_b['path']) ? $parsed_url_b['path'] : '';

            return $parsed_url_a['host'] == $parsed_url_b['host']
                && $path_a == $path_b;
        }

        return false;
    }

    private static function makeSegmentsFromAbsoluteUrl($absoluteUrl)
    {
        preg_match(
            '@' .
            '^(.*://|//)?' . // 1: schema (optional) like https://
            '([^/?]*)?' . // 2: host (optional) like wovn.io
            '((?:/|\?|#).*)?$' . // 3: path with query or hash
            '@',
            $absoluteUrl,
            $matches
        );
        return array(
            'schema' => array_key_exists(1, $matches) ? $matches[1] : '',
            'host' => array_key_exists(2, $matches) ? $matches[2] : '',
            'others' => array_key_exists(3, $matches) ? $matches[3] : ''
        );
    }
}
