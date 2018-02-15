<?php
  namespace Wovnio\Wovnphp;

  /**
   *  The Headers class contains the server variable environnement _SERVER
   *  It is used to store environment and modify it
   */
  class Headers {
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

    private $_env;
    private $store;
    private $_pathLang;
    private $query;
    private $_browserLang;

    /**
     * Constructor of the Headers class
     *
     * @param array &$env Contains the _SERVER env variable
     * @param Store &$store The store containing user settings
     * @return void
     */
    public function __construct(&$env, &$store) {
      $this->_env =& $env;
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
      if(!isset($env['REQUEST_URI'])) {
        $env['REQUEST_URI'] = $env['PATH_INFO'] . (strlen($env['QUERY_STRING']) === 0 ? '' : '?' . $env['QUERY_STRING']);
      }
      if(isset($env['REDIRECT_URL'])) {
          $this->unmaskedPathname = $env['REDIRECT_URL'];
      }
      if (!preg_match('/\/$/', $this->unmaskedPathname) || !preg_match('/\/[^\/.]+\.[^\/.]+$/', $this->unmaskedPathname)) {
        $this->unmaskedPathname .= '/';
      }
      $this->unmaskedUrl = $this->protocol . '://' . $this->unmaskedHost . $this->unmaskedPathname;
      $this->host = $this->removeLang($this->unmaskedHost, $this->lang());
      $exploded = explode('?', $env['REQUEST_URI']);
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
          if (isset($match['queryVal']))
            array_push($queryVals, $match['queryVal']);
        }
        if (count($queryVals) > 0) {
          asort($queryVals);
          $this->query = '?' . implode('&', $queryVals);
        }
      }
      else {
        $this->query = '';
      }
      $this->query = $this->removeLang($this->query, $this->lang());
      $this->pathname = preg_replace('/\/$/', '', $this->pathname);
      $this->url = $this->protocol . '://' . $this->host . $this->pathname . $urlQuery;
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
    public function matchQuery($urlQuery, $querySettings) {
      if (empty($urlQuery) || empty($querySettings)) return '';
      $urlQuery = preg_replace('/^\?/', '', $urlQuery);
      $queryArray = explode('&', $urlQuery);
      sort($queryArray, SORT_STRING);
      foreach($queryArray as $k => $q) {
        $keep = false;
        foreach($querySettings as $qs) {
          if (strpos($q, $qs) !== false) {
            $keep = true;
          }
        }
        if (!$keep) {
          unset($queryArray[$k]);
        }
      }
      if (!empty($queryArray)){
        return '?' . implode('&', $queryArray);
      }
    }

    /**
     * Public function returning the _env variable
     *
     * @return array The environment
     */
    public function env() {
      return $this->_env;
    }

    /**
     * Public function returning an url array with protocol, host, pathname
     *
     * @return array The url array
     */
    public function getUrlArray() {
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
    public function lang() {
      return ($this->pathLang() && strlen($this->pathLang()) > 0) ? $this->pathLang() : $this->store->settings['default_lang'];
    }

    /**
     * Public function returning the pathLang
     *
     * @return String The path lang
     */
  public function pathLang() {
    if ($this->_pathLang === null) {
      if ($this->store->settings['use_proxy'] && isset($this->_env['HTTP_X_FORWARDED_HOST'])) {
        $server_name = $this->_env['HTTP_X_FORWARDED_HOST'];
      } else {
        $server_name = $this->_env['SERVER_NAME'];
      }
      // get the lang in the path
      $rp = '/' . $this->store->settings['url_pattern_reg'] . '/';
      preg_match($rp, $server_name . $this->_env['REQUEST_URI'], $match);
      if (isset($match['lang'])) {
        $lang_code = Lang::formatLangCode($match['lang'], $this->store);
        if (!is_null($lang_code)) {
          $this->_pathLang = $lang_code;
        }
      }
      if ($this->_pathLang === null) {
        $this->_pathLang = '';
      }
    }
    return $this->_pathLang;
  }

    /**
     * Public function returning the lang of the user's browser
     *
     * @return String The browser lang
     */
    public function browserLang() {
      if ($this->_browserLang === null) {
        // cookie lang
        if (isset($this->_env['HTTP_COOKIE'])) {
          $cookie = $this->_env['HTTP_COOKIE'];
        }
        else {
          $cookie = '';
        }
        preg_match('/wovn_selected_lang\s*=\s*(?P<lang>[^;\s]+)/', $cookie, $match);
        if (isset($match['lang']) && isset(Lang::$lang[$match['lang']])) {
          $this->_browserLang = $match['lang'];
        }
        else {
# IS THIS RIGHT?
          $this->_browserLang = '';
          if (isset($this->_env['HTTP_ACCEPT_LANGUAGE'])) {
            $httpAcceptLang = $this->_env['HTTP_ACCEPT_LANGUAGE'];
          }
          else {
            $httpAcceptLang = '';
          }
          $acceptLangs = preg_split('/[,;]/', $httpAcceptLang);
          foreach ($acceptLangs as $l) {
            if (isset(Lang::$lang[$l])) {
              $this->_browserLang = $l;
              break;
            }
          }
        }
      }
      return $this->_browserLang;
    }

    /**
     * Public function returning the location of the redirection
     *
     * @param String $lang The lang to display, can be null or empty
     * @return String The url of the redirections location
     */
    public function redirectLocation($lang=null) {
      if ($lang === null)
        $lang = $this->browserLang();
      if ($lang === $this->store->settings['default_lang'])
        return $this->protocol . '://' . $this->url;
      $location = $this-> url;
      switch ($this->store->settings['url_pattern_name']) {
        case 'query':
          //if (!preg_match('/\?/', $this->_env['REQUEST_URI'])) {
          // as $location is directtly modified it is safe to directly test it
          if (!preg_match('/\?/', $location)) {
            $location = $location . '?wovn=' . $lang;
          }
          else {
            $location = $location . '&wovn=' . $lang;
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
     * Public function returning the _env environment for the request out
     * The _env must be the same as if the user visited the page without the interceptor
     *
     * @return array The environment
     */
    public function requestOut() {
      if (isset($this->_env['HTTP_REFERER'])) {
        $this->_env['HTTP_REFERER'] = $this->removeLang($this->_env['HTTP_REFERER']);
      }

      switch ($this->store->settings['url_pattern_name']){
        case 'query':
          if (isset($this->_env['REQUEST_URI'])) {
            $this->_env['REQUEST_URI'] = $this->removeLang($this->_env['REQUEST_URI']);
          }
          $this->_env['QUERY_STRING'] = $this->removeLang($this->_env['QUERY_STRING']);
          if (isset($this->_env['ORIGINAL_FULLPATH']))
            $this->_env['ORIGINAL_FULLPATH'] = $this->removeLang($this->_env['ORIGINAL_FULLPATH']);
          break;

        case 'subdomain':
          if ($this->store->settings['use_proxy'] && isset($this->_env['HTTP_X_FORWARDED_HOST'])) {
            $this->_env['HTTP_X_FORWARDED_HOST'] = $this->removeLang($this->_env['HTTP_X_FORWARDED_HOST']);
          }
          $this->_env['HTTP_HOST'] = $this->removeLang($this->_env['HTTP_HOST']);
          $this->_env['SERVER_NAME'] = $this->removeLang($this->_env['SERVER_NAME']);
          break;

        case 'path':
        default:
          if (isset($this->_env['REQUEST_URI'])) {
            $this->_env['REQUEST_URI'] = $this->removeLang($this->_env['REQUEST_URI']);
          }
          if (isset($this->_env['REDIRECT_URL'])) {
            $this->_env['REDIRECT_URL'] = $this->removeLang($this->_env['REDIRECT_URL']);
          }
      }

      return $this->_env;
    }

    /**
     * Changes the Location header to add the target lang code to the
     * redirection.
     */
    public function responseOut() {
      $lang = $this->pathLang();

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
     *
     * @param String $uri The url with the lang
     * @param String $lang The lang to remove
     * @return array The url without the lang
     */
    public function removeLang($uri, $lang=null) {
      if ($lang === null) {
        $lang = $this->pathLang();
      }

      $lang_code = $this->store->convertToCustomLangCode($lang);
      return Url::removeLangCode($uri, $this->store->settings['url_pattern_name'], $lang_code);
    }

    /**
     * Public function setting the query param of the page in the _env
     * Query and param should look like this: param=value
     *
     * @param String $param The query param (param=)
     * @param String $val The value of the query param (=value)
     * @return void
     */
    public function setQueryParam($param, $val) {
      global $_GET, $_REQUEST;

      // get old query string
      if (isset($this->_env['QUERY_STRING'])) {
        $oldQueryString = $this->_env['QUERY_STRING'];
      }
      elseif (isset($this->_env['REDIRECT_QUERY_STRING'])) {
        $oldQueryString = $this->_env['REDIRECT_QUERY_STRING'];
      }
      // if there is a query string in the request_uri
      elseif (isset($this->_env['REQUEST_URI']) && preg_match('/\?/', $this->_env['REQUEST_URI'])) {
        $oldQueryString = preg_replace('/^.*\?(.*)$/', '$1', $this->_env['REQUEST_URI']);
      }
      else {
        $oldQueryString = '';
      }

      // make new query string
      if ($oldQueryString === '') {
        $newQueryString = $param . '=' . $val;
      }
      elseif (preg_match('/(^|&)' . $param . '(=|&|$)/', $oldQueryString)) {
        $newQueryString = preg_replace('/(^|&)' . $param . '[^&]*/', '$1' . $param . '=' . $val, $oldQueryString);
      }
      else {
        $newQueryString = $oldQueryString . '&' . $param . '=' . $val;
      }

      // set new query string
      $this->_env['QUERY_STRING'] = $newQueryString;
      if (isset($this->_env['REDIRECT_QUERY_STRING'])) {
        $this->_env['REDIRECT_QUERY_STRING'] = $newQueryString;
      }
      if (isset($this->_env['REQUEST_URI'])) {
        $this->_env['REQUEST_URI'] = preg_replace('/\?.*$/', '', $this->_env['REQUEST_URI']);
        $this->_env['REQUEST_URI'] .= '?' . $newQueryString;
      }

      // set $_GET and $_REQUEST
      $_GET[$param] = $val;
      if (!isset($_REQUEST[$param])) $_REQUEST[$param] = $val; // do not set if a POST or a cookie value is set.
    }

    /**
     * Set all query params in the passed-in array
     *
     * @param array $queryArray The array of query=val Strings to set
     * @return void
     */
    public function setQueryParams($queryArray) {
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
    public function clearQueryParams() {
      // empty query strings
      if (isset($this->_env['QUERY_STRING'])) {
        $this->_env['QUERY_STRING'] = '';
      }
      if (isset($this->_env['REDIRECT_QUERY_STRING'])) {
        $this->_env['REDIRECT_QUERY_STRING'] = '';
      }
      if (isset($this->_env['REQUEST_URI'])) {
        $this->_env['REQUEST_URI'] = preg_replace('/\?.*$/', '', $this->_env['REQUEST_URI']);
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
    public function redirectTo($location) {
      header('Location: ' . $location, TRUE, 301);die();
    }

  }
