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


    public function __construct($queryStringArray, $debugModeEnable)
    {
        $this->disableMode = false;
        $this->cacheDisableMode = false;
        $this->debugMode = false;

        if ($queryStringArray !== null) {
            $this->disableMode = array_key_exists('wovnDisable', $queryStringArray) && strcasecmp($queryStringArray['wovnDisable'], 'false') !== 0;
            if ($debugModeEnable) {
                $this->cacheDisableMode = array_key_exists('wovnCacheDisable', $queryStringArray) && strcasecmp($queryStringArray['wovnCacheDisable'], 'false') !== 0;
                $this->debugMode = array_key_exists('wovnDebugMode', $queryStringArray) && strcasecmp($queryStringArray['wovnDebugMode'], 'false') !== 0;
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
