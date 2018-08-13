<?php
  namespace Wovnio\Wovnphp;

  class SSI {
    public static function readFile($includePath, $rootDir=null) {
      $rootDir = $rootDir ? $rootDir : dirname(dirname(dirname(dirname(__DIR__))));
      $limit = 10; // limit to 10 times nested SSI includes
      return self::readFileRecursive($includePath, $rootDir, $limit);
    }

    public static function readFileRecursive($includePath, $rootDir, &$limit) {
      $ssi_include_regexp = '/<!--#include virtual="(.+?)"\s*-->/';
      $includeDir = dirname($includePath);
      $code = self::get_contents($includePath);

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
            return SSI::readFileRecursive($path, $rootDir, $limit);
          }
          else {
            return '<!-- File not found: ' . $path . ' -->';
          }
        }, $code);
      }

      return $code;
    }

    private static function get_contents($includePath) {
      ob_start();
      include $includePath;
      $contents = ob_get_contents();
      ob_end_clean();

      return $contents;
    }
  }
