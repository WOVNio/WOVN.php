<?php
namespace Wovnio\Wovnphp\Core\RequestHandlers;

use \Wovnio\Wovnphp\Logger;

abstract class AbstractRequestHandler
{
    abstract protected function post($url, $request_headers, $query, $timeout);

    public static function available()
    {
        return false;
    }

    public function sendRequest($method, $url, $data, $timeout = 1.0)
    {
        $query = http_build_query($data);
        if (function_exists('gzencode')) {
            // reduce networkIO to make request faster.
            $query = gzencode($query);
            $content_length = strlen($query);
            $headers = array(
                'Content-Type: application/octet-stream',
                "Content-Length: $content_length"
            );
        } else {
            $content_length = strlen($query);
            $headers = array(
                'Content-Type: application/x-www-form-urlencoded',
                "Content-Length: $content_length"
            );
        }

        try {
            switch ($method) {
                case 'POST':
                    return $this->post($url, $headers, $query, $timeout);
            }
        } catch (\Exception $e) {
            $errorContext = array('method' => $method, 'url' => $url, 'exception' => $e);
            Logger::get()->error('Failed to send {method} request to {url}: {exception}', $errorContext);
        }
    }
}
