<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'AbstractRequestHandler.php';

use Wovnio\Utils\RequestHandlers\AbstractRequestHandler;

class CurlRequestHandler extends AbstractRequestHandler
{
    private function buildSession($url, $options)
    {
        $curl_session = curl_init($url);

        foreach ($options as $opt => $val) {
            curl_setopt($curl_session, $opt, $val);
        }

        return $curl_session;
    }

    protected function get($url, $timeout)
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_ENCODING => 'gzip'
        );

        return $this->curlExec($url, $options);
    }

    /**
     * @param $url
     * @param $data
     * @param $timeout
     * @return string
     *
     * TODO: pass gzipped data at argument of $data.
     * Because `sendRequest` manage query and body, it's confusing to pass gzipped data to `sendRequest`
     */
    protected function post($url, $data, $timeout)
    {
        // reduce networkIO to make request faster.
        $data = gzencode($data);
        $content_length = strlen($data);
        $context = array(
            "Content-Type: application/octet-stream",
            "Content-Length: $content_length"
        );

        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $context
        );

        return $this->curlExec($url, $options);
    }

    public function curlExec($url, $options)
    {
        $curl_session = $this->buildSession($url, $options);
        $response = curl_exec($curl_session);

        curl_close($curl_session);
        return $response;
    }
}
