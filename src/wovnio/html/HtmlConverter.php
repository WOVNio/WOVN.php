<?php

namespace Wovnio\Html;

use Wovnio\Wovnphp\Url;
use Wovnio\Wovnphp\Lang;

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
  public function __construct($html, $encoding, $token, $store, $headers)
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
  public function insertSnippetAndHreflangTags()
  {
    $this->html = $this->insertSnippet($this->html);
    $this->html = $this->insertHreflangTags($this->html);

    return array($this->html);

    // revert after memory optimization is finished
    if ($this->encoding && in_array($this->encoding, self::$supported_encodings)) {
      $encoding = $this->encoding;
    } else {
      // Encoding detection uses 30% of execution time for this method.
      $encoding = mb_detect_encoding($this->html, self::$supported_encodings);
    }

    $dom = simple_html_dom::str_get_html($this->html, $encoding, false, false, $encoding, false);
    error_log("cc-memory_get_usage:".memory_get_usage() / 1024);
    error_log("cc-memory_get_peak_usage:".memory_get_peak_usage() / 1024);
    // $this->insertSnippet($dom);
    // if (isset($this->store) && isset($this->headers)) {
    //   $this->insertHreflangTags($dom);
    // }

    $marker = new HtmlReplaceMarker();
    if ($removeParts) {
      // $this->removeWovnIgnore($dom, $marker);
      // $this->removeForm($dom, $marker);
      // $this->removeScript($dom, $marker);
    }

    // $self = $this;
    // $dom->iterateAll(function ($node) use ($self, $marker, $removeParts) {
    //   $self->insertSnippet2($node);
    //   if ($removeParts) {
    //     $self->removeWovnIgnore2($node, $marker);
    //     $self->removeForm2($node, $marker);
    //     $self->removeScript2($node, $marker);
    //   }
    // });

    $this->replaceDom($dom, $marker, $removeParts);

    $converted_html = $dom->save();

    // Without clear(), Segmentation fault will be raised.
    // @see https://sourceforge.net/p/simplehtmldom/bugs/103/

    if ($removeParts) {
      $converted_html = $this->removeBackendWovnIgnoreComment($converted_html, $marker);
    }
    // error_log("3****-memory_get_peak_usage:".memory_get_peak_usage() / 1024);

    $dom->clear();
    unset($dom);
    return array($converted_html, $marker);
  }

  private function replaceDom($dom, &$marker, $removeParts) {
    $self = $this;
    $adds_hreflang = isset($this->store) && isset($this->headers);

    $html = null;
    $head = null;
    $body = null;

    $dom->iterateAll(function ($node) use ($self, $marker, $removeParts, $adds_hreflang, &$html, &$head, &$body) {
      if (strtolower($node->tag) == "html") {
        $html = $node;
      } else if (strtolower($node->tag) == "head") {
        $head = $node;
      } else if (strtolower($node->tag) == "body") {
        $body = $node;
      }
      $self->removeSnippet($node);
      if ($adds_hreflang) {
        $self->removeHreflang($node);
      }
      if ($removeParts) {
        $self->removeWovnIgnore2($node, $marker);
        $self->removeForm2($node, $marker);
        // inside <script>, comment("<!--") is invalid
        // $self->removeScript2($node, $marker);
      }
    });

    $token = $this->token;
    $tags = array($head, $body, $html);
    foreach ($tags as $insert_tag) {
      if (is_null($insert_tag)) {
        continue;
      }

      $hreflangTags = array();
      if ($adds_hreflang) {
        $lang_codes = $this->store->settings['supported_langs'];
        foreach ($lang_codes as $lang_code) {
          $href = Url::addLangCode($this->headers->url, $this->store, $lang_code, $this->headers);
          array_push($hreflangTags, '<link rel="alternate" hreflang="' . Lang::iso639_1Normalization($lang_code) . '" href="' . $href . '">');
        }
      }

      $insert_tag->innertext = implode('', $hreflangTags) . "<script src=\"//j.wovn.io/1\" data-wovnio=\"key=$token\" data-wovnio-type=\"backend_without_api\" async></script>" . $insert_tag->innertext;

      break;
    }
  }

  /**
   * Insert wovn's snippet to ensure snippet is always inserted.
   * When snippet is always inserted, do nothing
   *
   * @param string $html
   */
  private function insertSnippet($html)
  {
    $snippet_regex = "/<script[^>]*src=[^>]*j\.[^ '\">]*wovn\.io[^>]*><\/script>/i";
    $html = $this->removeTagFromHtmlByRegex($html, $snippet_regex);

    $snippet_code = $this->buildSnippetCode();
    $parent_tags = array("(<head\s?.*?>)", "(<body\s?.*?>)", "(<html\s?.*?>)");

    return $this->insertAfterTag($parent_tags, $html, $snippet_code);
  }

  private function insertAfterTag($tag_names, $html, $insert_str)
  {
    foreach ($tag_names as $tag_name) {
      if (preg_match($tag_name, $html, $matches, PREG_OFFSET_CAPTURE)) {
        return substr_replace($html, $insert_str, $matches[0][1] + strlen($matches[0][0]), 0);
      }
    }
  }

  private function removeTagFromHtmlByRegex($html, $regex)
  {
    $result = $html;

    if (preg_match_all($regex, $result, $matches, PREG_OFFSET_CAPTURE)) {
      for ($i = count($matches[0]) - 1; $i >= 0; --$i) {
        $match = $matches[0][$i];
        $result = substr_replace($result, '', $match[1], strlen($match[0]));
      }
    }

    return $result;
  }

  private function buildSnippetCode()
  {
    $token = $this->token;
    $current_lang = $this->headers->lang();
    $default_lang = $this->store->settings['default_lang'];
    $url_pattern = $this->store->settings['url_pattern_name'];
    $lang_code_aliases_json = json_encode($this->store->settings['custom_lang_aliases']);
    $data_wovnio = htmlentities("key=$token&backend=true&currentLang=$current_lang&defaultLang=$default_lang&urlPattern=$url_pattern&langCodeAliases=$lang_code_aliases_json&version=WOVN.php");

    return "<script src=\"//j.wovn.io/1\" data-wovnio=\"$data_wovnio\" data-wovnio-type=\"backend_without_api\" async></script>";
  }

  /**
   * @param simple_html_dom_node $node
   */
  private function removeSnippet($node)
  {
    if (strtolower($node->tag) !== 'script') {
      return;
    }

    $src_value = $node->getAttribute('src');
    if (strpos($src_value, '//j.wovn.io/') !== false ||
      strpos($src_value, '//j.dev-wovn.io:3000/') !== false) {
      $node->outertext = ''; // remove node
    }
  }

  /**
   * Insert hreflang tags for all supported_langs
   *
   * @param string $html
   */
  private function insertHreflangTags($html)
  {
    if (isset($this->store->settings['supported_langs'])) {
      if (is_array($this->store->settings['supported_langs'])) {
        $lang_codes = $this->store->settings['supported_langs'];
      } else {
        $lang_codes = array($this->store->settings['supported_langs']);
      }
    } else {
      $lang_codes = array();
    }

    $lang_codes_with_pipe = implode('|', $lang_codes);
    $hreflang_regex = "/<link[^>]*hreflang=[^>]*($lang_codes_with_pipe)[^>]*\>/iU";
    $html = $this->removeTagFromHtmlByRegex($html, $hreflang_regex);

    $hreflangTags = array();
    foreach ($lang_codes as $lang_code) {
      $href = htmlentities(Url::addLangCode($this->headers->url, $this->store, $lang_code, $this->headers));
      array_push($hreflangTags, '<link rel="alternate" hreflang="' . Lang::iso639_1Normalization($lang_code) . '" href="' . $href . '">');
    }

    $parent_tags = array("(<head\s?.*?>)", "(<body\s?.*?>)", "(<html\s?.*?>)");

    return $this->insertAfterTag($parent_tags, $html, implode('', $hreflangTags));
  }

  private function removeHreflang($node) {
    if (strtolower($node->tag) != 'link') {
      return;
    }

    $lang_codes = $this->store->settings['supported_langs'];
    $hreflangValue = $node->getAttribute('hreflang');
    if (in_array(Lang::getCode($hreflangValue), $lang_codes)) {
      $node->outertext = ''; // remove node
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
   * @param simple_html_dom_node $node
   * @param HtmlReplaceMarker $marker
   */
  private function removeWovnIgnore2($node, $marker) {
    if ($node->getAttribute('wovn-ignore')) {
      $this->putReplaceMarker($node, $marker);
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
   * Remove form elements to avoid CSRF token or flexible input's value
   *
   * @param simple_html_dom_node $node
   * @param HtmlReplaceMarker $marker
   */
  private function removeForm2($node, $marker)
  {
    if (strtolower($node->tag) === 'form') {
      $this->putReplaceMarker($node, $marker);
      return;
    }

    if (strtolower($node->tag) === 'input' && strtolower($node->getAttribute('type')) == 'hidden') {
      $originalText = $node->getAttribute('value');
      if (strpos($originalText, HtmlReplaceMarker::$key_prefix) !== false) {
        return;
      }

      $key = $marker->addValue($originalText);
      $node->setAttribute('value', $key);
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
   * Remove <script>
   * some script have random value for almost same purpose with CSRF
   *
   * @param simple_html_dom_node $node
   * @param HtmlReplaceMarker $marker
   */
  private function removeScript2($node, $marker)
  {
    if (strtolower($node->tag) === 'script') {
      $this->putReplaceMarker($node, $marker);
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
