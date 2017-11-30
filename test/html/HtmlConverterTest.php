<?php
require_once 'src/wovnio/html/HtmlConverter.php';
require_once 'src/wovnio/html/HtmlReplaceMarker.php';

require_once 'src/wovnio/modified_vendor/simple_html_dom.php';

use Wovnio\Html\HtmlConverter;
use Wovnio\Html\HtmlReplaceMarker;
use Wovnio\ModifiedVendor\simple_html_dom;

class HtmlConverterTest extends PHPUnit_Framework_TestCase {
  public function testConvertAndRevertAtStackOverflow() {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/real_html/stack_overflow.html');
    $token = 'toK3n';

    $converter = new HtmlConverter($html, 'UTF-8', $token);
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

    $converter = new HtmlConverter($html, 'UTF-8', $token);
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

    $converter = new HtmlConverter($html, 'UTF-8', $token);
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

    $converter = new HtmlConverter($html, 'UTF-8', $token);
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
    $converter = new HtmlConverter($html, 'UTF-8', $token);
    list($translated_html, $marker) = $converter->convertToAppropriateForApiBody();
    $keys = $marker->keys();

    $this->assertEquals(0, count($keys));

    $expected_html = "<html><body><script src='//j.wovn.io/1' data-wovnio='key=$token' data-wovnio-type='backend_without_api' async></script><a>hello</a></body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithHead() {
    $html = '<html><head><title>TITLE</title></head><body><a>hello</a></body></html>';
    $token = 'toK3n';
    $converter = new HtmlConverter($html, 'UTF-8', $token);
    list($translated_html, $marker) = $converter->convertToAppropriateForApiBody();
    $keys = $marker->keys();

    $this->assertEquals(0, count($keys));

    $expected_html = "<html><head><script src='//j.wovn.io/1' data-wovnio='key=$token' data-wovnio-type='backend_without_api' async></script><title>TITLE</title></head><body><a>hello</a></body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithoutBody() {
    $html = '<html>hello<a>world</a></html>';
    $token = 'toK3n';
    $converter = new HtmlConverter($html, 'UTF-8', $token);
    list($translated_html, $marker) = $converter->convertToAppropriateForApiBody();
    $keys = $marker->keys();

    $this->assertEquals(0, count($keys));

    $expected_html = "<html><script src='//j.wovn.io/1' data-wovnio='key=$token' data-wovnio-type='backend_without_api' async></script>hello<a>world</a></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithoutEncoding() {
    $html = mb_convert_encoding('<html>こんにちは</html>', 'SJIS');

    $token = 'toK3n';
    $converter = new HtmlConverter($html, null, $token);
    list($translated_html, $marker) = $converter->convertToAppropriateForApiBody();
    $keys = $marker->keys();

    $this->assertEquals(0, count($keys));

    $expected_html = "<html><script src='//j.wovn.io/1' data-wovnio='key=$token' data-wovnio-type='backend_without_api' async></script>こんにちは</html>";
    $expected_html = mb_convert_encoding($expected_html, 'SJIS');
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithSupportedEncoding() {
    foreach(HtmlConverter::$supported_encodings as $encoding) {
      $html = mb_convert_encoding('<html>こんにちは</html>', $encoding);

      $token = 'toK3n';
      $converter = new HtmlConverter($html, $encoding, $token);
      list($translated_html, $marker) = $converter->convertToAppropriateForApiBody();
      $keys = $marker->keys();

      $this->assertEquals(0, count($keys));

      $expected_html = "<html><script src='//j.wovn.io/1' data-wovnio='key=$token' data-wovnio-type='backend_without_api' async></script>こんにちは</html>";
      $expected_html = mb_convert_encoding($expected_html, $encoding);
      $this->assertEquals($expected_html, $translated_html);
    }
  }

  public function testConvertToAppropriateForApiBodyWithWovnIgnore() {
    $html = '<html><body><a wovn-ignore>hello</a></body></html>';
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', 'removeWovnIgnore');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body><a wovn-ignore>$keys[0]</a></body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithMultipleWovnIgnore() {
    $html = '<html><body><a wovn-ignore>hello</a>ignore<div wovn-ignore>world</div></body></html>';
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', 'removeWovnIgnore');
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));
    $this->assertEquals("<html><body><a wovn-ignore>$keys[0]</a>ignore<div wovn-ignore>$keys[1]</div></body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithForm() {
    $html = '<html><body><form>hello<input type="button" value="click"></form>world</body></html>';
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', 'removeForm');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body><form>$keys[0]</form>world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithMultipleForm() {
    $html = '<html><body><form>hello<input type="button" value="click"></form>world<form>hello2<input type="button" value="click2"></form></body></html>';
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', 'removeForm');
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));
    $this->assertEquals("<html><body><form>$keys[0]</form>world<form>$keys[1]</form></body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithFormAndWovnIgnore() {
    $html = '<html><body><form wovn-ignore>hello<input type="button" value="click"></form>world</body></html>';
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', 'removeForm');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body><form wovn-ignore>$keys[0]</form>world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithHiddenInput() {
    $html = '<html><body><input type="hidden" value="aaaaa">world</body></html>';
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', 'removeForm');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body><input type=\"hidden\" value=\"$keys[0]\">world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithHiddenInputMultipleTimes() {
    $html = '<html><body><input type="hidden" value="aaaaa">world<input type="hidden" value="aaaaa"></body></html>';
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', 'removeForm');
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));
    $this->assertEquals("<html><body><input type=\"hidden\" value=\"$keys[0]\">world<input type=\"hidden\" value=\"$keys[1]\"></body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithScript() {
    $html = '<html><body><script>console.log("hello")</script>world</body></html>';
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', 'removeScript');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body><script>$keys[0]</script>world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithMultipleScript() {
    $html = '<html><head><script>console.log("hello")</script></head><body>world<script>console.log("hello2")</script></body></html>';
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n');
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', 'removeScript');
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));
    $this->assertEquals("<html><head><script>$keys[0]</script></head><body>world<script>$keys[1]</script></body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithComment() {
    $html = '<html><body>hello<!-- backend-wovn-ignore    -->ignored <!--/backend-wovn-ignore-->  world</body></html>';
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n');
    list($translated_html, $marker) = $this->executeRemoveBackendWovnIgnoreComment($converter, $html);
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body>hello<!-- backend-wovn-ignore    -->$keys[0]<!--/backend-wovn-ignore-->  world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateForApiBodyWithMultipleComment() {
    $html = "<html><body>hello<!-- backend-wovn-ignore    -->ignored <!--/backend-wovn-ignore-->  world
line break
<!-- backend-wovn-ignore    -->

ignored2 

<!--/backend-wovn-ignore-->
bye
</body></html>";
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n');
    list($translated_html, $marker) = $this->executeRemoveBackendWovnIgnoreComment($converter, $html);
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));

    $expected_html = "<html><body>hello<!-- backend-wovn-ignore    -->$keys[0]<!--/backend-wovn-ignore-->  world
line break
<!-- backend-wovn-ignore    -->$keys[1]<!--/backend-wovn-ignore-->
bye
</body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  private function executeConvert($converter, $html, $charset, $name) {
    $dom = simple_html_dom::str_get_html($html, $charset, false, false, $charset, false);
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
