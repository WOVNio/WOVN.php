<?php
class SSIWovnIndexSampleTest extends PHPUnit_Framework_TestCase {
  protected function setUp() {
    $indexFile = 'wovn_index.php';
    $sampleIndexFile = '../../../wovn_index_sample.php';
    $inclusionCode = '\$included\ =\ wovn_helper_include_by_paths\(\$paths\)\;';
    $ssiInclusionCode = '\$included\ =\ wovn_helper_include_by_paths_with_ssi\(\$paths\)\;';

    chdir(dirname(__FILE__) . '/wovn_index_sample_workspace');

    exec('sed -e s/^\ \ ' . $inclusionCode .'$/\ \ #\ ' . $inclusionCode . '/ ' . $sampleIndexFile . ' > ' . $indexFile . '.tmp');
    exec('sed -e s/^\ \ #\ ' . $ssiInclusionCode . '$/\ \ ' . $ssiInclusionCode . '/ ' . $indexFile . '.tmp' . ' > ' . $indexFile);
    unlink($indexFile . '.tmp');

    copy('../../../src/wovn_helper.php', 'WOVN.php/src/wovn_helper.php');
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

  public function testWithSSIAndPHP () {
    # If you are getting errors here and you modified wovn_index_sample, check the 'sed' commands above
    $this->touch('ssi.html', '<?php echo \'ssi\'; ?> <!--#include virtual="include.html" -->');
    $this->touch('include.html', 'include <!--#include virtual="nested.html" -->');
    $this->touch('nested.html');
    $this->assertEquals('ssi include This is nested.html', $this->runWovnIndex('/ssi.html'));
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
