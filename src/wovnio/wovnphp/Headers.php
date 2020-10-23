<?php

namespace Wovnio\Wovnphp;

use Wovnio\Wovnphp\Environment;

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

    private $env;
    private $envWrapper;
    private $store;
    private $urlLang;
    private $query;
    private $browserLang;

    /**
     * Constructor of the Headers class
     *
     * @param array &$env Contains the _SERVER env variable
     * @param Store &$store The store containing user settings
     * @param Environment $envWrapper $_SERVER env wrapper
     * @return void
     */
    public function __construct($store, $envWrapper)
    {
        $this->store = $store;
        $this->envWrapper = $envWrapper;

        $this->protocol = $this->envWrapper->getProtocol();
        $this->originalHost = $this->envWrapper->getHost();
        $this->host = $this->originalHost;

        if ($store->settings['url_pattern_name'] === 'subdomain') {
            $intermediateHost = explode('//', $this->removeLang($this->protocol . '://' . $this->host, $this->requestLang()));
            $this->host = $intermediateHost[1];
        } elseif ($store->settings['url_pattern_name'] === 'custom_domain') {
            $this->host = $this->removeLang($this->host, $this->requestLang());
        }

        $clientRequestUri = $this->envWrapper->getRequestUri();

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
            $server_name = $this->envWrapper->getServerName();

            // get the lang in the path
            $rp = '/' . $this->store->settings['url_pattern_reg'] . '/';
            $requestUri = $this->envWrapper->getRequestUri();

            $full_url = $server_name . $requestUri;

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
            $cookie = $this->envWrapper->getCookies();
            preg_match('/wovn_selected_lang\s*=\s*(?P<lang>[^;\s]+)/', $cookie, $match);
            if (isset($match['lang']) && isset(Lang::$lang[$match['lang']])) {
                $this->browserLang = $match['lang'];
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
        $this->envWrapper->setReferer($this->removeLang($this->envWrapper->getReferer()));

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
        $this->envWrapper->setHost($this->removeLang($this->$originalHost));
        $this->envWrapper->setServerName($this->removeLang($this->envWrapper->getServerName()));
    }

    private function removeLangFromPath()
    {
        $this->envWrapper->setRequestUri($this->removeLang($this->envWrapper->getRequestUri()));
        $this->envWrapper->setRedirectUrl($this->removeLang($this->envWrapper->getRedirectUrl()));
    }

    private function removeLangFromQuery()
    {
        $this->envWrapper->setRequestUri($this->removeLang($this->envWrapper->getRequestUri()));
        $this->envWrapper->setQueryString($this->removeLang($this->envWrapper->getQueryString()));
        $this->envWrapper->setOriginalFullpath($this->removeLang($this->envWrapper->getOriginalFullpath()));
    }

    /**
     * Changes the Location header to add the target lang code to the
     * redirection.
     */
    public function responseOut()
    {
        $urlLanguage = $this->urlLanguage();

        if ($urlLanguage && strlen($urlLanguage) > 0) {
            if (!headers_sent()) {
                $locationHeaders = array('location', 'Location');
                $responseHeaders = $this->getResponseHeaders();

                foreach ($locationHeaders as $locationHeader) {
                    if (array_key_exists($locationHeader, $responseHeaders)) {
                        $redirectLocation = $responseHeaders[$locationHeader];
                        $newLocation = Url::addLangCode($redirectLocation, $this->store, $urlLanguage, $this);

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
        if ($uri === null) {
            return $uri;
        }

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
        $url = $this->envWrapper->getRequestUri();

        return $this->removeLang($url, $this->requestLang());
    }
}
