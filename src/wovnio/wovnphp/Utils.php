<?php
namespace Wovnio\Wovnphp;

class Utils {

  // will return the store and headers objects
  public static function getStoreAndHeaders(&$env) {
    $store = new Store;
    $headers = new Headers($env, $store);
    return array($store, $headers);
  }

  /**
   * Get the include directory and include path from wovn interceptor query and
   * ensure include path is absolute.
   *
   * @param $headers [Headers]
   * @param $store [Store]
   * @return array
   */
  public static function getIncludeDirAndIncludePath($headers, $store) {
    $env = $headers->env();
    $includeDir = $store->settings['include_dir'];
    $includePath = self::getEnv($env, array('REDIRECT_WOVNPHP_INCLUDE_PATH','REDIRECT_URL'));
    // subrequest add 'redirect:' after some RewriteRule are applied
    $includePath = preg_replace('/^redirect:/', '', $includePath);

    // make sure the file for the top page is corretly found
    if($includePath === '' || $includePath === '/' || $includePath === $includeDir) {
      $directoryIndex = $store->settings['directory_index'];
      $includePath = self::joinPath($includeDir, $directoryIndex);
      if (is_file($includePath)) {
        return array($includeDir, $includePath);
      }
      $existingFilePath = self::getExistingIndexFile($includeDir);
      if ($existingFilePath) {
        return array($includeDir, $existingFilePath);
      }
    } elseif (substr($includePath, -1) === '/') {
      // file is a directy name (ends with /)
      if(!preg_match('%^' . $includeDir . '%', $includePath)) {
        $includePath = self::joinPath($includeDir, $includePath);
      }
      if (!is_file($includePath)) {
        $directoryIndex = $store->settings['directory_index'];
        $oldIncludePath = $includePath;
        $includePath = self::joinPath($includePath, $directoryIndex);

        $existingFilePath = self::getExistingIndexFile($oldIncludePath);
        if ($existingFilePath) {
          return array($includeDir, $existingFilePath);
        }
      }
    } else {
      // make sure that $includePath is an absolute path
      if(!preg_match('%^' . $includeDir . '%', $includePath)) {
        $includePath = self::joinPath($includeDir, $includePath);
      }

      if (file_exists($includePath)) {
        return array($includeDir, $includePath);
      }
    }

    if ($store->getConfig('force_directory_index_path')) {
      return array($includeDir, $store->getConfig('force_directory_index_path'));
    }

    // if the file does not exist, then show the website 404 page
    if(!file_exists($includePath)) {
      $includePath = self::joinPath($includeDir, '404.php');
    }

    return array($includeDir, $includePath);
  }

  static function getExistingIndexFile($basePath) {
    if (!is_dir($basePath)) {
      return null;
    }

    $candidateFiles = array('index.php', 'index.html', 'index.htm');
    foreach($candidateFiles as $fileName) {
      $path = self::joinPath($basePath, $fileName);
      if (is_file($path)) {
        return $path;
      }
    }
    return null;
  }

  /**
   * @param $parentPath [String]
   * @param $childPath [String]
   * @return [String]
   * @example
   *  joinPath('/hello/', '/world/') #=> '/hello/world/'
   */
  static function joinPath($parentPath, $childPath) {
    return preg_replace('/\/+/', '/', $parentPath . '/'. $childPath);
  }

  public static function changeHeaders($buffer, $store) {
    if($store->settings['override_content_length']) {
      $buffer_length = strlen($buffer);
      //header cannot get at phpunit, so this code doesn't have any test..
      header('Content-Length: '.$buffer_length);
    }
  }

  private static function getEnv($env, $keys) {
    foreach ($keys as $key) {
      if (array_key_exists($key, $env)) {
        return $env[$key];
      }
    }
    return '';
  }
}
