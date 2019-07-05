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
        if (function_exists('gzencode')) {
            // reduce networkIO to make request faster.
            $data = gzencode($data);
            $content_length = strlen($data);
            $headers = array(
                'Content-Type: application/octet-stream',
                "Content-Length: $content_length"
            );
        } else {
            $content_length = strlen($data);
            $headers = array(
                'Content-Type: application/x-www-form-urlencoded',
                "Content-Length: $content_length"
            );
        }

        try {
            switch ($method) {
                case 'POST':
                    return $this->post($url, $headers, $data, $timeout);
                    break;
            }
        } catch (\Exception $e) {
            $errorContext = array('method' => $method, 'url' => $url, 'exception' => $e);

            \Wovnio\Wovnphp\Logger::get()->error('Failed to send {method} request to {url}: {exception}', $errorContext);
        }
    }
}
