<?php
  namespace Wovnio\Wovnphp;

  class SSI {
    public static function include($includePath, $rootDir=null) {
      $rootDir = $rootDir ? $rootDir : dirname(dirname(dirname(dirname(__DIR__))));
      $limit = 10; // limit to 10 times nested SSI includes
      return self::includeRecursive($includePath, $rootDir, $limit);
    }

    public static function includeRecursive($includePath, $rootDir, &$limit) {
      $ssi_include_regexp = '/<!--#include virtual="(.+?)" -->/';
      $includeDir = dirname($includePath);
      $code = file_get_contents($includePath);

      while (preg_match($ssi_include_regexp, $code)) {
        $code = preg_replace_callback($ssi_include_regexp, function($match) use ($rootDir, $includeDir, $limit) {
          $path_and_query_string = explode('?', $match[1]);
          $ssi_path = $path_and_query_string[0];
          if (substr($ssi_path, 0, 1) == '/') {
            $path = $rootDir . '/' . $ssi_path;
          } else {
            $path = $includeDir . '/' . $ssi_path;
          }
          --$limit;
          if ($limit <= 0) {
              return '<!-- File does not include by limitation: ' . $path . '-->';
          }

          if (file_exists($path)) {
            return SSI::includeRecursive($path, $rootDir, $limit);
          }
          else {
            return '<!-- File not found: ' . $path . ' -->';
          }
        }, $code);
      }

      return $code;
    }
  }
