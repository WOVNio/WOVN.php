<?php
namespace Wovnio\Wovnphp\Tests\Integration;

use Wovnio\Test\Helpers\Utils;

class WovnIndexSampleApacheTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        Utils::cleanUpDirectory($this->docRoot);

        $this->sourceDir  = dirname(__FILE__) . '/../..';
        $this->docRoot    = dirname(__FILE__) . '/../docroot';
        $this->installDir = $this->docRoot . '/WOVN.php';

        mkdir($this->installDir);
        exec(sprintf('cp -rf %s %s', $this->sourceDir . '/src', $this->installDir . '/src'));

        copy($this->sourceDir . '/wovn_index_sample.php', $this->docRoot . '/wovn_index.php');
        copy($this->sourceDir . '/htaccess_sample', $this->docRoot . '/.htaccess');

        mkdir($this->docRoot . '/v0');
        copy($this->sourceDir . '/test/fixtures/integration/v0/translation', $this->docRoot . '/v0/translation');

        chdir($this->docRoot);
    }

    protected function tearDown()
    {
        Utils::cleanUpDirectory($this->docRoot);
    }

    public function testNoneConfigurationDoNotChangeWovn()
    {
        $this->writeFile('index.html', '<html><head><title>Hello World</title></head><body>Welcome Start Page!!</body></html>');
        $response = $this->fetchURL('/index.html')->body;
        $this->assertEquals('<html><head><title>Hello World</title></head><body>Welcome Start Page!!</body></html>', $response);
    }

    public function testWithFile()
    {
        $this->writeFile('index.html', 'This is index.html');
        $this->assertEquals('This is index.html', $this->fetchURL('/index.html')->body);
    }

    public function testDetectIndexPhp()
    {
        $this->writeFile('index.php', 'This is index.php');
        $this->assertEquals('This is index.php', $this->fetchURL('/')->body);
    }

    public function testDetectMultipleFiles()
    {
        $this->writeFile('index.html', 'This is index.html');
        $this->writeFile('index.php', 'This is index.php');
        $this->assertEquals('This is index.html', $this->fetchURL('/')->body);
    }

    public function testLeadingDoubleDotsBad()
    {
        $this->writeFile('index.php', 'This is index.php');

        $response = $this->fetchURL('/../../index.php');

        $this->assertEquals(400, $response->statusCode);
    }

    public function testTrailingDoubleDotsOk()
    {
        $this->writeFile('index.php', 'This is index.php');

        $response = $this->fetchURL('/bird/..');

        $this->assertEquals('This is index.php', $response->body);
    }

    public function testSingleDotsOk()
    {
        $this->writeFile('index.php', 'This is index.php');

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

        $this->writeFile('index.php', $index_php_content);

        $this->setWovnIni($this->getWovnIni());
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

        $this->writeFile('amp.php', $amp_php_content);
        $this->setWovnIni($this->getWovnIni(array('check_amp' => 1)));

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

        $this->writeFile('static.html', $static_html_content);
        $this->setWovnIni($this->getWovnIni());

        $response = $this->fetchURL('/static.html?a=b');

        $this->assertEquals($expected, $response->body);
    }

    private function fetchURL($path)
    {
        return Utils::fetchURL('http://localhost' . $path);
    }

    private function setMockApiResponse($contents)
    {
        $this->writeFile('v0/translation', $contents);
    }

    private function setWovnIni($contents)
    {
        $this->writeFile('wovn.ini', $contents);
    }

    private function setHtaccess($contents)
    {
        $this->writeFile('.htaccess', $contents);
    }

    private function writeFile($file, $contents)
    {
        $filePath = $this->docRoot . '/' . $file;

        $content = is_array($contents) ? implode("\n", $contents) : $contents;
        file_put_contents($filePath, $content);
    }

    private function getWovnIni($options = array())
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
                foreach ($value as $v) {
                    $contents[] = "${name}[] = $v";
                }
            } else {
                $contents[] = "$name = $value";
            }
        }

        return implode("\n", $contents);
    }
}
