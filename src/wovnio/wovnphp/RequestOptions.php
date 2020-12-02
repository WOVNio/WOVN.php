<?php
namespace Wovnio\Wovnphp;

class RequestOptions
{
    /*
     * disableMode:
     *      - do nothing to the request
     */
    private $disable_mode;
    /*
     * cacheDisableMode:
     *      - bypass cache for request to translation API
     * Only available if debugMode is also turned on server side.
     */
    private $cache_disable_mode;
    /*
     * debugMode:
     *      - activate extra debugging information.
     *      - send "debug_mode=true" to translation API
     *      - bypass cache for request to translation API
     * Only available if debugMode is also turned on server side.
     */
    private $debug_mode;


    public function __construct($queryString) {
        error_log($queryString);

        $this->disable_mode = false;
        $this->cache_disable_mode = false;
        $this->debug_mode = false;

        if ($queryString !== null) {
            $this->disable_mode = strpos($queryString, 'wovnDisable') !== false;
            if ($store->settings['debug_mode']) {
                $this->cache_disable_mode = strpos($queryString, 'wovnCacheDisable') !== false;
                $this->debug_mode = strpos($queryString, 'wovnDebugMode') !== false;
            }
        }
        
        error_log($this->disable_mode);
        error_log($this->cache_disable_mode);
        error_log($this->debug_mode);


        // TRANSLATE THIS TO PHP
        // String query = ((HttpServletRequest)request).getQueryString();
        // if (query != null) {
        //     this.disableMode = query.matches("(.*)wovnDisable(.*)");
        //     if (settings.debugMode) {
        //         this.cacheDisableMode = query.matches("(.*)wovnCacheDisable(.*)");
        //         this.debugMode = query.matches("(.*)wovnDebugMode(.*)");
        //     }
        // }
    }

    public function getDisableMode()
    {
        return $this->disable_mode;
    }

    public function getCacheDisableMode()
    {
        return $this->cache_disable_mode;
    }

    public function getDebugMode()
    {
        return $this->debug_mode;
    }
}