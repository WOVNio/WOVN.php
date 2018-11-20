<?php
namespace Wovnio\Wovnphp\Tests\Integration;

class SSIWovnIndexSampleTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->baseDir = getcwd();
        $this->workspace = dirname(__FILE__) . '/wovn_index_sample_workspace';
        $this->paths = array();

        $indexFile = 'wovn_index.php';
        $sampleIndexFile = '../../../wovn_index_sample.php';
        $inclusionCode = '\$included\ =\ wovn_helper_include_by_paths\(\$paths\)\;';
        $ssiInclusionCode = '\$included\ =\ wovn_helper_include_by_paths_with_ssi\(\$paths\)\;';

        mkdir($this->workspace);
        chdir($this->workspace);

        mkdir('WOVN.php');
        mkdir('WOVN.php/src');
        mkdir('WOVN.php/src/wovnio');
        mkdir('WOVN.php/src/wovnio/wovnphp');

        // do nothing on interception, the goal of the tests are to test content
        // fetching
        $this->touch('WOVN.php/src/wovn_interceptor.php', '');
        copy('../../../src/wovn_helper.php', 'WOVN.php/src/wovn_helper.php');
        copy('../../../src/wovnio/wovnphp/SSI.php', 'WOVN.php/src/wovnio/wovnphp/SSI.php');

        exec('cp -r ../../../src WOVN.php/src');
        exec('sed -e s/^\ \ \ \ ' . $inclusionCode .'$/\ \ \ \ #\ ' . $inclusionCode . '/ ' . $sampleIndexFile . ' > ' . $indexFile . '.tmp');
        exec('sed -e s/^\ \ \ \ #\ ' . $ssiInclusionCode . '$/\ \ \ \ ' . $ssiInclusionCode . '/ ' . $indexFile . '.tmp' . ' > ' . $indexFile);
        unlink($indexFile . '.tmp');

        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
    }

    protected function tearDown()
    {
        // Normaly, this variable is undefined so revert it
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['SERVER_PROTOCOL']);

        chdir($this->baseDir);
        exec('rm -rf ' . $this->workspace);

        $this->paths = array();
    }

    public function testWithSSIAndPHP()
    {
        // If you are getting errors here and you modified wovn_index_sample, check the 'sed' commands above
        $this->touch('ssi.html', '<?php echo \'ssi\'; ?> <!--#include virtual="include.html" -->');
        $this->touch('include.html', 'include <!--#include virtual="nested.html" -->');
        $this->touch('nested.html');
        $this->assertEquals('ssi include This is nested.html', $this->runWovnIndex('/ssi.html'));
    }

    private function runWovnIndex($request_uri)
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'wovn.php';
        $_SERVER['REQUEST_URI'] = $request_uri;
        $_SERVER['QUERY_STRING'] = '';
        ob_start();
        include('wovn_index.php');
        return ob_get_clean();
    }

    private function touch($file, $content = null)
    {
        $content = $content !== null ? $content : 'This is ' . $file;
        file_put_contents($file, $content);
        array_push($this->paths, $file);
    }
}
