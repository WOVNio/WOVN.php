<?php
namespace Wovnio\Wovnphp;

/**
 *  The Headers class contains the server variable environnement _SERVER
 *  It is used to store environment and modify it
 */
class Headers
{
    public $protocol;
    public $originalHost;
    public $originalPath;
    public $host;
    public $pathname;
    public $url;
    public $query;

    private $env;
    private $store;
    private $urlLang;
    private $browserLang;
    private $cookieLang;

    /**
     * Constructor of the Headers class
     *
     * @param array &$env Contains the _SERVER env variable
     * @param Store &$store The store containing user settings
     * @param CookieLang $cookieLang A CookieLang instance
     * @return void
     */
    public function __construct(&$env, &$store, $cookieLang)
    {
        $this->env =& $env;
        $this->store =& $store;
        $this->cookieLang = $cookieLang;
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
            $this->originalHost = $env['HTTP_X_FORWARDED_HOST'];
        } else {
            $this->originalHost = $env['HTTP_HOST'];
        }

        $this->host = $this->originalHost;
        if ($store->settings['url_pattern_name'] === 'subdomain') {
            $intermediateHost = explode('//', $this->removeLang($this->protocol . '://' . $this->host, $this->requestLang()));
            $this->host = $intermediateHost[1];
        } elseif ($store->settings['url_pattern_name'] === 'custom_domain') {
            $this->host = $this->removeLang($this->host, $this->requestLang());
        }
        if ($store->settings['use_proxy'] && isset($env['HTTP_X_FORWARDED_REQUEST_URI'])) {
            $clientRequestUri = $env['HTTP_X_FORWARDED_REQUEST_URI'];
        } else {
            $clientRequestUri = $env['REQUEST_URI'];
        }
        $exploded = explode('?', $clientRequestUri);
        $this->pathname = $exploded[0];
        $this->originalPath = $this->pathname;
        if ($store->settings['url_pattern_name'] === 'path' || $store->settings['url_pattern_name'] === 'custom_domain') {
            $this->pathname = $this->removeLang($exploded[0], $this->requestLang());
        }
        $this->query = (!isset($exploded[1])) ? '' : $exploded[1];
        $this->query = $this->removeLang($this->query, $this->requestLang());
        $urlQuery = strlen($this->query) > 0 ? '?' . $this->query : '';

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
     * Returns the language code derived from the request, with fallback to default lang
     *
     * @return String The lang
     */
    public function requestLang()
    {
        $urlLang = $this->urlLanguage();

        return ($urlLang && strlen($urlLang) > 0)
            ? $urlLang
            : $this->store->settings['default_lang'];
    }

    /**
     * Returns the language explicitly specified in the request URL
     *
     * @return String lang code
     */
    public function urlLanguage()
    {
        if ($this->urlLang === null) {
            if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_HOST'])) {
                $server_name = $this->env['HTTP_X_FORWARDED_HOST'];
            } else {
                $server_name = $this->env['HTTP_HOST'];
            }
            // get the lang in the path
            if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_REQUEST_URI'])) {
                $request_uri = $this->env['HTTP_X_FORWARDED_REQUEST_URI'];
            } else {
                $request_uri = $this->env['REQUEST_URI'];
            }

            $full_url = $server_name . $request_uri;
            $lang_code = null;
            if ($this->store->settings['url_pattern_name'] == 'custom_domain') {
                $customDomainLangs = $this->store->getCustomDomainLangs();
                $customDomain = $customDomainLangs->getCustomDomainLangByUrl($full_url);
                if (!empty($customDomain)) {
                    $lang_code = $customDomain->getLang();
                }
            } else {
                $rp = '/' . $this->store->settings['url_pattern_reg'] . '/';
                preg_match($rp, $full_url, $match);
                if (isset($match['lang'])) {
                    $lang_identifier = $match['lang'];
                    $lang_code = Lang::formatLangCode($lang_identifier, $this->store);
                }
            }
            $this->urlLang = is_null($lang_code) ? '' : $lang_code;
        }
        return $this->urlLang;
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
                $this->removeLangFromQuery();
                break;
            case 'subdomain':
                $this->removeLangFromHost();
                break;
            case 'custom_domain':
                $this->removeLangFromHost();
                $this->removeLangFromPath();
                break;
            case 'path':
            default:
                $this->removeLangFromPath();
                break;
        }

        return $this->env;
    }

    private function removeLangFromHost()
    {
        if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_HOST'])) {
            $this->env['HTTP_X_FORWARDED_HOST'] = $this->removeLang($this->env['HTTP_X_FORWARDED_HOST']);
        }
        $this->env['HTTP_HOST'] = $this->removeLang($this->env['HTTP_HOST']);
        $this->env['SERVER_NAME'] = $this->removeLang($this->env['SERVER_NAME']);
    }

    private function removeLangFromPath()
    {
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

    private function removeLangFromQuery()
    {
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
    }

    /**
     * Changes the Location header to add the target lang code to the
     * redirection.
     */
    public function responseOut()
    {
        $urlLanguage = $this->urlLanguage();
        if (!$urlLanguage || strlen($urlLanguage)==0) {
            return;
        }

        if (headers_sent()) {
            return;
        }

        $locationHeaders = array('location', 'Location');
        $responseHeaders = $this->getResponseHeaders();
        $redirectLocation = null;
        $newLocation = null;

        foreach ($locationHeaders as $locationHeader) {
            if (array_key_exists($locationHeader, $responseHeaders)) {
                $redirectLocation = $responseHeaders[$locationHeader];
            }
        }

        if ($redirectLocation) {
            $newLocation = Url::addLangCode($redirectLocation, $this->store, $urlLanguage, $this);
        }

        if ($newLocation) {
            header($locationHeader . ': ' . $newLocation);
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
            $lang = $this->urlLanguage();
        }

        $lang_code = $this->store->convertToCustomLangCode($lang);
        $default_lang = $this->store->settings['default_lang'];
        if ($this->store->hasDefaultLangAlias()) {
            $no_lang_uri = Url::removeLangCode($uri, $lang_code, $this->store, $this);
            return Url::addLangCode($no_lang_uri, $this->store, $default_lang, $this);
        } else {
            return Url::removeLangCode($uri, $lang_code, $this->store, $this);
        }
    }

    public function getDocumentURI()
    {
        $url = $this->env['REQUEST_URI'];

        return $this->removeLang($url, $this->requestLang());
    }

    public function computeRedirectUrl()
    {
        $cookieLangCode = $this->cookieLang->getCookieLang();
        $url = $this->urlKeepTrailingSlash;
        $url = Url::addLangCode($url, $this->store, $cookieLangCode, $this);
        return $url;
    }

    public function shouldRedirect()
    {
        if (!$this->store->settings['use_cookie_lang']) {
            return false;
        }
        $requestLang = $this->requestLang();
        $cookieLang = $this->cookieLang->getCookieLang();

        return $cookieLang && ($requestLang !== $cookieLang) && ($requestLang === $this->store->defaultLang());
    }

    public function getCanonicalUrl()
    {
        $canonicalUrl = $this->protocol . '://' . $this->host . $this->pathnameKeepTrailingSlash;
        if ($this->store->settings['query_canonical_significance'] && !empty($this->query)) {
            $canonicalUrl = $canonicalUrl . '?' . $this->query;
        }
        return $canonicalUrl;
    }

    public function isSearchEngineBot()
    {
        return strpos($this->env['HTTP_USER_AGENT'], 'Googlebot/') !== false;
    }
}
