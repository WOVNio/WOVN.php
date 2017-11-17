<?php
require_once 'src/wovnio/html/HtmlConverter.php';
require_once 'src/wovnio/html/HtmlReplaceMarker.php';

require_once 'src/vendor_download/simple_html_dom.php';

use Wovnio\Html\HtmlConverter;
use Wovnio\Html\HtmlReplaceMarker;

class HtmlConverterTest extends PHPUnit_Framework_TestCase {
  public function testConvertAndRevertAtStackOverflow() {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/real_html/stack_overflow.html');
    $token = 'toK3n';

    $converter = new HtmlConverter($html, $token);
    list($translated_html, $marker) = $converter->convertToAppropriateForApiBody();

    $expected_html_text = file_get_contents('test/fixtures/real_html/stack_overflow_expected.html');
    $doc = new DOMDocument( "1.0", "ISO-8859-15" );
    $doc->loadHTML(mb_convert_encoding($expected_html_text, 'HTML-ENTITIES', "utf-8"));
    $expected_html = $doc->saveHTML();

    $actual_html_text = $marker->revert($translated_html);
    $doc = new DOMDocument( "1.0", "ISO-8859-15" );
    $doc->loadHTML(mb_convert_encoding($actual_html_text, 'HTML-ENTITIES', "utf-8"));
    $actual_html = $doc->saveHTML();

    $this->assertEquals($expected_html, $actual_html);
  }

  public function testConvertAndRevertAtYoutube() {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/real_html/youtube.html');
    $token = 'toK3n';

    $converter = new HtmlConverter($html, $token);
    list($translated_html, $marker) = $converter->convertToAppropriateForApiBody();

    $expected_html_text = file_get_contents('test/fixtures/real_html/youtube_expected.html');
    $doc = new DOMDocument( "1.0", "ISO-8859-15" );
    $doc->loadHTML(mb_convert_encoding($expected_html_text, 'HTML-ENTITIES', "utf-8"));
    $expected_html = $doc->saveHTML();

    $actual_html_text = $marker->revert($translated_html);
    $doc = new DOMDocument( "1.0", "ISO-8859-15" );
    $doc->loadHTML(mb_convert_encoding($actual_html_text, 'HTML-ENTITIES', "utf-8"));
    $actual_html = $doc->saveHTML();

    $this->assertEquals($expected_html, $actual_html);
  }

  public function testConvertAndRevertAtYelp() {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/real_html/yelp.html');
    $token = 'toK3n';

    $converter = new HtmlConverter($html, $token);
    list($translated_html, $marker) = $converter->convertToAppropriateForApiBody();

    $expected_html_text = file_get_contents('test/fixtures/real_html/yelp_expected.html');
    $doc = new DOMDocument( "1.0", "ISO-8859-15" );
    $doc->loadHTML(mb_convert_encoding($expected_html_text, 'HTML-ENTITIES', "utf-8"));
    $expected_html = $doc->saveHTML();

    $actual_html_text = $marker->revert($translated_html);
    $doc = new DOMDocument( "1.0", "ISO-8859-15" );
    $doc->loadHTML(mb_convert_encoding($actual_html_text, 'HTML-ENTITIES', "utf-8"));
    $actual_html = $doc->saveHTML();

    $this->assertEquals($expected_html, $actual_html);
  }

  public function testConvertAndRevertAtYahooJp() {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/real_html/yahoo_jp.html');
    $token = 'toK3n';

    $converter = new HtmlConverter($html, $token);
    list($translated_html, $marker) = $converter->convertToAppropriateForApiBody();

    $expected_html_text = file_get_contents('test/fixtures/real_html/yahoo_jp_expected.html');
    $doc = new DOMDocument( "1.0", "ISO-8859-15" );
    $doc->loadHTML(mb_convert_encoding($expected_html_text, 'HTML-ENTITIES', "utf-8"));
    $expected_html = $doc->saveHTML();

    $actual_html_text = $marker->revert($translated_html);
    $doc = new DOMDocument( "1.0", "ISO-8859-15" );
    $doc->loadHTML(mb_convert_encoding($actual_html_text, 'HTML-ENTITIES', "utf-8"));
    $actual_html = $doc->saveHTML();

    $this->assertEquals($expected_html, $actual_html);
  }

