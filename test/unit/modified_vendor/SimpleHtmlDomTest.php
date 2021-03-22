<?php
namespace Wovnio\Wovnphp\Tests\Unit\ModifiedVendor;

require_once 'src/wovnio/modified_vendor/SimpleHtmlDom.php';
require_once 'src/wovnio/modified_vendor/SimpleHtmlDomNode.php';

use Wovnio\ModifiedVendor\SimpleHtmlDom;

class SimpleHtmlDomTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAttribute()
    {
        $html = '<html><body>'.
            '<div'.
            ' class="this is class"'.
            ' data-dummy-1="double quote"'.
            ' data-dummy-2=\'single quote\''.
            ' data-dummy-3=without-quote'.
            ' data-dummy-4=""'.
            ' data-dummy-5=\'\''.
            ' data-dummy-6'.
            '></div>'.
            '</body></html>';
        $dom = SimpleHtmlDom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
        $nodes = $this->getTagNodes($dom, 'div');
        $this->assertEquals(1, count($nodes));
        $this->assertEquals('this is class', $nodes[0]->getAttribute('class'));
        $this->assertEquals('double quote', $nodes[0]->getAttribute('data-dummy-1'));
        $this->assertEquals('single quote', $nodes[0]->getAttribute('data-dummy-2'));
        $this->assertEquals('without-quote', $nodes[0]->getAttribute('data-dummy-3'));
        $this->assertEquals('', $nodes[0]->getAttribute('data-dummy-4'));
        $this->assertEquals('', $nodes[0]->getAttribute('data-dummy-5'));
        $this->assertEquals(true, $nodes[0]->getAttribute('data-dummy-6'));
    }

    public function testSetAttribute()
    {
        $html = '<html><body><div class="hello" data-dummy="dummy"></div></body>';
        $dom = SimpleHtmlDom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
        $nodes = $this->getTagNodes($dom, 'div');
        $this->assertEquals(1, count($nodes));
        foreach ($nodes as $node) {
            $node->setAttribute('class', 'world');
            $this->assertEquals('world', $node->getAttribute('class'));
            $this->assertEquals('<div class="world" data-dummy="dummy"></div>', $node->outertext);
        }

        $replaced_html = $dom->save();
        $this->assertEquals('<html><body><div class="world" data-dummy="dummy"></div></body>', $replaced_html);
    }

    public function testSetAttributeWithSingleQuote()
    {
        $html = "<html><body><div class='hello' data-dummy='dummy'></div></body>";
        $dom = SimpleHtmlDom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
        $nodes = $this->getTagNodes($dom, 'div');
        $this->assertEquals(1, count($nodes));
        foreach ($nodes as $node) {
            $node->setAttribute('class', 'world');
            $this->assertEquals('world', $node->getAttribute('class'));
            $this->assertEquals("<div class='world' data-dummy='dummy'></div>", $node->outertext);
        }

        $replaced_html = $dom->save();
        $this->assertEquals("<html><body><div class='world' data-dummy='dummy'></div></body>", $replaced_html);
    }

    public function testSetAttributeWithoutQuote()
    {
        $html = "<html><body><div class=hello data-dummy=dummy></div></body>";
        $dom = SimpleHtmlDom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
        $nodes = $this->getTagNodes($dom, 'div');
        $this->assertEquals(1, count($nodes));
        foreach ($nodes as $node) {
            $node->setAttribute('class', 'world');
            $this->assertEquals('world', $node->getAttribute('class'));
            $this->assertEquals("<div class=world data-dummy=dummy></div>", $node->outertext);
        }

        $replaced_html = $dom->save();
        $this->assertEquals("<html><body><div class=world data-dummy=dummy></div></body>", $replaced_html);
    }

    public function testSetAttributeWithoutValue()
    {
        $html = "<html><body><div wovn-ignore></div></body>";
        $dom = SimpleHtmlDom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
        $nodes = $this->getTagNodes($dom, 'div');
        $this->assertEquals(1, count($nodes));
        foreach ($nodes as $node) {
            $node->setAttribute('wovn-ignore', 'world');
            $this->assertEquals('world', $node->getAttribute('wovn-ignore'));
            $this->assertEquals('<div wovn-ignore="world"></div>', $node->outertext);
        }

        $replaced_html = $dom->save();
        $this->assertEquals('<html><body><div wovn-ignore="world"></div></body>', $replaced_html);
    }

    public function testSetAttributeWithMultipleAttribute()
    {
        $html = "<html><body><div class='hello' style=\"test-style\" data-test=test-data data-dummy1=\"dummy\" data-dummy2='dummy' data-dummy3=dummy></div></body>";
        $dom = SimpleHtmlDom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
        $nodes = $this->getTagNodes($dom, 'div');
        $this->assertEquals(1, count($nodes));
        foreach ($nodes as $node) {
            $node->setAttribute('class', 'multiple');
            $node->setAttribute('style', 'attribute');
            $node->setAttribute('data-test', 'world');
            $this->assertEquals('multiple', $node->getAttribute('class'));
            $this->assertEquals('attribute', $node->getAttribute('style'));
            $this->assertEquals('world', $node->getAttribute('data-test'));

            $this->assertEquals('<div class=\'multiple\' style="attribute" data-test=world data-dummy1="dummy" data-dummy2=\'dummy\' data-dummy3=dummy></div>', $node->outertext);
        }

        $replaced_html = $dom->save();
        $this->assertEquals('<html><body><div class=\'multiple\' style="attribute" data-test=world data-dummy1="dummy" data-dummy2=\'dummy\' data-dummy3=dummy></div></body>', $replaced_html);
    }

    public function testHasAttribute()
    {
        $html = '<html><body>'.
            '<div'.
            ' class="test1"'.
            ' data-dummy-1="test2"'.
            ' data-dummy-2=\'test3\''.
            ' data-dummy-3=test4'.
            ' data-dummy-4=""'.
            ' data-dummy-5=\'\''.
            ' data-dummy-6'.
            ' data-dummy-7=false'.
            '></div>'.
            '</body></html>';
        $dom = SimpleHtmlDom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
        $nodes = $this->getTagNodes($dom, 'div');
        $this->assertEquals(1, count($nodes));
        $this->assertEquals(true, $nodes[0]->hasAttribute('class'));
        $this->assertEquals(true, $nodes[0]->hasAttribute('data-dummy-1'));
        $this->assertEquals(true, $nodes[0]->hasAttribute('data-dummy-2'));
        $this->assertEquals(true, $nodes[0]->hasAttribute('data-dummy-3'));
        $this->assertEquals(true, $nodes[0]->hasAttribute('data-dummy-4'));
        $this->assertEquals(true, $nodes[0]->hasAttribute('data-dummy-5'));
        $this->assertEquals(true, $nodes[0]->hasAttribute('data-dummy-6'));
        $this->assertEquals(true, $nodes[0]->hasAttribute('data-dummy-7'));
        $this->assertEquals(false, $nodes[0]->hasAttribute('data-dummy-100'));
    }

    public function testHasAttributeWovnIgnore()
    {
        $html = '<html><body><div wovn-ignore>test</div></body></html>';
        $dom = SimpleHtmlDom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
        $nodes = $this->getTagNodes($dom, 'div');
        $this->assertEquals(1, count($nodes));
        $this->assertEquals(true, $nodes[0]->hasAttribute('wovn-ignore'));
        $this->assertEquals(false, $nodes[0]->hasAttribute('data-wovn-ignore'));
        $this->assertEquals(false, $nodes[0]->hasAttribute('wovn-ignore-attrs'));
        $this->assertEquals(false, $nodes[0]->hasAttribute('wovn-ignore-content'));
    }

    public function testHasAttributeWovnIgnoreAttrs()
    {
        $html = '<html><body><div wovn-ignore-attrs>test</div></body></html>';
        $dom = SimpleHtmlDom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
        $nodes = $this->getTagNodes($dom, 'div');
        $this->assertEquals(1, count($nodes));
        $this->assertEquals(true, $nodes[0]->hasAttribute('wovn-ignore-attrs'));
        $this->assertEquals(false, $nodes[0]->hasAttribute('wovn-ignore'));
        $this->assertEquals(false, $nodes[0]->hasAttribute('data-wovn-ignore'));
        $this->assertEquals(false, $nodes[0]->hasAttribute('wovn-ignore-content'));
    }

    private function getTagNodes($dom, $tag_name)
    {
        $self = $this;
        $nodes = array();
        $dom->iterateAll(function ($node) use ($self, $tag_name, &$nodes) {
            if (strtolower($node->tag) == strtolower($tag_name)) {
                $nodes[] = $node;
            }
        });

        return $nodes;
    }
}
