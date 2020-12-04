<?php

namespace Wovnio\Wovnphp;

class RequestOptions
{
    /*
     * disableMode:
     *      - do nothing to the request
     */
    private $disableMode;
    /*
     * cacheDisableMode:
     *      - bypass cache for request to translation API
     * Only available if debugMode is also turned on server side.
     */
    private $cacheDisableMode;
    /*
     * debugMode:
     *      - activate extra debugging information.
     *      - send "debugMode=true" to translation API
     *      - bypass cache for request to translation API
     * Only available if debugMode is also turned on server side.
     */
    private $debugMode;


    public function __construct($queryString, $debugModeEnable)
    {
        $this->disableMode = false;
        $this->cacheDisableMode = false;
        $this->debugMode = false;

        if ($queryString !== null) {
            $this->disableMode = strpos($queryString, 'wovnDisable') !== false;
            if ($debugModeEnable) {
                $this->cacheDisableMode = strpos($queryString, 'wovnCacheDisable') !== false;
                $this->debugMode = strpos($queryString, 'wovnDebugMode') !== false;
            }
        }
    }

    public function getDisableMode()
    {
        return $this->disableMode;
    }

    public function getCacheDisableMode()
    {
        return $this->cacheDisableMode;
    }

    public function getDebugMode()
    {
        return $this->debugMode;
    }
}
