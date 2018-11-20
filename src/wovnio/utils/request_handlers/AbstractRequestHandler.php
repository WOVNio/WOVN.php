<?php
namespace Wovnio\Utils\RequestHandlers;

use \Wovnio\Wovnphp\Logger;

abstract class AbstractRequestHandler
{

    abstract protected function get($url, $timeout);
    abstract protected function post($url, $data, $timeout);

    public function sendRequest($method, $url, $data, $timeout = 1.0)
    {
        $response = null;
        $query = http_build_query($data);

        try {
            switch ($method) {
                case 'GET':
                    $response = $this->get($url . '?' . $query, $timeout);
                    break;
                case 'POST':
                    $response = $this->post($url, $query, $timeout);
                    break;
            }
        } catch (\Exception $e) {
            $errorContext = array('method' => $method, 'url' => $url, 'exception' => $e);

            \Wovnio\Wovnphp\Logger::get()->error('Failed to send {method} request to {url}: {exception}', $errorContext);
        }

        return $response;
    }
}
