<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'AbstractRequestHandler.php';

use Wovnio\Utils\RequestHandlers\AbstractRequestHandler;

class FileGetContentsRequestHandler extends AbstractRequestHandler
{
    public static function available()
    {
        return ini_get('allow_url_fopen');
    }

    /**
     * @param $url
     * @param $request_headers
     * @param $data
     * @param $timeout
     * @return array
     */
    protected function post($url, $request_headers, $data, $timeout)
    {
        array_push($request_headers, 'Accept-Encoding: gzip');

        $http_context = stream_context_create(
            array(
                'http' => array(
                    'header' => implode("\r\n", $request_headers),
                    'method' => 'POST',
                    'timeout' => $timeout,
                    'content' => $data
                )
            )
        );
        list($response, $response_headers) = $this->fileGetContents($url, $http_context);

        if ($response === false) {
            $error_type = error_get_last() ? error_get_last()['type'] : '';
            $error_in_response = $response_headers[0];

            return array(null, $response_headers, "[fgc] Request failed ($error_type - $error_in_response)");
        }

        foreach ($response_headers as $c => $h) {
            if (stristr($h, 'content-encoding') and stristr($h, 'gzip')) {
                $response = gzinflate(substr($response, 10, -8));
            }
        }

        return array($response, $response_headers, null);
    }

    public function fileGetContents($url, $http_context)
    {
        $response = @file_get_contents($url, false, $http_context);
        return array($response, $http_response_header);
    }
}
