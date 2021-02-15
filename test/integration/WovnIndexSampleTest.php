<?php
namespace Wovnio\Wovnphp\Tests\Integration;

/*
 * Notes
 * - @runInSeparateProcess: It's need if test call header() function
 */

use PHPUnit\Framework\TestCase;

class WovnIndexSampleTest extends TestCase
{
    protected function setUp()
    {
        $this->baseDir = getcwd();
        $this->workspace = dirname(__FILE__) . '/wovn_index_sample_workspace';
        $this->paths = array();

        $sampleIndexFile = '../../../wovn_index_sample.php';
        $indexFile = 'wovn_index.php';

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
        copy($sampleIndexFile, $indexFile);

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

    public function testWithFile()
    {
        $this->touch('index.html');
        $this->assertEquals('This is index.html', $this->runWovnIndex('/index.html'));
    }

    public function testDetectIndexPhp()
    {
        $this->touch('index.php');
        $this->assertEquals('This is index.php', $this->runWovnIndex('/'));
    }

    public function testDetectMultipleFiles()
    {
        $this->touch('index.html');
        $this->touch('index.php');
        $this->assertEquals('This is index.html', $this->runWovnIndex('/'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testLeadingDoubleDotsBad()
    {
        $this->touch('index.php');
        $this->assertEquals('Page Not Found', $this->runWovnIndex('/../../index.php'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testTrailingDoubleDotsOk()
    {
        $this->touch('index.php');
        $this->assertEquals('This is index.php', $this->runWovnIndex('/bird/..'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testSingleDotsOk()
    {
        $this->touch('index.php');
        $this->assertEquals('This is index.php', $this->runWovnIndex('/./././././'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testNotFoundFile()
    {
        $this->assertEquals('Page Not Found', $this->runWovnIndex('/index.html'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testNotFoundWithDetection()
    {
        $this->assertEquals('Page Not Found', $this->runWovnIndex('/'));
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
