<?php
namespace Wovnio\Wovnphp\Tests\Integration;

use PHPUnit\Framework\TestCase;

class SSIWovnIndexSampleTest extends TestCase
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

        // Turn on SSI
        exec("sed -e 's/wovn_use_ssi = false;/wovn_use_ssi = true;/' $sampleIndexFile > $indexFile");

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

    public function testWithSSIWhichIncludeMultipleSpaces()
    {
        $this->touch('ssi.html', '<?php echo \'ssi\'; ?> <!--#include  virtual="include.html" -->');
        $this->touch('include.html', 'include <!--#include  virtual="nested.html" -->');
        $this->touch('nested.html');
        $this->assertEquals('ssi include This is nested.html', $this->runWovnIndex('/ssi.html'));
    }

    public function testWithSSIIncludeWithQueryParams()
    {
        $ssi_php = <<<'CONTENT'
<?php echo "ssi\n"; ?>
<!--#include virtual="include.php?foo=1&bar=2" -->
<!--#include virtual="include.php?foo=3" -->
<!--#include virtual="include.php" -->
<?php echo "root query=foo:" . $_GET['foo'] . " bar:" . $_GET['bar']; ?>
CONTENT;
        $this->touch('ssi.php', $ssi_php);

        $included_php = <<<'CONTENT'
<?php $foo=isset($_GET['foo']) ? $_GET['foo'] : ''; $bar=isset($_GET['bar']) ? $_GET['bar'] : ''; echo "Included SSI query=foo:$foo bar:$bar"; ?>
CONTENT;
        $this->touch('include.php', $included_php);

        $expected_content = <<<'CONTENT'
ssi
Included SSI query=foo:1 bar:2
Included SSI query=foo:3 bar:
Included SSI query=foo: bar:
root query=foo:reqFoo bar:reqBar
CONTENT;
        $actual_content = $this->runWovnIndex('/ssi.php?foo=reqFoo&bar=reqBar');

        $this->assertEquals($expected_content, $actual_content);
    }

    private function runWovnIndex($request_uri)
    {
        $parsed_url = parse_url($request_uri);
        $queryParams = array();
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $queryParams);
        }

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'wovn.php';
        $_SERVER['REQUEST_URI'] = $request_uri;
        $_GET = $queryParams;
        
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
