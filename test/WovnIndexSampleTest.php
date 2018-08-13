<?php
/*
 * Notes
 * - @runInSeparateProcess: It's need if test call header() function
 */

require_once(__DIR__ . '/../src/wovnio/wovnphp/SSI.php');

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

  protected function setUpSSI () {
    $indexFile = dirname(__FILE__) . '/wovn_index_sample_workspace/wovn_index.php';
    $inclusion = '\$included\ =\ wovn_helper_include_by_paths\(\$paths\)\;';
    $ssiInclusion = '\$included\ =\ wovn_helper_include_by_paths_with_ssi\(\$paths\)\;';

    exec('sed -i -e s/^' . $inclusion .'$/#\ ' . $inclusion . '/ ' . $indexFile);
    exec('sed -i -e s/^#\ ' . $ssiInclusion . '$/' . $ssiInclusion . '/ ' . $indexFile);
  }

  protected function tearDownSSI () {
    unlink('wovn_index.php-e');
  }

  public function testWithFile () {
    $this->touch('index.html');
    $this->assertEquals('This is index.html', $this->runWovnIndex('/index.html'));
  }

  public function testWithSSIAndPHP () {
    $this->setUpSSI();

    $this->touch('ssi.html', '<?php echo \'ssi\'; ?> <!--#include virtual="include.html" -->');
    $this->touch('include.html', 'include <!--#include virtual="nested.html" -->');
    $this->touch('nested.html');
    $this->assertEquals('ssi include This is nested.html', $this->runWovnIndex('/ssi.html'));

    $this->tearDownSSI();
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

  private function touch($file, $content=null) {
    $content = $content !== null ? $content : 'This is ' . $file;
    file_put_contents($file, $content);
    array_push($this->paths, $file);
  }
}
