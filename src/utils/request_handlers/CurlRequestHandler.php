<?php
  namespace Wovnio\Utils\RequestHandlers;

  require_once 'src/utils/request_handlers/AbstractRequestHandler.php';

  use Wovnio\Utils\RequestHandlers\AbstractRequestHandler;

  class CurlRequestHandler extends AbstractRequestHandler {
    private static function buildSession($url, $options) {
      $curl_session = curl_init($url);

      foreach ($options as $opt => $val) {
        curl_setopt($curl_session, $opt, $val);
      }

      return $curl_session;
    }

    private static function curlExec($url, $options) {
      $curl_session = self::buildSession($url, $options);
      $response = curl_exec($curl_session);

      curl_close($curl_session);
      return $response;
    }

    protected function get($url, $timeout) {
      $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_ENCODING => 'gzip'
      );

      return self::curlExec($url, $options);
    }

    protected function post($url, $data, $timeout) {
      $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_ENCODING => 'gzip',
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $data
      );

      return self::curlExec($url, $options);
    }
  }
