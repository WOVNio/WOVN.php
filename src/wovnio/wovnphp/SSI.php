<?php
  namespace Wovnio\Wovnphp;

  require_once(__DIR__ . '/../../wovn_helper.php');

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
      $fix_ssi_path = function($path, $dir) {
        if (!is_file($path)) {
          $candidates = wovn_helper_detect_paths($dir, $path);

          foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
              return $candidate;
            }
          }
        }

        return $path;
      };

      while (preg_match($ssi_include_regexp, $code)) {
        $code = preg_replace_callback($ssi_include_regexp, function($match) use ($rootDir, $includeDir, $limit, $fix_ssi_path) {
          $path_and_query_string = explode('?', $match[1]);
          $ssi_path = $path_and_query_string[0];
          if (substr($ssi_path, 0, 1) == '/') {
            $path = $fix_ssi_path($ssi_path, $rootDir);
          } else {
            $path = $fix_ssi_path($ssi_path, $includeDir);
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

      return ob_get_clean();
    }
  }
