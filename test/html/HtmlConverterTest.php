<?php
require_once 'src/wovnio/html/HtmlConverter.php';
require_once 'src/wovnio/html/HtmlReplaceMarker.php';
require_once 'src/wovnio/wovnphp/Utils.php';
require_once 'src/wovnio/wovnphp/Headers.php';
require_once 'src/wovnio/wovnphp/Lang.php';
require_once 'src/wovnio/wovnphp/Store.php';
require_once 'src/wovnio/wovnphp/Url.php';
require_once 'src/wovnio/modified_vendor/SimpleHtmlDom.php';
require_once 'src/wovnio/wovnphp/Url.php';
require_once 'src/wovnio/wovnphp/Lang.php';

use Wovnio\Html\HtmlConverter;
use Wovnio\Wovnphp\Utils;
use Wovnio\Html\HtmlReplaceMarker;
use Wovnio\ModifiedVendor\SimpleHtmlDom;

class HtmlConverterTest extends PHPUnit_Framework_TestCase
{
  private function getEnv($num = "")
  {
    $env = array();
    $file = parse_ini_file(dirname(__FILE__) . '/../mock_env' . $num . '.ini');
    $env = $file['env'];
    return $env;
  }

  public function testConvertAndRevertAtStackOverflow()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/real_html/stack_overflow.html');
    $token = 'toK3n';

    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html, $marker) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/real_html/stack_overflow_expected.html');
    $doc = new DOMDocument("1.0", "ISO-8859-15");
    $doc->loadHTML(mb_convert_encoding($expected_html_text, 'HTML-ENTITIES', "utf-8"));
    $expected_html = $doc->saveHTML();

    $actual_html_text = $marker->revert($translated_html);
    $doc = new DOMDocument("1.0", "ISO-8859-15");
    $doc->loadHTML(mb_convert_encoding($actual_html_text, 'HTML-ENTITIES', "utf-8"));
    $actual_html = $doc->saveHTML();

    $this->assertEquals($expected_html, $actual_html);
  }

  public function testConvertAndRevertAtYoutube()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/real_html/youtube.html');
    $token = 'toK3n';

    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html, $marker) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/real_html/youtube_expected.html');
    $doc = new DOMDocument("1.0", "ISO-8859-15");
    $doc->loadHTML(mb_convert_encoding($expected_html_text, 'HTML-ENTITIES', "utf-8"));
    $expected_html = $doc->saveHTML();

    $actual_html_text = $marker->revert($translated_html);
    $doc = new DOMDocument("1.0", "ISO-8859-15");
    $doc->loadHTML(mb_convert_encoding($actual_html_text, 'HTML-ENTITIES', "utf-8"));
    $actual_html = $doc->saveHTML();

    $this->assertEquals($expected_html, $actual_html);
  }

  public function testConvertAndRevertAtYelp()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/real_html/yelp.html');
    $token = 'toK3n';

    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html, $marker) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/real_html/yelp_expected.html');
    $doc = new DOMDocument("1.0", "ISO-8859-15");
    $doc->loadHTML(mb_convert_encoding($expected_html_text, 'HTML-ENTITIES', "utf-8"));
    $expected_html = $doc->saveHTML();

    $actual_html_text = $marker->revert($translated_html);
    $doc = new DOMDocument("1.0", "ISO-8859-15");
    $doc->loadHTML(mb_convert_encoding($actual_html_text, 'HTML-ENTITIES', "utf-8"));
    $actual_html = $doc->saveHTML();

    $this->assertEquals($expected_html, $actual_html);
  }

  public function testConvertAndRevertAtYahooJp()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/real_html/yahoo_jp.html');
    $token = 'toK3n';

    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html, $marker) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/real_html/yahoo_jp_expected.html');
    $doc = new DOMDocument("1.0", "ISO-8859-15");
    $doc->loadHTML(mb_convert_encoding($expected_html_text, 'HTML-ENTITIES', "utf-8"));
    $expected_html = $doc->saveHTML();

    $actual_html_text = $marker->revert($translated_html);
    $doc = new DOMDocument("1.0", "ISO-8859-15");
    $doc->loadHTML(mb_convert_encoding($actual_html_text, 'HTML-ENTITIES', "utf-8"));
    $actual_html = $doc->saveHTML();

    $this->assertEquals($expected_html, $actual_html);
  }

  public function testinsertSnippetAndHreflangTags()
  {
    $html = '<html><body><a>hello</a></body></html>';
    $token = 'toK3n';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['supported_langs'] = array('en', 'vi');
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html = "<html><body><link rel=\"alternate\" hreflang=\"en\" href=\"http://ja.localhost/t.php?hey=yo&amp;wovn=en\"><link rel=\"alternate\" hreflang=\"vi\" href=\"http://ja.localhost/t.php?hey=yo&amp;wovn=vi\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=toK3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;version=WOVN.php\" async></script><a>hello</a></body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testinsertSnippetAndHreflangTagsWithErrorMark()
  {
    $html = '<html><body><a>hello</a></body></html>';
    $token = 'toK3n';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['supported_langs'] = array('en', 'vi');
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->insertSnippetAndHreflangTags(true);

    $expected_html = "<html><body><link rel=\"alternate\" hreflang=\"en\" href=\"http://ja.localhost/t.php?hey=yo&amp;wovn=en\"><link rel=\"alternate\" hreflang=\"vi\" href=\"http://ja.localhost/t.php?hey=yo&amp;wovn=vi\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=toK3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script><a>hello</a></body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateBodyForApi()
  {
    $html = '<html><body><a>hello</a></body></html>';
    $token = 'toK3n';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['supported_langs'] = array('en', 'vi');
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->convertToAppropriateBodyForApi();

    $expected_html = "<html><body><link rel=\"alternate\" hreflang=\"en\" href=\"http://ja.localhost/t.php?hey=yo&amp;wovn=en\"><link rel=\"alternate\" hreflang=\"vi\" href=\"http://ja.localhost/t.php?hey=yo&amp;wovn=vi\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=toK3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script><a>hello</a></body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testInsertSnippetAndHreflangTagsWithEmptySupportedLangs()
  {
    $html = '<html><body><a>hello</a></body></html>';
    $token = 'toK3n';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->convertToAppropriateBodyForApi();

    $expected_html = "<html><body><link rel=\"alternate\" hreflang=\"en\" href=\"http://ja.localhost/t.php?hey=yo&amp;wovn=en\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=toK3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script><a>hello</a></body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testInsertSnippetAndHreflangTagsWithHead()
  {
    $html = '<html><head><title>TITLE</title></head><body><a>hello</a></body></html>';
    $token = 'toK3n';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->convertToAppropriateBodyForApi();

    $expected_html = "<html><head><link rel=\"alternate\" hreflang=\"en\" href=\"http://ja.localhost/t.php?hey=yo&amp;wovn=en\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=toK3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script><title>TITLE</title></head><body><a>hello</a></body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testInsertSnippetAndHreflangTagsWithoutBody()
  {
    $html = '<html>hello<a>world</a></html>';
    $token = 'toK3n';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['supported_langs'] = array();
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->convertToAppropriateBodyForApi();

    $expected_html = "<html><script src=\"//j.wovn.io/1\" data-wovnio=\"key=toK3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script>hello<a>world</a></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithEmptySupportedLangs()
  {
    $html = '<html><body><a>hello</a></body></html>';
    $token = 'toK3n';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->convertToAppropriateBodyForApi();

    $expected_html = "<html><body><link rel=\"alternate\" hreflang=\"en\" href=\"http://ja.localhost/t.php?hey=yo&amp;wovn=en\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=toK3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script><a>hello</a></body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithHead()
  {
    $html = '<html><head><title>TITLE</title></head><body><a>hello</a></body></html>';
    $token = 'toK3n';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->convertToAppropriateBodyForApi();

    $expected_html = "<html><head><link rel=\"alternate\" hreflang=\"en\" href=\"http://ja.localhost/t.php?hey=yo&amp;wovn=en\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=toK3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script><title>TITLE</title></head><body><a>hello</a></body></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithoutBody()
  {
    $html = '<html>hello<a>world</a></html>';
    $token = 'toK3n';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['supported_langs'] = array();
    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->convertToAppropriateBodyForApi();

    $expected_html = "<html><script src=\"//j.wovn.io/1\" data-wovnio=\"key=toK3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script>hello<a>world</a></html>";
    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithoutEncoding()
  {
    $html = mb_convert_encoding('<html>こんにちは</html>', 'SJIS');

    $token = 'toK3n';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, null, $token, $store, $headers);
    list($translated_html) = $converter->convertToAppropriateBodyForApi();

    $expected_html = "<html><link rel=\"alternate\" hreflang=\"en\" href=\"http://ja.localhost/t.php?hey=yo&amp;wovn=en\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=toK3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script>こんにちは</html>";
    $expected_html = mb_convert_encoding($expected_html, 'SJIS');

    $this->assertEquals($expected_html, $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithSupportedEncoding()
  {
    foreach (HtmlConverter::$supported_encodings as $encoding) {
      $html = mb_convert_encoding('<html>こんにちは</html>', $encoding);

      $token = 'toK3n';
      $env = $this->getEnv();
      list($store, $headers) = Utils::getStoreAndHeaders($env);
      $converter = new HtmlConverter($html, $encoding, $token, $store, $headers);
      list($translated_html) = $converter->convertToAppropriateBodyForApi();

      $expected_html = "<html><link rel=\"alternate\" hreflang=\"en\" href=\"http://ja.localhost/t.php?hey=yo&amp;wovn=en\"><script src=\"//j.wovn.io/1\" data-wovnio=\"key=toK3n&amp;backend=true&amp;currentLang=en&amp;defaultLang=en&amp;urlPattern=query&amp;langCodeAliases=[]&amp;version=WOVN.php\" data-wovnio-type=\"fallback_snippet\" async></script>こんにちは</html>";
      $expected_html = mb_convert_encoding($expected_html, $encoding);
      $this->assertEquals($expected_html, $translated_html);
    }
  }

  public function testConvertToAppropriateBodyForApiWithWovnIgnore()
  {
    $html = '<html><body><a wovn-ignore>hello</a></body></html>';
    $token = 'toK3n';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, null, $token, $store, $headers);
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeWovnIgnore');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body><a wovn-ignore>$keys[0]</a></body></html>", $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithMultipleWovnIgnore()
  {
    $html = '<html><body><a wovn-ignore>hello</a>ignore<div wovn-ignore>world</div></body></html>';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n', $store, $headers);
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeWovnIgnore');
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));
    $this->assertEquals("<html><body><a wovn-ignore>$keys[0]</a>ignore<div wovn-ignore>$keys[1]</div></body></html>", $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithForm()
  {
    $html = '<html><body><form>hello<input type="button" value="click"></form>world</body></html>';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n', $store, $headers);
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body><form>$keys[0]</form>world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithMultipleForm()
  {
    $html = '<html><body><form>hello<input type="button" value="click"></form>world<form>hello2<input type="button" value="click2"></form></body></html>';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n', $store, $headers);
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));
    $this->assertEquals("<html><body><form>$keys[0]</form>world<form>$keys[1]</form></body></html>", $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithFormAndWovnIgnore()
  {
    $html = '<html><body><form wovn-ignore>hello<input type="button" value="click"></form>world</body></html>';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n', $store, $headers);
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body><form wovn-ignore>$keys[0]</form>world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithHiddenInput()
  {
    $html = '<html><body><input type="hidden" value="aaaaa">world</body></html>';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n', $store, $headers);
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body><input type=\"hidden\" value=\"$keys[0]\">world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithHiddenInputMultipleTimes()
  {
    $html = '<html><body><input type="hidden" value="aaaaa">world<input type="hidden" value="aaaaa"></body></html>';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n', $store, $headers);
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeForm');
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));
    $this->assertEquals("<html><body><input type=\"hidden\" value=\"$keys[0]\">world<input type=\"hidden\" value=\"$keys[1]\"></body></html>", $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithScript()
  {
    $html = '<html><body><script>console.log("hello")</script>world</body></html>';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n', $store, $headers);
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeScript');
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body><script>$keys[0]</script>world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithMultipleScript()
  {
    $html = '<html><head><script>console.log("hello")</script></head><body>world<script>console.log("hello2")</script></body></html>';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n', $store, $headers);
    list($translated_html, $marker) = $this->executeConvert($converter, $html, 'UTF-8', '_removeScript');
    $keys = $marker->keys();

    $this->assertEquals(2, count($keys));
    $this->assertEquals("<html><head><script>$keys[0]</script></head><body>world<script>$keys[1]</script></body></html>", $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithComment()
  {
    $html = '<html><body>hello<!-- backend-wovn-ignore    -->ignored <!--/backend-wovn-ignore-->  world</body></html>';
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n', $store, $headers);
    list($translated_html, $marker) = $this->executeRemoveBackendWovnIgnoreComment($converter, $html);
    $keys = $marker->keys();

    $this->assertEquals(1, count($keys));
    $this->assertEquals("<html><body>hello<!-- backend-wovn-ignore    -->$keys[0]<!--/backend-wovn-ignore-->  world</body></html>", $translated_html);
  }

  public function testConvertToAppropriateBodyForApiWithMultipleComment()
  {
    $html = "<html><body>hello<!-- backend-wovn-ignore    -->ignored <!--/backend-wovn-ignore-->  world
line break
<!-- backend-wovn-ignore    -->

ignored2 

<!--/backend-wovn-ignore-->
bye
</body></html>";
    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $converter = new HtmlConverter($html, 'UTF-8', 'toK3n', $store, $headers);
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

  public function testInsertHreflang()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/real_html/stack_overflow_hreflang.html');
    $token = 'toK3n';

    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['default_lang'] = 'ja';
    $store->settings['supported_langs'] = array('en', 'vi');
    $store->settings['disable_api_request_for_default_lang'] = true;
    $store->settings['url_pattern_name'] = 'path';

    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/real_html/stack_overflow_hreflang_expected.html');

    $this->assertEquals($expected_html_text, $translated_html);
  }

  public function testInsertHreflangWithCustomLangCodes()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_with_custom_lang_codes.html');
    $token = 'toK3n';

    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['default_lang'] = 'en';
    $store->settings['supported_langs'] = array('en', 'vi', 'zh-CHS');
    $store->settings['disable_api_request_for_default_lang'] = true;
    $store->settings['url_pattern_name'] = 'path';
    $store->settings['custom_lang_aliases'] = array('zh-CHS' => 'custom_simple');

    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_with_custom_lang_codes_expected.html');

    $this->assertEquals($expected_html_text, $translated_html);
  }

  public function testInsertHreflangIntoHeadWithStyle()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_head_style.html');
    $token = 'toK3n';

    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['default_lang'] = 'ja';
    $store->settings['supported_langs'] = array('en', 'vi');
    $store->settings['disable_api_request_for_default_lang'] = true;
    $store->settings['url_pattern_name'] = 'path';

    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_head_style_expected.html');

    $this->assertEquals($expected_html_text, $translated_html);
  }

  public function testInsertHreflangIntoBodyTag()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_body.html');
    $token = 'toK3n';

    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['default_lang'] = 'ja';
    $store->settings['supported_langs'] = array('en', 'vi');
    $store->settings['disable_api_request_for_default_lang'] = true;
    $store->settings['url_pattern_name'] = 'path';

    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_body_expected.html');

    $this->assertEquals($expected_html_text, $translated_html);
  }

  public function testInsertSnippetForHtmlWithSnippetCode()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/basic_html/insert_snippet_when_already_exist.html');
    $token = 'toK3n';

    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['default_lang'] = 'ja';
    $store->settings['supported_langs'] = array('en', 'vi');
    $store->settings['disable_api_request_for_default_lang'] = true;
    $store->settings['url_pattern_name'] = 'path';

    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_snippet_when_already_exist_expected.html');

    $this->assertEquals($expected_html_text, $translated_html);
  }

  public function testInsertHreflangIntoHtmlTag()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/basic_html/insert_hreflang_html.html');
    $token = 'toK3n';

    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['default_lang'] = 'ja';
    $store->settings['supported_langs'] = array('en', 'vi');
    $store->settings['disable_api_request_for_default_lang'] = true;
    $store->settings['url_pattern_name'] = 'path';

    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_html_expected.html');

    $this->assertEquals($expected_html_text, $translated_html);
  }

  public function testInsertHreflangShouldRemoveExistHreflangTags()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/basic_html/insert_with_exist_hreflang.html');
    $token = 'toK3n';

    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['default_lang'] = 'ja';
    $store->settings['supported_langs'] = array('en', 'vi', 'zh-CHT', 'zh-CHS');
    $store->settings['disable_api_request_for_default_lang'] = true;
    $store->settings['url_pattern_name'] = 'path';

    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_with_exist_hreflang_expected.html');
    $this->assertEquals($expected_html_text, $translated_html);
  }

  public function testInsertHreflangHtmlEntities()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/real_html/stack_overflow_hreflang.html');
    $token = 'toK3n';

    $env = $this->getEnv('html_entities');
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['default_lang'] = 'ja';
    $store->settings['supported_langs'] = array('en', 'vi');
    $store->settings['disable_api_request_for_default_lang'] = true;
    $store->settings['url_pattern_name'] = 'path';

    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/real_html/stack_overflow_hreflang_html_entities_expected.html');

    $this->assertEquals($expected_html_text, $translated_html);
  }

  public function testInsertHreflangWithCustomLangAliasAndChinese()
  {
    libxml_use_internal_errors(true);
    $html = file_get_contents('test/fixtures/basic_html/insert_hreflang.html');
    $token = 'toK3n';

    $env = $this->getEnv();
    list($store, $headers) = Utils::getStoreAndHeaders($env);
    $store->settings['default_lang'] = 'ja';
    $store->settings['supported_langs'] = array('en', 'vi', 'zh-CHT', 'zh-CHS');
    $store->settings['custom_lang_aliases'] = array('en' => 'custom_en', 'zh-CHS' => 'custom_simple');
    $store->settings['url_pattern_name'] = 'path';

    $converter = new HtmlConverter($html, 'UTF-8', $token, $store, $headers);
    list($translated_html) = $converter->insertSnippetAndHreflangTags(false);

    $expected_html_text = file_get_contents('test/fixtures/basic_html/insert_hreflang_expected.html');

    $this->assertEquals($expected_html_text, $translated_html);
  }

  private function executeConvert($converter, $html, $charset, $name)
  {
    $dom = SimpleHtmlDom::str_get_html($html, $charset, false, false, $charset, false);
    $marker = new HtmlReplaceMarker();

    $method = new ReflectionMethod($converter, $name);
    $method->setAccessible(true);

    $dom->iterateAll(function ($node) use ($method, $converter, $marker) {
      $method->invoke($converter, $node, $marker);
    });

    $converted_html = $dom->save();
    $dom->clear();
    unset($dom);

    return array($converted_html, $marker);
  }

  private function executeRemoveBackendWovnIgnoreComment($converter, $html)
  {
    $marker = new HtmlReplaceMarker();

    $method = new ReflectionMethod($converter, 'removeBackendWovnIgnoreComment');
    $method->setAccessible(true);
    $converted_html = $method->invoke($converter, $html, $marker);

    return array($converted_html, $marker);
  }
}
