<?php
namespace Wovnio\Wovnphp;

class Url {
  /**
   * Escapes a text to be used inside of a regular expression.
   *
   * @param String $text The text to escape.
   *
   * @return String The text escaped to be used inside a regular expression.
   */
  private static function formatForRegExp($text) {
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
  public static function addLangCode($uri, $store, $lang, $headers) {
    if (!$lang || strlen($lang) == 0) {
      return $uri;
    }

    #anchor links case, do nothing
    if (preg_match('/^(#.*)?$/', $uri)) {
      return $uri;
    }

    $new_uri = $uri;
    $pattern = $store->settings['url_pattern_name'];
    $lang_code = $store->convertToCustomLangCode($lang);

    $no_lang_uri = self::removeLangCode($uri, $pattern, $lang_code);
    $no_lang_host = self::removeLangCode($headers->host, $pattern, $lang_code);

    if ($store->defaultLangAlias()) {
        $default_lang = $store->settings['default_lang'];
        $no_lang_uri = self::removeLangCode($no_lang_uri, $pattern, $default_lang);
        $no_lang_host = self::removeLangCode($no_lang_host, $pattern, $default_lang);
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
              $sub_do_a = explode( '.', $parsed_url['host']);
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
            $new_uri = self::addQueryLangCode($no_lang_uri, $lang_code);
            break;
          default:
            //path
            $new_uri = preg_replace('/([^\.]*\.[^?\/]*)(\?|\/|$)/', '${1}/' . self::formatForRegExp($lang_code) . '${2}', $no_lang_uri, 1);
        }
      }
    } else {
      if(!preg_match('/:/', $no_lang_uri)) { // do nothing for protocols other than http and https (e.g. tel)
        // relative links
        switch ($pattern) {
          case 'subdomain':
            $lang_url = $headers->protocol . '://' . strtolower($lang_code) . '.' . $headers->host;
            $current_dir = preg_replace('/[^\/]*\.[^\.]{2,6}$/', '', $headers->pathname,1);
            if (preg_match('/^\.\..*$/',$no_lang_uri)) {
              // ../path
              $new_uri = $lang_url . '/' . preg_replace('/^\.\.\//', '', $no_lang_uri);
            }
            else if (preg_match('/^\..*$/', $no_lang_uri)) {
              // ./path
              $new_uri = $lang_url . $current_dir . preg_replace('/^\.\//', '', $no_lang_uri);
            }
            else if (preg_match('/^\/.*$/', $no_lang_uri)) {
              // /path
              $new_uri = $lang_url . $no_lang_uri;
            }
            else {
              $new_uri = $lang_url . $current_dir . $no_lang_uri;
            }
            break;
          case 'query':
            $new_uri = self::addQueryLangCode($no_lang_uri, $lang_code);
            break;
          default: // path
            if (preg_match('/^\//', $no_lang_uri)) {
              $new_uri = '/' . $lang_code . $no_lang_uri;
            }
            else {
              $current_dir = preg_replace('/[^\/]*\.[^\.]{2,6}$/', '', $headers->pathname, 1);
              $new_uri = '/' . $lang_code . $current_dir . $no_lang_uri;
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
  private static function addQueryLangCode($uri, $lang_code) {
    $sep = '?';
    if (preg_match('/\?/', $uri)) {
      $sep = '&';
    }
    return preg_replace('/(#|$)/', $sep . 'wovn=' . $lang_code . '${1}', $uri, 1);
  }

  /**
   * Public function removing the lang of the url
   *
   * @param String $uri The url with the lang
   * @param String $pattern
   * @param String $lang_code The lang to remove
   * @return array The url without the lang
   */
  public static function removeLangCode($uri, $pattern, $lang_code) {
    if (!$lang_code || strlen($lang_code) == 0) {
      return $uri;
    }

    #anchor links case, do nothing
    if (preg_match('/^(#.*)?$/', $uri)) {
      return $uri;
    }

    switch ($pattern) {
      case 'query':
        return preg_replace('/(\?|&)$/', '', preg_replace('/(^|\?|&)wovn=' . $lang_code . '(&|$)/i', '\1', $uri));
      break;
      case 'subdomain':
        # limit to one replacement
        return preg_replace('/(\/\/|^)' . $lang_code . '\./i', '\1', $uri, 1);
      break;
      case 'path':
      default:
        # limit to one replacement
        return preg_replace('/\/' . $lang_code . '(\/|$)/i', '/', $uri, 1);
      break;
    }
  }
}
