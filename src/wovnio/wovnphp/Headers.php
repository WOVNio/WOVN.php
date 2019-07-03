<?php
namespace Wovnio\Wovnphp;

/**
 *  The Headers class contains the server variable environnement _SERVER
 *  It is used to store environment and modify it
 */
class Headers
{
    public $protocol;
    public $unmaskedHost;
    public $unmaskedPathname;
    public $unmaskedUrl;
    public $host;
    public $pathname;
    public $url;
    public $redisUrl;
    // PHP ONLY
    public $maskedRequestURI;

    private $env;
    private $store;
    private $pathLang;
    private $query;
    private $browserLang;

    /**
     * Constructor of the Headers class
     *
     * @param array &$env Contains the _SERVER env variable
     * @param Store &$store The store containing user settings
     * @return void
     */
    public function __construct(&$env, &$store)
    {
        $this->env =& $env;
        $this->store =& $store;
        if ($store->settings['use_proxy'] && isset($env['HTTP_X_FORWARDED_PROTO'])) {
            $this->protocol = $env['HTTP_X_FORWARDED_PROTO'];
        } else {
            if (isset($env['HTTPS']) && !empty($env['HTTPS']) && $env['HTTPS'] !== 'off') {
                $this->protocol = 'https';
            } else {
                $this->protocol = 'http';
            }
        }
        if ($store->settings['use_proxy'] && isset($env['HTTP_X_FORWARDED_HOST'])) {
            $this->unmaskedHost = $env['HTTP_X_FORWARDED_HOST'];
        } else {
            $this->unmaskedHost = $env['HTTP_HOST'];
        }
        if (!isset($env['REQUEST_URI'])) {
            $env['REQUEST_URI'] = $env['PATH_INFO'] . (strlen($env['QUERY_STRING']) === 0 ? '' : '?' . $env['QUERY_STRING']);
        }
        if ($store->settings['use_proxy'] && isset($env['HTTP_X_FORWARDED_REQUEST_URI'])) {
            $this->unmaskedPathname = $env['HTTP_X_FORWARDED_REQUEST_URI'];
        } elseif (isset($env['REDIRECT_URL'])) {
            $this->unmaskedPathname = $env['REDIRECT_URL'];
        }
        if (!preg_match('/\/$/', $this->unmaskedPathname) || !preg_match('/\/[^\/.]+\.[^\/.]+$/', $this->unmaskedPathname)) {
            $this->unmaskedPathname .= '/';
        }
        $this->unmaskedUrl = $this->protocol . '://' . $this->unmaskedHost . $this->unmaskedPathname;
        $this->host = $this->unmaskedHost;
        if ($store->settings['url_pattern_name'] === 'subdomain') {
            $this->host = $this->removeLang($this->host, $this->lang());
        }
        if ($store->settings['use_proxy'] && isset($env['HTTP_X_FORWARDED_REQUEST_URI'])) {
            $clientRequestUri = $env['HTTP_X_FORWARDED_REQUEST_URI'];
        } else {
            $clientRequestUri = $env['REQUEST_URI'];
        }
        $exploded = explode('?', $clientRequestUri);
        $this->pathname = $this->removeLang($exploded[0], $this->lang());
        $this->query = (!isset($exploded[1])) ? '' : $exploded[1];
        $urlQuery = $this->removeLang($this->query, $this->lang());
        $urlQuery = strlen($urlQuery) > 0 ? '?' . $urlQuery : '';
        $this->url = $this->protocol . '://' . $this->host . $this->pathname . $urlQuery;
        if (count($store->settings['query']) > 0) {
            $queryVals = array();
            foreach ($store->settings['query'] as $qv) {
                $rp = '/(^|&)(?P<queryVal>' . $qv . '[^&]+)(&|$)/';
                preg_match($rp, $this->query, $match);
                if (isset($match['queryVal'])) {
                    array_push($queryVals, $match['queryVal']);
                }
            }
            if (count($queryVals) > 0) {
                asort($queryVals);
                $this->query = '?' . implode('&', $queryVals);
            }
        } else {
            $this->query = '';
        }
        $this->query = $this->removeLang($this->query, $this->lang());
        $this->pathnameKeepTrailingSlash = $this->pathname;
        $this->pathname = preg_replace('/\/$/', '', $this->pathname);
        $this->url = $this->protocol . '://' . $this->host . $this->pathname . $urlQuery;
        $this->urlKeepTrailingSlash = $this->protocol . '://' . $this->host . $this->pathnameKeepTrailingSlash . $urlQuery;
        if (isset($store->settings['query']) && !empty($store->settings['query'])) {
            $this->redisUrl = $this->host . $this->pathname . $this->matchQuery($urlQuery, $store->settings['query']);
        } else {
            $this->redisUrl = $this->host . $this->pathname;// . $urlQuery;
        }
        // PHP ONLY
        $this->maskedRequestURI = $this->removeLang(preg_replace('/\?.*$/', '', $env['REQUEST_URI']));
    }

