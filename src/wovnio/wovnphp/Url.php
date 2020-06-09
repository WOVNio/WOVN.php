<?php
namespace Wovnio\Wovnphp;

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
     * Adds a language code to a uri.
     *
     * @param String  $uri     The uri to modify.
     * @param Store $store
     * @param String  $lang    The language code to add to the uri.
     * @param Headers $headers The headers.
     *
     * @return String The new uri containing the language code.
     */
    public static function addLangCode($uri, $store, $lang, $headers)
    {
        if (!$lang || strlen($lang) == 0) {
            return $uri;
        }

        // anchor links case, do nothing
        if (preg_match('/^(#.*)?$/', $uri)) {
            return $uri;
        }

        $new_uri = $uri;
        $pattern = $store->settings['url_pattern_name'];
        $site_prefix_path = $store->settings['site_prefix_path'];
        $lang_code = $store->convertToCustomLangCode($lang);
        $lang_param_name = $store->settings['lang_param_name'];

        if (Utils::isIgnoredPath($uri, $store)) {
            return $uri;
        }

        $no_lang_uri = self::removeLangCode($uri, $lang_code, $store->settings);
        $no_lang_host = self::removeLangCode($headers->host, $lang_code, $store->settings);

        if ($store->defaultLangAlias()) {
            $default_lang = $store->settings['default_lang'];
            $no_lang_uri = self::removeLangCode($no_lang_uri, $default_lang, $store->settings);
            $no_lang_host = self::removeLangCode($no_lang_host, $default_lang, $store->settings);
        }

        // absolute urls
        if (preg_match('/^(https?:)?\/\//i', $no_lang_uri)) {
            // only to use with absolute urls!!
            $parsed_url = parse_url($no_lang_uri);
            // On seriously malformed URLs, parse_url() may return FALSE. (php doc)
            if (!$parsed_url) {
                return $uri;
            }

            $parsed_host = array_key_exists('host', $parsed_url) ? $parsed_url['host'] : null;
            if ($parsed_host !== null && array_key_exists('port', $parsed_url)) {
                $parsed_host = $parsed_host . ':' . $parsed_url['port'];
            }

            // only continue if the host of the url is the same as the headers host
            if ($parsed_host !== null && strtolower($parsed_host) === strtolower($no_lang_host)) {
                switch ($pattern) {
                    case 'subdomain':
                        // check if subdomain already exists
                        if (preg_match('/\./', $parsed_url['host'])) {
                            $sub_do_a = explode('.', $parsed_url['host']);
                            $sub_do = $sub_do_a[0];
                            $sub_do_lang = Lang::getCode($sub_do);
                            if ($sub_do_lang && strtolower($sub_do_lang) === strtolower($lang_code)) {
                                $new_uri = preg_replace('/' . $lang_code . '/i', strtolower($lang_code), $no_lang_uri, 1);
                            } else {
                                $new_uri = preg_replace('/(\/\/)([^\.]*)/', '${1}' . self::formatForRegExp(strtolower($lang_code)) . '.' . '${2}', $no_lang_uri, 1);
                            }
                        } else {
                            $new_uri = preg_replace('/(\/\/)([^\.]*)/', '${1}' . self::formatForRegExp(strtolower($lang_code)) . '.' . '${2}', $no_lang_uri, 1);
                        }
                        break;
                    case 'query':
                        $new_uri = self::addQueryLangCode($no_lang_uri, $lang_code, $lang_param_name);
                        break;
                    default:
                        //path
                        $new_uri = self::addPathLangCode($no_lang_uri, $lang_code, $site_prefix_path);
                }
            }
        } else {
            if (!preg_match('/:/', $no_lang_uri)) { // do nothing for protocols other than http and https (e.g. tel)
                // relative links
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
                    default: // path
                        if (preg_match('/^\//', $no_lang_uri)) {
                            $new_uri = self::addPathLangCode($no_lang_uri, $lang_code, $site_prefix_path);
                        } else {
                            $current_dir = preg_replace('/[^\/]*\.[^\.]{2,6}$/', '', $headers->pathname, 1);
                            $new_uri = self::addPathLangCode($current_dir . $no_lang_uri, $lang_code, $site_prefix_path);
                        }
                }
            }
        }

        return $new_uri;
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
     * Public function removing the lang of the url
     *
     * @param String $uri The url with the lang
     * @param String $pattern
     * @param String $lang_code The lang to remove
     * @return array The url without the lang
     */
    public static function removeLangCode($uri, $lang_code, $settings)
    {
        if (!$lang_code || strlen($lang_code) == 0) {
            return $uri;
        }

        // anchor links case, do nothing
        if (preg_match('/^(#.*)?$/', $uri)) {
            return $uri;
        }

        $pattern = $settings['url_pattern_name'];
        $lang_param_name = $settings['lang_param_name'];
        $site_prefix_path = $settings['site_prefix_path'];

        switch ($pattern) {
            case 'query':
                return preg_replace('/(\?|&)$/', '', preg_replace('/(^|\?|&)' . $lang_param_name . '=' . $lang_code . '(&|$)/i', '\1', $uri));
                break;
            case 'subdomain':
                // limit to one replacement
                return preg_replace('/(\/\/|^)' . $lang_code . '\./i', '\1', $uri, 1);
                break;
            case 'path':
            default:
                // limit to one replacement
                $prefix = empty($site_prefix_path) ? '' : '/' . $site_prefix_path;
                return preg_replace("@$prefix/$lang_code(/|$)@i", "$prefix/", $uri, 1);
                break;
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
}
