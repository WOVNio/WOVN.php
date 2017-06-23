<?php
  namespace Wovnio\Utils\RequestHandlers;

  abstract class AbstractRequestHandler {

    abstract protected function get($url, $timeout);
    abstract protected function post($url, $data, $timeout);

    public function sendRequest($method, $url, $data, $timeout = 1.0) {
      $response = NULL;
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
        error_log('****** WOVN++ LOGGER :: Failed to get data from ' . $url . ': ' . $e->getMessage() . ' ******');
      }

      return $response;
    }
  }
