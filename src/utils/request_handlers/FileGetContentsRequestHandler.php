<?php
  namespace Wovnio\Utils\RequestHandlers;

  require_once 'src/utils/request_handlers/AbstractRequestHandler.php';

  use Wovnio\Utils\RequestHandlers\AbstractRequestHandler;

  class FileGetContentsRequestHandler extends AbstractRequestHandler {
    private static function buildContext($http_context) {
      $context = stream_context_create(array(
        'http' => $http_context
      ));

      return $context;
    }

    private static function fileGetContents($url, $http_context) {
      $context = self::buildContext($http_context);
      $response = file_get_contents($url, false, $context);

      foreach ($http_response_header as $c => $h) {
        if (stristr($h, 'content-encoding') and stristr($h, 'gzip')) {
          $response = gzinflate(substr($response,10,-8));
        }
      }

      return $response;
    }

    protected function get($url, $timeout) {
      $http_context = array(
        'header' => 'Accept-Encoding: gzip\r\n',
        'method' => 'GET'
      );

      return self::fileGetContents($url, $http_context);
    }

    protected function post($url, $data, $timeout) {
      $http_context = array(
        'header' => 'Accept-Encoding: gzip\r\n',
        'method' => 'POST',
        'content' => $data
      );

      return self::fileGetContents($url, $http_context);
    }
  }
