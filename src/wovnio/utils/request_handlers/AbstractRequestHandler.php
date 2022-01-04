<?php
namespace Wovnio\Utils\RequestHandlers;

use \Wovnio\Wovnphp\Logger;

abstract class AbstractRequestHandler
{
    abstract protected function post($url, $request_headers, $query, $timeout);

    public static function available()
    {
        return false;
    }

    public function __construct($store)
    {
        $this->store = $store;
    }

    public function sendRequest($method, $url, $data, $timeout = 1.0)
    {
        Logger::get()->info("[API call URL: {$url}.");
        $query = json_encode($data);
        if (function_exists('gzencode') && $this->store->compressApiRequests()) {
            // reduce networkIO to make request faster.
            $query = gzencode($query);
            $content_length = strlen($query);
            $uniqueId = Logger::get()->getUniqueId();
            $headers = array(
                'Content-Type: application/json',
                'Content-Encoding: gzip',
                "Content-Length: $content_length",
                "X-Request-Id: $uniqueId"
            );
        } else {
            $content_length = strlen($query);
            $headers = array(
                'Content-Type: application/json',
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

            \Wovnio\Wovnphp\Logger::get()->error('Failed to send {method} request to {url}: {exception}', $errorContext);
        }
    }
}
