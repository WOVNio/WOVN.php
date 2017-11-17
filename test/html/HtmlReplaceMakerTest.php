<?php
require_once 'src/wovnio/html/HtmlReplaceMarker.php';

use Wovnio\Html\HtmlReplaceMarker;

class HtmlReplaceMarkerTest extends PHPUnit_Framework_TestCase {
  public function testAddValue() {
    $marker = new HtmlReplaceMarker();
    $this->assertEquals('<!-- __wovn-backend-ignored-key-0 -->', $marker->addValue('hello'));
  }

  public function testAddValueMultipleTimes() {
    $maker = new HtmlReplaceMarker();
    $this->assertEquals('<!-- __wovn-backend-ignored-key-0 -->', $maker->addValue('hello'));
    $this->assertEquals('<!-- __wovn-backend-ignored-key-1 -->', $maker->addValue('hello'));
    $this->assertEquals('<!-- __wovn-backend-ignored-key-2 -->', $maker->addValue('hello'));
    $this->assertEquals('<!-- __wovn-backend-ignored-key-3 -->', $maker->addValue('hello'));
  }

  public function testAddValueManyTimes() {
    $marker = new HtmlReplaceMarker();

    for ($i = 0; $i < 25; $i++) {
      $this->assertEquals("<!-- __wovn-backend-ignored-key-$i -->", $marker->addValue('hello'));
    }
  }

  public function testRevert() {
    $marker = new HtmlReplaceMarker();
    $original_html = '<html><body>hello<a>  replacement </a>world </body></html>';
    $key = $marker->addValue('hello');
    $new_html = str_replace('hello', $key, $original_html);
    $this->assertEquals("<html><body>$key<a>  replacement </a>world </body></html>", $new_html);
    $this->assertEquals($original_html, $marker->revert($new_html));
  }

  public function testRevertMultipleValue() {
    $marker = new HtmlReplaceMarker();
    $original_html = '<html><body>hello<a>  replacement </a>world </body></html>';
    $key1 = $marker->addValue('hello');
    $key2 = $marker->addValue('replacement');
    $key3 = $marker->addValue('world');
    $new_html = str_replace('hello', $key1, $original_html);
    $new_html = str_replace('replacement', $key2, $new_html);
    $new_html = str_replace('world', $key3, $new_html);
    $this->assertEquals("<html><body>$key1<a>  $key2 </a>$key3 </body></html>", $new_html);
    $this->assertEquals($original_html, $marker->revert($new_html));
  }

  public function testRevertManyValue() {
    $marker = new HtmlReplaceMarker();
    $original_html = '<html><body>';
    for ($i = 0; $i < 25; $i++) {
      $original_html .= "<a>hello_$i</a>";
    }
    $original_html .= '</body></html>';

    $new_html = $original_html;
    $keys = array();
    for ($i = 0; $i < 25; $i++) {
      $key = $marker->addValue("hello_$i");
      array_push($keys, $key);
      $new_html = str_replace("hello_$i", $key, $new_html);
    }

    $this->assertEquals(false, strpos($new_html,'hello'));
    $this->assertEquals($original_html, $marker->revert($new_html));
  }

  public function testRevertSameValue() {
    $marker = new HtmlReplaceMarker();
    $original_html = '<html><body>hello<a>hello</a>hello</body></html>';
    $key1 = $marker->addValue('hello');
    $key2 = $marker->addValue('hello');
    $key3 = $marker->addValue('hello');
    $new_html = "<html><body>$key1<a>$key2</a>$key3</body></html>";
    $this->assertEquals($original_html, $marker->revert($new_html));
  }
}
