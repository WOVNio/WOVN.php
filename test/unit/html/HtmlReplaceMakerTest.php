<?php
namespace Wovnio\Wovnphp\Tests\Unit;

require_once 'src/wovnio/html/HtmlReplaceMarker.php';

use Wovnio\Html\HtmlReplaceMarker;

class HtmlReplaceMarkerTest extends \PHPUnit_Framework_TestCase
{
    public function testAddCommentValue()
    {
        $marker = new HtmlReplaceMarker();
        $this->assertEquals('<!-- __wovn-backend-ignored-key-0 -->', $marker->addCommentValue('hello'));
    }

    public function testAddCommentValueMultipleTimes()
    {
        $maker = new HtmlReplaceMarker();
        $this->assertEquals('<!-- __wovn-backend-ignored-key-0 -->', $maker->addCommentValue('hello'));
        $this->assertEquals('<!-- __wovn-backend-ignored-key-1 -->', $maker->addCommentValue('hello'));
        $this->assertEquals('<!-- __wovn-backend-ignored-key-2 -->', $maker->addCommentValue('hello'));
        $this->assertEquals('<!-- __wovn-backend-ignored-key-3 -->', $maker->addCommentValue('hello'));
    }

    public function testAddCommentValueManyTimes()
    {
        $marker = new HtmlReplaceMarker();

        for ($i = 0; $i < 25; $i++) {
            $this->assertEquals("<!-- __wovn-backend-ignored-key-$i -->", $marker->addCommentValue('hello'));
        }
    }

    public function testAddValueManyTimes()
    {
        $marker = new HtmlReplaceMarker();

        for ($i = 0; $i < 25; $i++) {
            $this->assertEquals("__wovn-backend-ignored-key-$i", $marker->addValue('hello'));
        }
    }

    public function testRevertCommentValue()
    {
        $marker = new HtmlReplaceMarker();
        $original_html = '<html><body>hello<a>  replacement </a>world </body></html>';
        $key = $marker->addCommentValue('hello');
        $new_html = str_replace('hello', $key, $original_html);
        $this->assertEquals("<html><body>$key<a>  replacement </a>world </body></html>", $new_html);
        $this->assertEquals($original_html, $marker->revert($new_html));
    }

    public function testRevertMultipleCommentValue()
    {
        $marker = new HtmlReplaceMarker();
        $original_html = '<html><body>hello<a>  replacement </a>world </body></html>';
        $key1 = $marker->addCommentValue('hello');
        $key2 = $marker->addCommentValue('replacement');
        $key3 = $marker->addCommentValue('world');
        $new_html = str_replace('hello', $key1, $original_html);
        $new_html = str_replace('replacement', $key2, $new_html);
        $new_html = str_replace('world', $key3, $new_html);
        $this->assertEquals("<html><body>$key1<a>  $key2 </a>$key3 </body></html>", $new_html);
        $this->assertEquals($original_html, $marker->revert($new_html));
    }

    public function testRevertMultipleValue()
    {
        $marker = new HtmlReplaceMarker();
        $original_html = '<html><body>'
        .'<input type="hidden" value="test1">'
        .'<input type="hidden" value="test2">'
        .'<input type="hidden" value="">'
        .'</body></html>';
        $key1 = $marker->addValue('test1');
        $key2 = $marker->addValue('test2');
        $key3 = $marker->addValue('');
        $new_html = '<html><body>'
        ."<input type=\"hidden\" value=\"$key1\">"
        ."<input type=\"hidden\" value=\"$key2\">"
        ."<input type=\"hidden\" value=\"$key3\">"
        .'</body></html>';
        $this->assertEquals($original_html, $marker->revert($new_html));
    }

    public function testRevertManyCommentValue()
    {
        $marker = new HtmlReplaceMarker();
        $original_html = '<html><body>';
        for ($i = 0; $i < 25; $i++) {
            $original_html .= "<a>hello_$i</a>";
        }
        $original_html .= '</body></html>';

        $new_html = $original_html;
        $keys = array();
        for ($i = 0; $i < 25; $i++) {
            $key = $marker->addCommentValue("hello_$i");
            array_push($keys, $key);
            $new_html = str_replace("hello_$i", $key, $new_html);
        }

        $this->assertEquals(false, strpos($new_html, 'hello'));
        $this->assertEquals($original_html, $marker->revert($new_html));
    }

    public function testRevertSameValue()
    {
        $marker = new HtmlReplaceMarker();
        $original_html = '<html><body>hello<a>hello</a>hello</body></html>';
        $key1 = $marker->addCommentValue('hello');
        $key2 = $marker->addCommentValue('hello');
        $key3 = $marker->addCommentValue('hello');
        $new_html = "<html><body>$key1<a>$key2</a>$key3</body></html>";
        $this->assertEquals($original_html, $marker->revert($new_html));
    }

    public function testRevertMixedCommentValueAndValue()
    {
        $marker = new HtmlReplaceMarker();
        $original_html = '<html><body>'
        .'<input type="hidden" value="test1">'
        .'<p>hello</p>'
        .'<input type="hidden" value="test2">'
        .'<p>world</p>'
        .'<input type="hidden" value="">'
        .'</body></html>';
        $key1 = $marker->addValue('test1');
        $key2 = $marker->addCommentValue('hello');
        $key3 = $marker->addValue('test2');
        $key4 = $marker->addCommentValue('world');
        $key5 = $marker->addValue('');
        $new_html = '<html><body>'
        ."<input type=\"hidden\" value=\"$key1\">"
        ."<p>$key2</p>"
        ."<input type=\"hidden\" value=\"$key3\">"
        ."<p>$key4</p>"
        ."<input type=\"hidden\" value=\"$key5\">"
        .'</body></html>';
        $this->assertEquals($original_html, $marker->revert($new_html));
    }
}
