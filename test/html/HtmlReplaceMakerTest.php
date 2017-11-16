<?php
require_once 'src/wovnio/html/HtmlReplaceMarker.php';

use Wovnio\Html\HtmlReplaceMarker;

class HtmlReplaceMarkerTest extends PHPUnit_Framework_TestCase {
  public function testAddValue() {
    $maker = new HtmlReplaceMarker();
    $this->assertEquals('<!-- __wovn-backend-ignored-key-0 -->', $maker->addValue('hello'));
  }

  public function testAddValueMultipleTimes() {
    $maker = new HtmlReplaceMarker();
    $this->assertEquals('<!-- __wovn-backend-ignored-key-0 -->', $maker->addValue('hello'));
    $this->assertEquals('<!-- __wovn-backend-ignored-key-1 -->', $maker->addValue('hello'));
    $this->assertEquals('<!-- __wovn-backend-ignored-key-2 -->', $maker->addValue('hello'));
    $this->assertEquals('<!-- __wovn-backend-ignored-key-3 -->', $maker->addValue('hello'));
  }

  public function testRevert() {
    $maker = new HtmlReplaceMarker();
    $original_html = '<html><body>hello<a>  replacement </a>world </body></html>';
    $key = $maker->addValue('hello');
    $new_html = str_replace('hello', $key, $original_html);
    $this->assertEquals("<html><body>$key<a>  replacement </a>world </body></html>", $new_html);
    $this->assertEquals($original_html, $maker->revert($new_html));
  }

  public function testRevertMultipleValue() {
    $maker = new HtmlReplaceMarker();
    $original_html = '<html><body>hello<a>  replacement </a>world </body></html>';
    $key1 = $maker->addValue('hello');
    $key2 = $maker->addValue('replacement');
    $key3 = $maker->addValue('world');
    $new_html = str_replace('hello', $key1, $original_html);
    $new_html = str_replace('replacement', $key2, $new_html);
    $new_html = str_replace('world', $key3, $new_html);
    $this->assertEquals("<html><body>$key1<a>  $key2 </a>$key3 </body></html>", $new_html);
    $this->assertEquals($original_html, $maker->revert($new_html));
  }

  public function testRevertSameValue() {
    $maker = new HtmlReplaceMarker();
    $original_html = '<html><body>hello<a>hello</a>hello</body></html>';
    $key1 = $maker->addValue('hello');
    $key2 = $maker->addValue('hello');
    $key3 = $maker->addValue('hello');
    $new_html = "<html><body>$key1<a>$key2</a>$key3</body></html>";
    $this->assertEquals($original_html, $maker->revert($new_html));
  }
}
