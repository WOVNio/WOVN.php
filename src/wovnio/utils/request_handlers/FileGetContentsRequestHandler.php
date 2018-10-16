<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'AbstractRequestHandler.php';

use Wovnio\Utils\RequestHandlers\AbstractRequestHandler;

class FileGetContentsRequestHandler extends AbstractRequestHandler
{
    private function buildContext($http_context)
    {
        $context = stream_context_create(array(
            'http' => $http_context
        ));

        return $context;
    }

    protected function get($url, $timeout)
    {
        $http_context = array(
            'header' => "Accept-Encoding: gzip\r\n",
            'method' => 'GET'
        );

        return $this->fileGetContents($url, $http_context);
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
        if (function_exists('gzencode')) {
            // reduce networkIO to make request faster.
            $data = gzencode($data);
            $content_length = strlen($data);
            $http_context = array(
                'header' => "Accept-Encoding: gzip\r\n"
                            . "Content-type: application/octet-stream\r\n"
                            . "Content-Length: $content_length",
                'method' => 'POST',
                'timeout' => $timeout,
                'content' => $data
            );
        } else {
            $content_length = strlen($data);
            $http_context = array(
                'header' => "Accept-Encoding: gzip\r\n"
                            . "Content-type: application/x-www-form-urlencoded\r\n"
                            . "Content-Length: $content_length",
                'method' => 'POST',
                'timeout' => $timeout,
                'content' => $data
            );
        }

        return $this->fileGetContents($url, $http_context);
    }

    public function fileGetContents($url, $http_context)
    {
        $context = $this->buildContext($http_context);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        foreach ($http_response_header as $c => $h) {
            if (stristr($h, 'content-encoding') and stristr($h, 'gzip')) {
                $response = gzinflate(substr($response, 10, -8));
            }
        }

        return $response;
    }
}
