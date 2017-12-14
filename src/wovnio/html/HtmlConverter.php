<?php

namespace Wovnio\Html;

require_once 'src/wovnio/wovnphp/Url.php';
require_once 'src/wovnio/wovnphp/Lang.php';

use Wovnio\Wovnphp\Url;
use Wovnio\Wovnphp\Lang;
use Wovnio\ModifiedVendor\simple_html_dom;

/**
 * Convert html via Simple HTML DOM Parser
 *
 * @see http://simplehtmldom.sourceforge.net/manual.htm
 */
class HtmlConverter
{
  public static $supported_encodings = array('UTF-8', 'EUC-JP', 'SJIS', 'eucJP-win', 'SJIS-win', 'JIS', 'ISO-2022-JP', 'ASCII');

  private $html;
  private $encoding;
  private $token;
  private $store;
  private $headers;


  /**
   * HtmlConverter constructor.
   * @param String $html
   * @param String $encoding
   * @param String $token project_token
   * @param Store $store
   * @param Headers $headers
   */
  public function __construct($html, $encoding, $token, $store = null, $headers = null)
  {
    $this->html = $html;
    $this->encoding = $encoding;
    $this->token = $token;
    $this->store = $store;
    $this->headers = $headers;
  }

  /**
   * Convert to appropriate HTML to send to Translation API
   * e.g.) remove wovn-ignore content
   *
   * @return array converted html and HtmlReplaceMarker
   */
  public function convertToAppropriateForApiBody($removeParts = true)
  {
    if ($this->encoding && in_array($this->encoding, self::$supported_encodings)) {
      $encoding = $this->encoding;
    } else {
      // Encoding detection uses 30% of execution time for this method.
      $encoding = mb_detect_encoding($this->html, self::$supported_encodings);
    }

    $dom = simple_html_dom::str_get_html($this->html, $encoding, false, false, $encoding, false);
    $this->insertSnippet($dom);
    if (isset($this->store) && isset($this->headers)) {
      $this->insertHreflangTags($dom);
    }

    $marker = new HtmlReplaceMarker();
    if ($removeParts) {
      $this->removeWovnIgnore($dom, $marker);
      $this->removeForm($dom, $marker);
      $this->removeScript($dom, $marker);
    }

    $converted_html = $dom->save();

    // Without clear(), Segmentation fault will be raised.
    // @see https://sourceforge.net/p/simplehtmldom/bugs/103/
    $dom->clear();
    unset($dom);

    if ($removeParts) {
      $converted_html = $this->removeBackendWovnIgnoreComment($converted_html, $marker);
    }
    return array($converted_html, $marker);
  }

  /**
   * Insert wovn's snippet to ensure snippet is always inserted.
   * When snippet is always inserted, do nothing
   *
   * @param simple_html_dom_node $dom
   */
  private function insertSnippet($dom)
  {
    foreach ($dom->find('script') as $node) {
      if (strpos($node->getAttribute('src'), '//j.wovn.io/') !== false ||
        strpos($node->getAttribute('src'), '//j.dev-wovn.io:3000/') !== false) {
        return;
      }
    }

    $token = $this->token;
    $insert_tags = array('head', 'body', 'html');
    foreach ($insert_tags as $tag_name) {
      $parents = $dom->find($tag_name);
      if (count($parents) > 0) {
        $parent = $parents[0];
        $parent->innertext = "<script src='//j.wovn.io/1' data-wovnio='key=$token' data-wovnio-type='backend_without_api' async></script>" . $parent->innertext;
        return;
      }
    }
  }

  /**
   * Insert hreflang tags for all supported_langs
   *
   * @param simple_html_dom_node $dom
   */
  private function insertHreflangTags($dom)
  {
    $lang_codes = $this->store->settings['supported_langs'];
    foreach ($dom->find('link') as $node) {
      $hreflangValue = $node->getAttribute('hreflang');
      if (in_array(Lang::getCode($hreflangValue), $lang_codes)) {
        $node->outertext = ''; // remove node
      }
    }

    $insert_tags = array('head', 'body', 'html');
    foreach ($insert_tags as $tag_name) {
      $parents = $dom->find($tag_name);
      if (count($parents) > 0) {
        $parent = $parents[0];
        $hreflangTags = array();

        foreach ($lang_codes as $lang_code) {
          $href = htmlentities(Url::addLangCode($this->headers->url, $this->store, $lang_code, $this->headers));
          array_push($hreflangTags, '<link rel="alternate" hreflang="' . Lang::iso639_1Normalization($lang_code) . '" href="' . $href . '">');
        }
        $parent->innertext = implode('', $hreflangTags) . $parent->innertext;
        return;
      }
    }
  }

  /**
   * Remove elements and children which have wovn-ignore attribute
   * @param simple_html_dom_node $dom
   * @param HtmlReplaceMarker $marker
   */
  private function removeWovnIgnore($dom, $marker)
  {
    foreach ($dom->find('[wovn-ignore]') as $element) {
      $this->putReplaceMarker($element, $marker);
    }
  }

  /**
   * Remove form elements to avoid CSRF token or flexible input's value
   *
   * @param simple_html_dom_node $dom
   * @param HtmlReplaceMarker $marker
   */
  private function removeForm($dom, $marker)
  {
    foreach ($dom->find('form') as $element) {
      $this->putReplaceMarker($element, $marker);
    }
    foreach ($dom->find('input[type=hidden]') as $element) {
      $originalText = $element->value;
      if (strpos($originalText, HtmlReplaceMarker::$key_prefix) !== false) {
        return;
      }

      $key = $marker->addValue($originalText);
      $element->value = $key;
    }
  }

  /**
   * Remove <script>
   * some script have random value for almost same purpose with CSRF
   *
   * @param simple_html_dom_node $dom
   * @param HtmlReplaceMarker $marker
   */
  private function removeScript($dom, $marker)
  {
    foreach ($dom->find('script') as $element) {
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
  private function removeBackendWovnIgnoreComment($html, $marker)
  {
    $ignoreMark = 'backend-wovn-ignore';

    return preg_replace_callback(
      "/(<!--\s*$ignoreMark\s*-->)(.+?)(<!--\s*\/$ignoreMark\s*-->)/s",
      function ($matches) use (&$marker) {
        $comment = $matches[2];
        $key = $marker->addCommentValue($comment);
        return $matches[1] . $key . $matches[3];
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
  private function putReplaceMarker($element, $marker)
  {
    $originalText = $element->innertext;
    if (strpos($originalText, HtmlReplaceMarker::$key_prefix) !== false) {
      return;
    }

    $key = $marker->addCommentValue($originalText);
    $element->innertext = $key;
  }
}