    /**
     * Public function matching the query in the url with the query params in the settings
     *  - Will remove query params not include in the settings
     *  - Will sort the query params in order and deliver a valid string
     *
     * @return String A valid query params string with '?' and separators '&'
     */
    public function matchQuery($urlQuery, $querySettings)
    {
        if (empty($urlQuery) || empty($querySettings)) {
            return '';
        }

        $urlQuery = preg_replace('/^\?/', '', $urlQuery);
        $queryArray = explode('&', $urlQuery);

        sort($queryArray, SORT_STRING);
        foreach ($queryArray as $k => $q) {
            $keep = false;
            foreach ($querySettings as $qs) {
                if (strpos($q, $qs) !== false) {
                    $keep = true;
                }
            }
            if (!$keep) {
                unset($queryArray[$k]);
            }
        }
        if (!empty($queryArray)) {
            return '?' . implode('&', $queryArray);
        }
    }

    /**
     * Public function returning the env variable
     *
     * @return array The environment
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Public function returning an url array with protocol, host, pathname
     *
     * @return array The url array
     */
    public function getUrlArray()
    {
        $url = array();
        $url['protocol'] = $this->protocol;
        $url['host'] = $this->host;
        $url['pathname'] = $this->pathname;
        return $url;
    }

    /**
     * Public function returns the pathLang if exists or the default lang in the store
     *
     * @return String The lang
     */
    public function lang()
    {
        return ($this->computePathLang() && strlen($this->computePathLang()) > 0) ? $this->computePathLang() : $this->store->settings['default_lang'];
    }