  public function testConvertToAppropriateForApiBody() {
    $html = '<html><body><a>hello</a></body></html>';
    $token = 'toK3n';
    $converter = new HtmlConverter($html, $token);
    list($translated_html, $marker) = $converter->convertToAppropriateForApiBody();
    $keys = $marker->keys();

    $this->assertEquals(0, count($keys));

    $expected_html = "<html><body><script src='//j.wovn.io/1' data-wovnio='key=$token' async></script><a>hello</a></body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithHead() {
    $html = '<html><head><title>TITLE</title></head><body><a>hello</a></body></html>';
    $token = 'toK3n';
    $converter = new HtmlConverter($html, $token);
    list($translated_html, $marker) = $converter->convertToAppropriateForApiBody();
    $keys = $marker->keys();

    $this->assertEquals(0, count($keys));

    $expected_html = "<html><head><script src='//j.wovn.io/1' data-wovnio='key=$token' async></script><title>TITLE</title></head><body><a>hello</a></body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithoutBody() {
    $html = '<html>hello<a>world</a></html>';
    $token = 'toK3n';
    $converter = new HtmlConverter($html, $token);
    list($translated_html, $marker) = $converter->convertToAppropriateForApiBody();
    $keys = $marker->keys();

    $this->assertEquals(0, count($keys));

    $expected_html = "<html><script src='//j.wovn.io/1' data-wovnio='key=$token' async></script>hello<a>world</a></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithWovnIgnore() {
    $html = '<html><body><a wovn-ignore>hello</a></body></html>';
    $converter = new HtmlConverter($html, 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'removeWovnIgnore');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body>$keys[0]</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithMultipleWovnIgnore() {
    $html = '<html><body><a wovn-ignore>hello</a>ignore<div wovn-ignore>world</div></body></html>';
    $converter = new HtmlConverter($html, 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'removeWovnIgnore');
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));
    $this->assertEquals("<html><body>$keys[0]ignore$keys[1]</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithForm() {
    $html = '<html><body><form>hello<input type="button" value="click"></form>world</body></html>';
    $converter = new HtmlConverter($html, 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'removeForm');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body>$keys[0]world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithMultipleForm() {
    $html = '<html><body><form>hello<input type="button" value="click"></form>world<form>hello2<input type="button" value="click2"></form></body></html>';
    $converter = new HtmlConverter($html, 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'removeForm');
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));
    $this->assertEquals("<html><body>$keys[0]world$keys[1]</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithFormAndWovnIgnore() {
    $html = '<html><body><form wovn-ignore>hello<input type="button" value="click"></form>world</body></html>';
    $converter = new HtmlConverter($html, 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'removeForm');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body>$keys[0]world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithScript() {
    $html = '<html><body><script>console.log("hello")</script>world</body></html>';
    $converter = new HtmlConverter($html, 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'removeScript');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body>$keys[0]world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithMultipleScript() {
    $html = '<html><head><script>console.log("hello")</script></head><body>world<script>console.log("hello2")</script></body></html>';
    $converter = new HtmlConverter($html, 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'removeScript');
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));
    $this->assertEquals("<html><head>$keys[0]</head><body>world$keys[1]</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithComment() {
    $html = '<html><body>hello<!-- backend-wovn-ignore    -->ignored <!--/backend-wovn-ignore-->  world</body></html>';
    $converter = new HtmlConverter($html, 'toK3n');
    list($translated_html, $marker) = $this->executeRemoveBackendWovnIgnoreComment($converter, $html);
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body>hello$keys[0]  world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithMultipleComment() {
    $html = "<html><body>hello<!-- backend-wovn-ignore    -->ignored <!--/backend-wovn-ignore-->  world
line break
<!-- backend-wovn-ignore    -->

ignored2 

<!--/backend-wovn-ignore-->
bye
</body></html>";
    $converter = new HtmlConverter($html, 'toK3n');
    list($translated_html, $marker) = $this->executeRemoveBackendWovnIgnoreComment($converter, $html);
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));

    $expected_html = "<html><body>hello$keys[0]  world
line break
$keys[1]
bye
</body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  private function executeConvert($converter, $html, $name) {
    $dom = str_get_html($html);
    $marker = new HtmlReplaceMarker();

    $method = new ReflectionMethod($converter, $name);
    $method->setAccessible(true);
    $method->invoke($converter, $dom, $marker);

    $converted_html = $dom->save();
    $dom->clear();
    unset($dom);

    return array($converted_html, $marker);
  }

  private function executeRemoveBackendWovnIgnoreComment($converter, $html) {
    $marker = new HtmlReplaceMarker();

    $method = new ReflectionMethod($converter, 'removeBackendWovnIgnoreComment');
    $method->setAccessible(true);
    $converted_html = $method->invoke($converter, $html, $marker);

    return array($converted_html, $marker);
  }
}
