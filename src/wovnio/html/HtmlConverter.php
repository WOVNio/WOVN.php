<?php
namespace Wovnio\Html;

use Wovnio\ModifiedVendor\simple_html_dom;
/**
 * Convert html via Simple HTML DOM Parser
 *
 * @see http://simplehtmldom.sourceforge.net/manual.htm
 */
class HtmlConverter {
  public static $supported_encodings = array('UTF-8', 'EUC-JP', 'SJIS', 'eucJP-win', 'SJIS-win', 'JIS', 'ISO-2022-JP', 'ASCII');

  private $html;
  private $encoding;
  private $token;

  /**
   * HtmlConverter constructor.
   * @param String $html
   * @param String $encoding
   * @param String $token project_token
   */
  public function __construct($html, $encoding, $token) {
    $this->html = $html;
    $this->encoding = $encoding;
    $this->token = $token;
  }

  /**
   * Convert to appropriate HTML to send to Translation API
   * e.g.) remove wovn-ignore content
   *
   * @return array converted html and HtmlReplaceMarker
   */
  public function convertToAppropriateForApiBody() {
    if ($this->encoding && in_array($this->encoding, self::$supported_encodings)) {
      $encoding = $this->encoding;
    } else {
      // Encoding detection uses 30% of execution time for this method.
      $encoding = mb_detect_encoding($this->html, self::$supported_encodings);
    }

    $dom = simple_html_dom::str_get_html($this->html, $encoding, false, false, $encoding, false);
    $marker = new HtmlReplaceMarker();
    $this->insertSnippet($dom);
    $this->removeWovnIgnore($dom, $marker);
    $this->removeForm($dom, $marker);
    $this->removeScript($dom, $marker);

    $converted_html = $dom->save();

    // Without clear(), Segmentation fault will be raised.
    // @see https://sourceforge.net/p/simplehtmldom/bugs/103/
    $dom->clear();
    unset($dom);

    $converted_html = $this->removeBackendWovnIgnoreComment($converted_html, $marker);
    return array($converted_html, $marker);
  }

  /**
   * Insert wovn's snippet to ensure snippet is always inserted.
   * When snippet is always inserted, do nothing
   *
   * @param simple_html_dom_node $dom
   */
  private function insertSnippet($dom) {
    foreach ($dom->find('script') as $node) {
      if (strpos($node->getAttribute('src'), '//j.wovn.io/') !== false ||
        strpos($node->getAttribute('src'), '//j.dev-wovn.io:3000/') !== false) {
        return;
      }
    }

    $token = $this->token;
    $insert_tags = array('head', 'body', 'html');
    foreach($insert_tags as $tag_name) {
      $parents = $dom->find($tag_name);
      if (count($parents) > 0) {
        $parent = $parents[0];
        $parent->innertext = "<script src='//j.wovn.io/1' data-wovnio='key=$token' async></script>" . $parent->innertext;
        return;
      }
    }
  }

  /**
   * Remove elements and children which have wovn-ignore attribute
   * @param simple_html_dom_node $dom
   * @param HtmlReplaceMarker $marker
   */
  private function removeWovnIgnore($dom, $marker) {
    foreach ($dom->find('[wovn-ignore]') as $element)
    {
      $this->putReplaceMarker($element, $marker);
    }
  }

  /**
   * Remove form elements to avoid CSRF token or flexible input's value
   *
   * @param simple_html_dom_node $dom
   * @param HtmlReplaceMarker $marker
   */
  private function removeForm($dom, $marker) {
    foreach ($dom->find('form') as $element)
    {
      $this->putReplaceMarker($element, $marker);
    }
    foreach ($dom->find('input[type=hidden]') as $element)
    {
      $this->putReplaceMarker($element, $marker);
    }
  }

  /**
   * Remove <script>
   * some script have random value for almost same purpose with CSRF
   *
   * @param simple_html_dom_node $dom
   * @param HtmlReplaceMarker $marker
   */
  private function removeScript($dom, $marker) {
    foreach ($dom->find('script') as $element)
    {
      $this->putReplaceMarker($element, $marker);
    }
  }

  /**
   * Remove User specified content from <!--backend-wovn-ignore--> to <!--/backend-wovn-ignore'-->
   *
   * @param String $html
   * @param HtmlReplaceMarker $marker
   * @return String
   */
  private function removeBackendWovnIgnoreComment($html, $marker) {
    $ignoreMark = 'backend-wovn-ignore';

    return preg_replace_callback(
      "/<!--\s*$ignoreMark\s*-->.+?<!--\s*\/$ignoreMark\s*-->/s",
      function ($matches) use (&$marker) {
        $comment = $matches[0];
        $key = $marker->addValue($comment);
        return $key;
      },
      $html
    );
  }

  /**
   * Replace original to key (comment) to not send to translation API
   *
   * @param $element
   * @param HtmlReplaceMarker $marker
   */
  private function putReplaceMarker($element, $marker) {
    $originalText = $element->outertext;
    if (strpos($originalText, HtmlReplaceMarker::$key_prefix) !== false) {
      return;
    }

    $key = $marker->addValue($originalText);
    $element->outertext = $key;
  }
}
