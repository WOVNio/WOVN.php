<?php
namespace Wovnio\Wovnphp\Tests\Integration;

require_once(__DIR__ . '/../helpers/Utils.php');
use Wovnio\Test\Helpers\Utils;

class WovnIndexSampleApacheTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->sourceDir  = realpath(dirname(__FILE__) . '/../..');
        $this->docRoot    = "/var/www/html";

        Utils::cleanUpDirectory($this->docRoot);

        // Copy WOVN.php
        mkdir("{$this->docRoot}/WOVN.php");
        exec("cp -rf {$this->sourceDir}/src {$this->docRoot}/WOVN.php/src");

        // Set .htaccess and wovn_index.php
        copy("{$this->sourceDir}/htaccess_sample", "{$this->docRoot}/.htaccess");
        copy($this->sourceDir . '/wovn_index_sample.php', $this->docRoot . '/wovn_index.php');

        // Set html-swapper mock
        mkdir("{$this->docRoot}/v0");
        copy("{$this->sourceDir}/test/fixtures/integration/v0/translation", "{$this->docRoot}/v0/translation");
    }

    protected function tearDown()
    {
        Utils::cleanUpDirectory($this->docRoot);
    }

    public function testNoneConfigurationDoNotChangeWovn()
    {
        Utils::writeFile($this->docRoot . '/' . 'index.html', '<html><head><title>Hello World</title></head><body>Welcome Start Page!!</body></html>');
        $response = $this->fetchURL('/index.html')->body;
        $this->assertEquals('<html><head><title>Hello World</title></head><body>Welcome Start Page!!</body></html>', $response);
    }

    public function testWithFile()
    {
        Utils::writeFile($this->docRoot . '/' . 'index.html', 'This is index.html');
        $this->assertEquals('This is index.html', $this->fetchURL('/index.html')->body);
    }

    public function testDetectIndexPhp()
    {
        Utils::writeFile($this->docRoot . '/' . 'index.php', 'This is index.php');
        $this->assertEquals('This is index.php', $this->fetchURL('/')->body);
    }

    public function testDetectMultipleFiles()
    {
        Utils::writeFile($this->docRoot . '/' . 'index.html', 'This is index.html');
        Utils::writeFile($this->docRoot . '/' . 'index.php', 'This is index.php');
        $this->assertEquals('This is index.html', $this->fetchURL('/')->body);
    }

    public function testLeadingDoubleDotsBad()
    {
        Utils::writeFile($this->docRoot . '/' . 'index.php', 'This is index.php');

        $response = $this->fetchURL('/../../index.php');

        $this->assertEquals(400, $response->statusCode);
    }

    public function testTrailingDoubleDotsOk()
    {
        Utils::writeFile($this->docRoot . '/' . 'index.php', 'This is index.php');

        $response = $this->fetchURL('/bird/..');

        $this->assertEquals('This is index.php', $response->body);
    }

    public function testSingleDotsOk()
    {
        Utils::writeFile($this->docRoot . '/' . 'index.php', 'This is index.php');

        $response = $this->fetchURL('/./././././');

        $this->assertEquals('This is index.php', $response->body);
    }

    public function testNotFoundFile()
    {
        $response = $this->fetchURL('/index.html');

        $this->assertEquals(404, $response->statusCode);
        $this->assertEquals('Page Not Found', $response->body);
    }

    public function testNotFoundWithDetection()
    {
        $response = $this->fetchURL('/');

        $this->assertEquals(404, $response->statusCode);
        $this->assertEquals('Page Not Found', $response->body);
    }


    public function testIncludedSnippetAndHreflang()
    {
        $index_php_content = <<<CONTENT
<?php require_once('WOVN.php/src/wovn_interceptor.php'); ?>
<html><head><link rel="alternate" hreflang="en" href="http://ja.AAAAA.com/" /></head><body>test</body></html>
CONTENT;
        $mock_api_response = <<<JSON
{"body": "<html lang=\"en\"><head><script src=\"//j.wovn.io/1\" async=\"true\" data-wovnio=\"key=zwBmtA&amp;backend=true&amp;currentLang=ja&amp;defaultLang=ja&amp;urlPattern=path&amp;langCodeAliases={}&amp;langParamName=wovn&amp;version=0.0.1\"> </script><link rel=\"alternate\" hreflang=\"en\" href=\"http://localhost/en/index.php\"><link rel=\"alternate\" hreflang=\"ja\" href=\"http://localhost/index.php\"></head><body>test</body></html>"}
JSON;
        $expected = <<<EXPECTED
<html lang="en"><head><script src="//j.wovn.io/1" async="true" data-wovnio="key=zwBmtA&amp;backend=true&amp;currentLang=ja&amp;defaultLang=ja&amp;urlPattern=path&amp;langCodeAliases={}&amp;langParamName=wovn&amp;version=0.0.1"> </script><link rel="alternate" hreflang="en" href="http://localhost/en/index.php"><link rel="alternate" hreflang="ja" href="http://localhost/index.php"></head><body>test</body></html>
EXPECTED;

        Utils::writeFile($this->docRoot . '/' . 'index.php', $index_php_content);

        $this->setWovnIni();
        $this->setMockApiResponse($mock_api_response);

        $response = $this->fetchURL('/index.php?wovn=ja');

        $this->assertEquals($expected, $response->body);
    }

    public function testCheckAmpOption()
    {
        $amp_php_content = <<<CONTENT
<?php require_once('WOVN.php/src/wovn_interceptor.php'); ?>
<html ⚡><head></head><body>test</body></html>
CONTENT;

        $expected = <<<EXPECTED
<html ⚡><head></head><body>test</body></html>
EXPECTED;

        Utils::writeFile($this->docRoot . '/' . 'amp.php', $amp_php_content);
        $this->setWovnIni(array('check_amp' => 1));

        $response = $this->fetchURL('/amp.php');

        $this->assertEquals($expected, $response->body);
    }

    public function testStaticHtml()
    {
        $static_html_content = <<<CONTENT
<html>
  <head></head>
  <body>
    <h1>Static Content</h1>
  </body>
</html>
CONTENT;

        $expected = <<<EXPECTED
<html>
  <head><link rel="alternate" hreflang="ja" href="http://localhost/static.html?a=b&amp;wovn=ja"><link rel="alternate" hreflang="fr" href="http://localhost/static.html?a=b&amp;wovn=fr"><link rel="alternate" hreflang="bg" href="http://localhost/static.html?a=b&amp;wovn=bg"><link rel="alternate" hreflang="en" href="http://localhost/static.html?a=b"><script src="//j.wovn.io/1" data-wovnio="key=Tek3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;langParamName=wovn" data-wovnio-info="version=WOVN.php_VERSION" async></script></head>
  <body>
    <h1>Static Content</h1>
  </body>
</html>
EXPECTED;

        Utils::writeFile($this->docRoot . '/' . 'static.html', $static_html_content);
        $this->setWovnIni(array());

        $response = $this->fetchURL('/static.html?a=b');

        $this->assertEquals($expected, $response->body);
    }

    private function fetchURL($path)
    {
        return Utils::fetchURL('http://localhost' . $path);
    }

    private function setMockApiResponse($contents)
    {
        Utils::writeFile($this->docRoot . '/' . 'v0/translation', $contents);
    }

    private function setWovnIni($options = array())
    {
        $defaultOptions = array(
            'project_token' => 'Tek3n',
            'url_pattern_name' => 'query',
            'default_lang' => 'en',
            'encoding' => 'UTF-8',
            'disable_api_request_for_default_lang' => 'true',
            'supported_langs' => array('ja', 'fr', 'bg', 'en'),
            'api_url' => 'http://localhost/v0/',
        );
        $options = array_merge($defaultOptions, $options);

        $contents = array();
        foreach ($options as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $v) {
                    if (is_string($key)) {
                        $contents[] = "${name}[$key] = $v";
                    } else {
                        $contents[] = "${name}[] = $v";
                    }
                }
            } else {
                $contents[] = "$name = $value";
            }
        }

        Utils::writeFile($this->docRoot . '/' . 'wovn.ini', $contents);
    }

    private function setHtaccess($contents)
    {
        Utils::writeFile($this->docRoot . '/' . '.htaccess', $contents);
    }
}
