<?php
require_once 'src/wovnio/wovnphp/Utils.php';

use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Utils;

class UtilsTest extends PHPUnit_Framework_TestCase {
  private function getEnv($num="") {
    $env = array();
    $file = parse_ini_file(dirname(__FILE__) . '/mock_env' . $num . '.ini');
    $env = $file['env'];
    return $env;
  }

  public function testFunctionsExists() {
    $this->assertTrue(class_exists('Wovnio\Wovnphp\Utils'));
    $this->assertTrue(method_exists('Wovnio\Wovnphp\Utils', 'getStoreAndHeaders'));
    $this->assertTrue(method_exists('Wovnio\Wovnphp\Utils', 'getIncludeDirAndIncludePath'));
    $this->assertFalse(method_exists('Wovnio\Wovnphp\Utils', 'dispatchRequest'));
  }

  public function testGetStoreAndHeaders() {
    $env = $this->getEnv('_path');
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $this->assertEquals('Wovnio\Wovnphp\Store', get_class($store));
    $this->assertEquals('Wovnio\Wovnphp\Headers', get_class($headers));
  }

  public function testGetIncludeDirAndIncludePath() {
    $this->assertEquals('index.html'   , $this->path_helper(array('index.html')   , array('REDIRECT_WOVNPHP_INCLUDE_PATH' => '')));
    $this->assertEquals('index.html'   , $this->path_helper(array('index.html')   , array('REDIRECT_WOVNPHP_INCLUDE_PATH' => '/')));
    $this->assertEquals('foo.html'     , $this->path_helper(array('foo.html')     , array('REDIRECT_WOVNPHP_INCLUDE_PATH' => 'foo.html')));
    $this->assertEquals('nest/index.html', $this->path_helper(array('nest/index.html'), array('REDIRECT_WOVNPHP_INCLUDE_PATH' => 'nest/')));
    $this->assertEquals('nest/index.htm', $this->path_helper(array('nest/index.htm'), array('REDIRECT_WOVNPHP_INCLUDE_PATH' => 'nest/')));
    $this->assertEquals('nest/foo.html', $this->path_helper(array('nest/foo.html'), array('REDIRECT_WOVNPHP_INCLUDE_PATH' => 'nest/foo.html')));
    $this->assertEquals('nest/foo.html', $this->path_helper(array('nest/foo.html'), array('REDIRECT_WOVNPHP_INCLUDE_PATH' => '/nest/foo.html')));
    $this->assertEquals('nest/foo.html', $this->path_helper(array('nest/foo.html'), array('REDIRECT_WOVNPHP_INCLUDE_PATH' => '/tmp/nest/foo.html')));
    $this->assertEquals('404.php'      , $this->path_helper(array()));
    $this->assertEquals('404.php'      , $this->path_helper(array('nest/foo.html'), array('REDIRECT_WOVNPHP_INCLUDE_PATH' => 'foo.html')));
    $this->assertEquals('404.php'      , $this->path_helper(array('foo.html')     , array('REDIRECT_WOVNPHP_INCLUDE_PATH' => 'nest/foo.html')));

    $this->assertEquals('foo.html'     , $this->path_helper(array('foo.html')     , array('REDIRECT_URL' => 'foo.html')));
    $this->assertEquals('nest/foo.html', $this->path_helper(array('nest/foo.html'), array('REDIRECT_URL' => 'nest/foo.html')));
    $this->assertEquals('nest/index.php', $this->path_helper(array('nest/index.php'), array('REDIRECT_URL' => 'nest/')));
  }

  public function testGetIncludeDirAndIncludePathWithForceDirectoryIndex() {
    $this->assertEquals('foo.html', $this->path_helper(array('foo.html'), array('REDIRECT_URL' => 'foo.html'), array('force_directory_index_path' => '/default.php')));
    list($includeDir, $includePath) = $this->executeGetIncludeDirAndIncludePath(array('REDIRECT_URL' => 'foo.html'), array('force_directory_index_path' => '/default.php'));
    $this->assertEquals('/default.php', $includePath);
  }

  function path_helper($files, $additional_env = array(), $store_settings = array()) {
    $tmp_dir = '/tmp';
    $nest_dir = '/tmp/nest';
    Store::$config_dir = dirname(__FILE__);

    $path = '';
    try {
      mkdir($nest_dir);
      foreach ($files as $file) {
        file_put_contents ("$tmp_dir/$file", '');
      }

      list($includeDir, $includePath) =  $this->executeGetIncludeDirAndIncludePath($additional_env, $store_settings);
      $path = substr($includePath, strlen($tmp_dir) + 1);
    } catch (Exception $ex) {
      error_log($ex);
    }
    foreach ($files as $file) {
      unlink ("$tmp_dir/$file");
    }
    rmdir($nest_dir);

    return $path;
  }

  function executeGetIncludeDirAndIncludePath($additional_env, $store_settings) {
    $env = $this->getEnv('_path');
    foreach ($additional_env as $key => $value) {
      $env[$key] = $value;
    }

    list($store, $headers) = Utils::getStoreAndHeaders($env);
    foreach ($store_settings as $key => $value) {
      $store->settings[$key] = $value;
    }

    return Utils::getIncludeDirAndIncludePath($headers, $store);
  }
}