    /**
     * Public function returning the pathLang
     *
     * @return String The path lang
     */
    public function computePathLang()
    {
        if ($this->pathLang === null) {
            if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_HOST'])) {
                $server_name = $this->env['HTTP_X_FORWARDED_HOST'];
            } else {
                $server_name = $this->env['SERVER_NAME'];
            }
            // get the lang in the path
            $rp = '/' . $this->store->settings['url_pattern_reg'] . '/';
            preg_match($rp, $server_name . $this->env['REQUEST_URI'], $match);
            if (isset($match['lang'])) {
                $lang_code = Lang::formatLangCode($match['lang'], $this->store);
                if (!is_null($lang_code)) {
                    $this->pathLang = $lang_code;
                }
            }
            if ($this->pathLang === null) {
                $this->pathLang = '';
            }
        }
        return $this->pathLang;
    }

    /**
     * Public function returning the lang of the user's browser
     *
     * @return String The browser lang
     */
    public function computeBrowserLang()
    {
        if ($this->browserLang === null) {
            // cookie lang
            if (isset($this->env['HTTP_COOKIE'])) {
                $cookie = $this->env['HTTP_COOKIE'];
            } else {
                $cookie = '';
            }
            preg_match('/wovn_selected_lang\s*=\s*(?P<lang>[^;\s]+)/', $cookie, $match);
            if (isset($match['lang']) && isset(Lang::$lang[$match['lang']])) {
                $this->browserLang = $match['lang'];
            } else {
                # IS THIS RIGHT?
                $this->browserLang = '';
                if (isset($this->env['HTTP_ACCEPT_LANGUAGE'])) {
                    $httpAcceptLang = $this->env['HTTP_ACCEPT_LANGUAGE'];
                } else {
                    $httpAcceptLang = '';
                }
                $acceptLangs = preg_split('/[,;]/', $httpAcceptLang);
                foreach ($acceptLangs as $l) {
                    if (isset(Lang::$lang[$l])) {
                        $this->browserLang = $l;
                        break;
                    }
                }
            }
        }
        return $this->browserLang;
    }

    /**
     * Public function returning the location of the redirection
     *
     * @param String $lang The lang to display, can be null or empty
     * @return String The url of the redirections location
     */
    public function redirectLocation($lang = null)
    {
        if ($lang === null) {
            $lang = $this->computeBrowserLang();
        }
        if ($lang === $this->store->settings['default_lang']) {
            return $this->protocol . '://' . $this->url;
        }
        $location = $this-> url;
        switch ($this->store->settings['url_pattern_name']) {
            case 'query':
                // if (!preg_match('/\?/', $this->env['REQUEST_URI'])) {
                // as $location is directtly modified it is safe to directly test it
                if (!preg_match('/\?/', $location)) {
                    $location = $location . '?' . $this->store->settings['lang_param_name'] . '=' . $lang;
                } else {
                    $location = $location . '&' . $this->store->settings['lang_param_name'] . '=' . $lang;
                }
                break;
            case 'subdomain':
                $location = $lang . '.' . $location;
                break;
            case 'path':
            default:
                $location = preg_replace('/(\/|$)/', '/' . $lang . '/', $location, 1);
        }
        return $this->protocol . '://' . $location;
    }


    /**
     * Public function returning the env environment for the request out
     * The env must be the same as if the user visited the page without the interceptor
     *
     * @return array The environment
     */
    public function requestOut()
    {
        if (isset($this->env['HTTP_REFERER'])) {
            $this->env['HTTP_REFERER'] = $this->removeLang($this->env['HTTP_REFERER']);
        }

        switch ($this->store->settings['url_pattern_name']) {
            case 'query':
                if (isset($this->env['REQUEST_URI'])) {
                    $this->env['REQUEST_URI'] = $this->removeLang($this->env['REQUEST_URI']);
                }
                $this->env['QUERY_STRING'] = $this->removeLang($this->env['QUERY_STRING']);
                if (isset($this->env['ORIGINAL_FULLPATH'])) {
                    $this->env['ORIGINAL_FULLPATH'] = $this->removeLang($this->env['ORIGINAL_FULLPATH']);
                }
                if (isset($this->env['HTTP_X_FORWARDED_REQUEST_URI'])) {
                    $this->env['HTTP_X_FORWARDED_REQUEST_URI'] = $this->removeLang($this->env['X-FORWARDED_REQUEST_URI']);
                }
                break;

            case 'subdomain':
                if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_HOST'])) {
                    $this->env['HTTP_X_FORWARDED_HOST'] = $this->removeLang($this->env['HTTP_X_FORWARDED_HOST']);
                }
                $this->env['HTTP_HOST'] = $this->removeLang($this->env['HTTP_HOST']);
                $this->env['SERVER_NAME'] = $this->removeLang($this->env['SERVER_NAME']);
                break;

            case 'path':
            default:
                if (isset($this->env['REQUEST_URI'])) {
                    $this->env['REQUEST_URI'] = $this->removeLang($this->env['REQUEST_URI']);
                }
                if (isset($this->env['REDIRECT_URL'])) {
                    $this->env['REDIRECT_URL'] = $this->removeLang($this->env['REDIRECT_URL']);
                }
                if (isset($this->env['HTTP_X_FORWARDED_REQUEST_URI'])) {
                    $this->env['HTTP_X_FORWARDED_REQUEST_URI'] = $this->removeLang($this->env['HTTP_X_FORWARDED_REQUEST_URI']);
                }
        }

        return $this->env;
    }

    /**
     * Changes the Location header to add the target lang code to the
     * redirection.
     */
    public function responseOut()
    {
        $lang = $this->computePathLang();

        if ($lang && strlen($lang) > 0) {
            if (function_exists('apache_response_headers') && !headers_sent()) {
                $locationHeaders = array('location', 'Location');
                $responseHeaders = apache_response_headers();

                foreach ($locationHeaders as $locationHeader) {
                    if (array_key_exists($locationHeader, $responseHeaders)) {
                        $redirectLocation = $responseHeaders[$locationHeader];
                        $newLocation = Url::addLangCode($redirectLocation, $this->store, $lang, $this);

                        header($locationHeader . ': ' . $newLocation);
                    }
                }
            }
        }
    }

    /**
     * Public function removing the lang of the url
     * Notice: if there is default language code in custom language code, keep language code url
     *
     * @param String $uri The url with the lang
     * @param String $lang The lang to remove
     * @return array The url without the lang
     */
    public function removeLang($uri, $lang = null)
    {
        if ($lang === null) {
            $lang = $this->computePathLang();
        }

        $lang_code = $this->store->convertToCustomLangCode($lang);
        $default_lang = $this->store->settings['default_lang'];
        $aliases = $this->store->settings['custom_lang_aliases'];
        if (array_key_exists($default_lang, $aliases)) {
            $no_lang_uri = Url::removeLangCode($uri, $this->store->settings['url_pattern_name'], $lang_code, $this->store->settings['lang_param_name']);
            return Url::addLangCode($no_lang_uri, $this->store, $default_lang, $this);
        } else {
            return Url::removeLangCode($uri, $this->store->settings['url_pattern_name'], $lang_code, $this->store->settings['lang_param_name']);
        }
    }

    /**
     * Public function setting the query param of the page in the env
     * Query and param should look like this: param=value
     *
     * @param String $param The query param (param=)
     * @param String $val The value of the query param (=value)
     * @return void
     */
    public function setQueryParam($param, $val)
    {
        global $_GET, $_REQUEST;

        // get old query string
        if (isset($this->env['QUERY_STRING'])) {
            $oldQueryString = $this->env['QUERY_STRING'];
        } elseif (isset($this->env['REDIRECT_QUERY_STRING'])) {
            $oldQueryString = $this->env['REDIRECT_QUERY_STRING'];
            // if there is a query string in the request_uri
        } elseif (isset($this->env['REQUEST_URI']) && preg_match('/\?/', $this->env['REQUEST_URI'])) {
            $oldQueryString = preg_replace('/^.*\?(.*)$/', '$1', $this->env['REQUEST_URI']);
        } else {
            $oldQueryString = '';
        }

        // make new query string
        if ($oldQueryString === '') {
            $newQueryString = $param . '=' . $val;
        } elseif (preg_match('/(^|&)' . $param . '(=|&|$)/', $oldQueryString)) {
            $newQueryString = preg_replace('/(^|&)' . $param . '[^&]*/', '$1' . $param . '=' . $val, $oldQueryString);
        } else {
            $newQueryString = $oldQueryString . '&' . $param . '=' . $val;
        }

        // set new query string
        $this->env['QUERY_STRING'] = $newQueryString;
        if (isset($this->env['REDIRECT_QUERY_STRING'])) {
            $this->env['REDIRECT_QUERY_STRING'] = $newQueryString;
        }
        if (isset($this->env['REQUEST_URI'])) {
            $this->env['REQUEST_URI'] = preg_replace('/\?.*$/', '', $this->env['REQUEST_URI']);
            $this->env['REQUEST_URI'] .= '?' . $newQueryString;
        }

        // set $_GET and $_REQUEST
        $_GET[$param] = $val;
        if (!isset($_REQUEST[$param])) {
            // do not set if a POST or a cookie value is set.
            $_REQUEST[$param] = $val;
        }
    }

    /**
     * Set all query params in the passed-in array
     *
     * @param array $queryArray The array of query=val Strings to set
     * @return void
     */
    public function setQueryParams($queryArray)
    {
        foreach ($queryArray as $qv) {
            $parts = explode('=', $qv);
            $parts[1] = urldecode(preg_replace('/\.[^&=]*$/', '', $parts[1]));
            $parts[0] = urldecode($parts[0]);
            $this->setQueryParam($parts[0], $parts[1]);
        }
    }

    /**
     * Clear all query params
     *
     * @return void
     */
    public function clearQueryParams()
    {
        // empty query strings
        if (isset($this->env['QUERY_STRING'])) {
            $this->env['QUERY_STRING'] = '';
        }
        if (isset($this->env['REDIRECT_QUERY_STRING'])) {
            $this->env['REDIRECT_QUERY_STRING'] = '';
        }
        if (isset($this->env['REQUEST_URI'])) {
            $this->env['REQUEST_URI'] = preg_replace('/\?.*$/', '', $this->env['REQUEST_URI']);
        }

        // unset all keys in the $_GET array
        foreach ($_GET as $key => $val) {
            unset($_GET[$key]);
        }
    }

    /**
     * Make a redirection with a 301 code to a specified location
     * and dies
     *
     * @param String $location The location where to make the redirection
     */
    public function redirectTo($location)
    {
        header('Location: ' . $location, true, 301);
        die();
    }

    public function getDocumentURI()
    {
        $url = $this->env['REQUEST_URI'];
        $url_arr = parse_url($url);

        if ($url_arr && array_key_exists('query', $url_arr)) {
            $query = $url_arr['query'];
            $uri = str_replace(array($query,'?'), '', $url);
        } else {
            $uri = $url;
        }

        return $this->removeLang($uri, $this->lang());
    }
}
