<?php
  namespace Wovnio\Utils\RequestHandlers;

  require_once 'AbstractRequestHandler.php';

  use Wovnio\Utils\RequestHandlers\AbstractRequestHandler;

  class CurlRequestHandler extends AbstractRequestHandler {
    private function buildSession($url, $options) {
      $curl_session = curl_init($url);

      foreach ($options as $opt => $val) {
        curl_setopt($curl_session, $opt, $val);
      }

      return $curl_session;
    }

    protected function get($url, $timeout) {
      $options = array(
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_ENCODING => 'gzip'
      );

      return $this->curlExec($url, $options);
    }

    protected function post($url, $data, $timeout) {
      $content_length = strlen($data);
      $context = array(
        "Content-Type: application/x-www-form-urlencoded",
        "Content-Length: $content_length"
      );

      $options = array(
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_ENCODING => 'gzip',
        CURLOPT_POST => TRUE,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HEADER => TRUE,
        CURLOPT_HTTPHEADER => $context
      );

      return $this->curlExec($url, $options);
    }

    public function curlExec($url, $options) {
      $curl_session = $this->buildSession($url, $options);
      $response = curl_exec($curl_session);

      curl_close($curl_session);
      return $response;
    }
  }
