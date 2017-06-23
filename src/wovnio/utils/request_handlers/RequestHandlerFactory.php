<?php
  namespace Wovnio\Utils\RequestHandlers;

  require_once 'FileGetContentsRequestHandler.php';
  require_once 'CurlRequestHandler.php';

  use Wovnio\Utils\RequestHandlers\FileGetContentsRequestHandler;
  use Wovnio\Utils\RequestHandlers\CurlRequestHandler;

  class RequestHandlerFactory {
    static $instance = NULL;

    public static function set_instance($instance) {
      self::$instance = $instance;
    }

    public static function get() {
      if (self::$instance === NULL) {
        if (false && function_exists('curl_version')) {
          self::$instance = new CurlRequestHandler();
        } else {
          self::$instance = new FileGetContentsRequestHandler();
        }
      }

      return self::$instance;
    }
  }
