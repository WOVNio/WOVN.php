<?php
namespace Wovnio\Utils\RequestHandlers;

use \Wovnio\Wovnphp\Logger;

abstract class AbstractRequestHandler
{
    abstract protected function post($url, $request_headers, $data, $timeout);

    public static function available()
    {
        return false;
    }

    public function sendRequest($method, $url, $data, $timeout = 1.0)
    {
        $formatted_data = http_build_query($data);
        $compressed_data = gzencode($formatted_data);
        $content_length = strlen($compressed_data);
        $headers = array(
            'Content-Type: application/octet-stream',
            "Content-Length: $content_length"
        );

        try {
            switch ($method) {
                case 'POST':
                    return $this->post($url, $headers, $compressed_data, $timeout);
                    break;
            }
        } catch (\Exception $e) {
            $errorContext = array('method' => $method, 'url' => $url, 'exception' => $e);

            \Wovnio\Wovnphp\Logger::get()->error('Failed to send {method} request to {url}: {exception}', $errorContext);
        }
    }
}
