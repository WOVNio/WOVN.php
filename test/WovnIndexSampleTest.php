<?php
/*
 * Notes
 * - @runInSeparateProcess: It's need if test call header() function
 */

class WovnIndexSampleTest extends PHPUnit_Framework_TestCase {
  protected function setUp() {
    chdir(dirname(__FILE__) . '/wovn_index_sample_workspace');
    copy('../../wovn_index_sample.php', 'wovn_index.php');
    copy('../../src/wovn_helper.php', 'WOVN.php/src/wovn_helper.php');
    $this->paths = array();
    $this->original_dir = getcwd();
    $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
  }

  protected function tearDown() {
    foreach($this->paths as $path) {
      unlink($path);
    }
    $this->paths = array();

    // Normaly, this variable is undefined so revert it
    unset($_SERVER['REQUEST_URI']);
    unset($_SERVER['SERVER_PROTOCOL']);

    // Avoid both exists wovn_index_sample.php and wovn_index.php for git diff
    unlink('wovn_index.php');

    chdir($this->original_dir);
  }

  public function testWithFile () {
    $this->touch('index.html');
    $this->assertEquals('This is index.html', $this->runWovnIndex('/index.html'));
  }

  public function testDetectIndexPhp () {
    $this->touch('index.php');
    $this->assertEquals('This is index.php', $this->runWovnIndex('/'));
  }

  public function testDetectMultipleFiles () {
    $this->touch('index.html');
    $this->touch('index.php');
    $this->assertEquals('This is index.html', $this->runWovnIndex('/'));
  }

  public function testInvalidPath () {
    $this->touch('index.php');
    $this->assertEquals('This is index.php', $this->runWovnIndex('/../../index.php'));
  }

  /**
   * @runInSeparateProcess
   */
  public function testNotFoundFile () {
    $this->assertEquals('Page Not Found', $this->runWovnIndex('/index.html'));
  }

  /**
   * @runInSeparateProcess
   */
  public function testNotFoundWithDetection () {
    $this->assertEquals('Page Not Found', $this->runWovnIndex('/'));
  }

  private function runWovnIndex($request_uri) {
    $_SERVER['REQUEST_URI'] = $request_uri;
    ob_start();
    include('wovn_index.php');
    return ob_get_clean();
  }

  private function touch($file) {
    file_put_contents($file, 'This is ' . $file);
    array_push($this->paths, $file);
  }
}
