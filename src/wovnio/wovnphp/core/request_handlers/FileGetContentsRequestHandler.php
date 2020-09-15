<?php
namespace Wovnio\Wovnphp\Core\RequestHandlers;

require_once 'AbstractRequestHandler.php';

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
            preg_match('{HTTP\/\S*\s(\d{3})}', $response_headers[0], $match);

            $http_error_code = $match[1];

            return array(null, $response_headers, "[fgc] Request failed ($http_error_code)");
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
