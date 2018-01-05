<?php
require_once 'src/wovnio/modified_vendor/simple_html_dom.php';

use Wovnio\ModifiedVendor\simple_html_dom;

class simple_html_domTest extends PHPUnit_Framework_TestCase
{
  public function testGetAttribute() {
    $html = '<html><body><div class="hello" data-dummy="dummy"></div></body>';
    $dom = simple_html_dom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
    $nodes = $this->getTagNodes($dom, 'div');
    $this->assertEquals(1, count($nodes));
    foreach ($nodes as $node) {
      $this->assertEquals('hello', $node->getAttribute('class'));
    }
  }

  public function testGetAttributeWithSingleQuote() {
    $html = "<html><body><div class='hello' data-dummy='dummy'></div></body>";
    $dom = simple_html_dom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
    $nodes = $this->getTagNodes($dom, 'div');
    $this->assertEquals(1, count($nodes));
    foreach ($nodes as $node) {
      $this->assertEquals('hello', $node->getAttribute('class'));
    }
  }

  public function testGetAttributeWithoutQuote() {
    $html = "<html><body><div class=hello  data-dummy=dummy></div></body>";
    $dom = simple_html_dom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
    $nodes = $this->getTagNodes($dom, 'div');
    $this->assertEquals(1, count($nodes));
    foreach ($nodes as $node) {
      $this->assertEquals('hello', $node->getAttribute('class'));
    }
  }

  public function testGetAttributeWithoutValue() {
    $html = "<html><body><div wovn-ignore></div></body>";
    $dom = simple_html_dom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
    $nodes = $this->getTagNodes($dom, 'div');
    $this->assertEquals(1, count($nodes));
    foreach ($nodes as $node) {
      $this->assertEquals(true, $node->getAttribute('wovn-ignore'));
    }
  }

  public function testGetAttributeWithMultipleAttribute() {
    $html = "<html><body><div class='hello' style=\"test-style\" data-test=test-data data-dummy1=\"dummy\" data-dummy2='dummy' data-dummy3=dummy></div></body>";
    $dom = simple_html_dom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
    $nodes = $this->getTagNodes($dom, 'div');
    $this->assertEquals(1, count($nodes));
    foreach ($nodes as $node) {
      $this->assertEquals('hello', $node->getAttribute('class'));
      $this->assertEquals('test-style', $node->getAttribute('style'));
      $this->assertEquals('test-data', $node->getAttribute('data-test'));
    }
  }

  public function testSetAttribute() {
    $html = '<html><body><div class="hello" data-dummy="dummy"></div></body>';
    $dom = simple_html_dom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
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

  public function testSetAttributeWithSingleQuote() {
    $html = "<html><body><div class='hello' data-dummy='dummy'></div></body>";
    $dom = simple_html_dom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
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

  public function testSetAttributeWithoutQuote() {
    $html = "<html><body><div class=hello data-dummy=dummy></div></body>";
    $dom = simple_html_dom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
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

  public function testSetAttributeWithoutValue() {
    $html = "<html><body><div wovn-ignore></div></body>";
    $dom = simple_html_dom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
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

  public function testSetAttributeWithMultipleAttribute() {
    $html = "<html><body><div class='hello' style=\"test-style\" data-test=test-data data-dummy1=\"dummy\" data-dummy2='dummy' data-dummy3=dummy></div></body>";
    $dom = simple_html_dom::str_get_html($html, 'UTF-8', false, false, 'UTF-8', false);
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

  private function getTagNodes($dom, $tag_name) {
    $self = $this;
    $nodes = array();
    $dom->iterateAll(function ($node) use($self, $tag_name, &$nodes){
      if (strtolower($node->tag) == strtolower($tag_name)) {
        $nodes[] = $node;
      }
    });

    return $nodes;
  }
}
