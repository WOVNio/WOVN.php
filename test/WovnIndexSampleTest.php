<?php
/*
 * Notes
 * - http_response_code(): FALSE will be returned if response_code is not provided and it is not invoked in a web server environemnt.
 * - @runInSeparateProcess: It's need if test call header() function
 */

class WovnIndexSampleTest extends PHPUnit_Framework_TestCase {
  protected function setUp() {
    chdir(dirname(__FILE__) . '/wovn_index_sample_workspace');
    copy('../../wovn_index_sample.php', 'wovn_index.php');
    $this->paths = [];
    $this->original_dir = getcwd();
    $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
  }

  protected function tearDown() {
    foreach($this->paths as $path) {
      unlink($path);
    }
    $this->paths = [];

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
    $this->assertEquals(false, http_response_code());
  }

  public function testDetectIndexPhp () {
    $this->touch('index.php');
    $this->assertEquals('This is index.php', $this->runWovnIndex('/'));
    $this->assertEquals(false, http_response_code());
  }

  public function testDetectMultipleFiles () {
    $this->touch('index.html');
    $this->touch('index.php');
    $this->assertEquals('This is index.html', $this->runWovnIndex('/'));
    $this->assertEquals(false, http_response_code());
  }

  /**
   * @runInSeparateProcess
   */
  public function testNotfound () {
    $this->assertEquals('', $this->runWovnIndex('/'));
    $this->assertEquals(404, http_response_code());
  }

  /**
   * @runInSeparateProcess
   */
  public function testInvalidPath () {
    $this->assertEquals('', $this->runWovnIndex('/../../etc/passwd'));
    $this->assertEquals(404, http_response_code());
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
