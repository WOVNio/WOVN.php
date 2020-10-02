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
    public $host;
    public $pathname;
    public $url;

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
        $this->host = $this->unmaskedHost;
        if ($store->settings['url_pattern_name'] === 'subdomain') {
            $intermediateHost = explode('//', $this->removeLang($this->protocol . '://' . $this->host, $this->lang()));
            $this->host = $intermediateHost[1];
        }
        if ($store->settings['use_proxy'] && isset($env['HTTP_X_FORWARDED_REQUEST_URI'])) {
            $clientRequestUri = $env['HTTP_X_FORWARDED_REQUEST_URI'];
        } else {
            $clientRequestUri = $env['REQUEST_URI'];
        }
        $exploded = explode('?', $clientRequestUri);
        if ($store->settings['url_pattern_name'] === 'subdomain') {
            $this->pathname = $exploded[0];
        } else {
            $this->pathname = $this->removeLang($exploded[0], $this->lang());
        }
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
            if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_REQUEST_URI'])) {
                $request_uri = $this->env['HTTP_X_FORWARDED_REQUEST_URI'];
            } else {
                $request_uri = $this->env['REQUEST_URI'];
            }
            preg_match($rp, $server_name . $request_uri, $match);
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
            if (!headers_sent()) {
                $locationHeaders = array('location', 'Location');
                $responseHeaders = $this->getResponseHeaders();

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

    private function getResponseHeaders()
    {
        if (function_exists('apache_response_headers')) {
            return apache_response_headers();
        }

        $result = array();
        $headers = headers_list();
        foreach ($headers as $header) {
            $header = explode(":", $header);
            $result[array_shift($header)] = trim(implode(":", $header));
        }
        return $result;
    }

    /**
     * Public function reverts the URL to its base form (no lang code)
     * Notice: if there is default language code in custom language code, keep language code url
     *
     * @param String $uri The url with the lang
     * @param String $lang The lang to remove
     * @return String The url without the lang
     */
    public function removeLang($uri, $lang = null)
    {
        if ($lang === null) {
            $lang = $this->computePathLang();
        }

        $lang_code = $this->store->convertToCustomLangCode($lang);
        $default_lang = $this->store->settings['default_lang'];
        if ($this->store->hasDefaultLangAlias()) {
            $no_lang_uri = Url::removeLangCode($uri, $lang_code, $this->store->settings);
            return Url::addLangCode($no_lang_uri, $this->store, $default_lang, $this);
        } else {
            return Url::removeLangCode($uri, $lang_code, $this->store->settings);
        }
    }

    public function getDocumentURI($withQuery=false)
    {
        $url = $this->env['REQUEST_URI'];
        $url_arr = parse_url($url);

        if ($withQuery) {
            return $url;
        }

        if ($url_arr && array_key_exists('query', $url_arr)) {
            $query = $url_arr['query'];
            $uri = str_replace(array($query,'?'), '', $url);
        } else {
            $uri = $url;
        }

        return $this->removeLang($uri, $this->lang());
    }
}
