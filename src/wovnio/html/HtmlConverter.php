<?php

namespace Wovnio\Html;

use Wovnio\Wovnphp\Url;
use Wovnio\Wovnphp\Lang;
use Wovnio\Wovnphp\Store;
use Wovnio\Wovnphp\Headers;
use Wovnio\ModifiedVendor\SimpleHtmlDom;
use Wovnio\ModifiedVendor\SimpleHtmlDomNode;

/**
 * Convert html via Simple HTML DOM Parser
 *
 * @see http://simplehtmldom.sourceforge.net/manual.htm
 */
class HtmlConverter
{
    public static $supportedEncodings = array('UTF-8', 'EUC-JP', 'SJIS', 'eucJP-win', 'SJIS-win', 'JIS', 'ISO-2022-JP', 'ASCII');

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

    public function insertSnippetAndHreflangTags($adds_backend_error_mark)
    {
        $this->html = $this->insertSnippet($this->html, $adds_backend_error_mark);
        $this->html = $this->insertHreflangTags($this->html);

        if ($this->isNoindexLang($this->headers->lang())) {
            $this->html = $this->insertNoindex($this->html);
        }

        $marker = new HtmlReplaceMarker();
        return array($this->html, $marker);
    }

    /**
     * Convert to appropriate HTML to send to Translation API
     * e.g.) remove wovn-ignore content
     *
     * @return array converted html and HtmlReplaceMarker
     */
    public function convertToAppropriateBodyForApi()
    {
        if ($this->encoding && in_array($this->encoding, self::$supportedEncodings)) {
            $encoding = $this->encoding;
        } else {
            // Encoding detection uses 30% of execution time for this method.
            $encoding = mb_detect_encoding($this->html, self::$supportedEncodings);
        }
        $marker = new HtmlReplaceMarker();
        $converted_html = $this->html;

        $dom = SimpleHtmlDom::str_get_html($this->html, $encoding, false, false, $encoding, false);
        if ($dom) {
            $this->replaceDom($dom, $marker);

            $converted_html = $dom->save();
            $converted_html = $this->removeBackendWovnIgnoreComment($converted_html, $marker);

            // Without clear(), Segmentation fault will be raised.
            // @see https://sourceforge.net/p/simplehtmldom/bugs/103/
            $dom->clear();
            unset($dom);
        }

        return array($converted_html, $marker);
    }

    private function replaceDom($dom, &$marker)
    {
        $self = $this;
        $adds_hreflang = isset($this->store) && isset($this->headers);

        $html = null;
        $head = null;
        $body = null;

        $dom->iterateAll(function ($node) use (&$self, $marker, $adds_hreflang, &$html, &$head, &$body) {
            if (strtolower($node->tag) == "html") {
                $html = $node;
            } elseif (strtolower($node->tag) == "head") {
                $head = $node;
            } elseif (strtolower($node->tag) == "body") {
                $body = $node;
            }
            $self->_removeSnippet($node);
            if ($adds_hreflang) {
                $self->_removeHreflang($node);
            }
            $self->_removeWovnIgnore($node, $marker);
            $self->_removeCustomIgnoreClass($node, $marker);
            $self->_removeForm($node, $marker);
            // inside <script>, comment("<!--") is invalid
            $self->_removeScript($node, $marker);
        });

        $tags = array($head, $body, $html);
        foreach ($tags as $insert_tag) {
            if (is_null($insert_tag)) {
                continue;
            }

            $hreflangTags = array();
            if ($adds_hreflang) {
                $lang_codes = $this->store->settings['supported_langs'];
                foreach ($lang_codes as $lang_code) {
                    $href = $this->buildHrefLang($lang_code);
                    array_push($hreflangTags, '<link rel="alternate" hreflang="' . Lang::iso6391Normalization($lang_code) . '" href="' . $href . '">');
                }
            }

            $snippet = $this->buildSnippetCode(true);
            $insert_tag->innertext = implode('', $hreflangTags) . $snippet . $insert_tag->innertext;
            break;
        }
    }

    /**
     * Insert wovn's snippet to ensure snippet is always inserted.
     * When snippet is always inserted, do nothing
     *
     * @param string $html
     * @param bool $adds_backend_error_mark
     */
    private function insertSnippet($html, $adds_backend_error_mark)
    {
        $snippet_regex = "/<script[^>]*src=[^>]*j\.[^ '\">]*wovn\.io[^>]*><\/script>/i";
        $html = $this->removeTagFromHtmlByRegex($html, $snippet_regex);

        $snippet_code = $this->buildSnippetCode($adds_backend_error_mark);
        $parent_tags = array("(<head\s?.*?>)", "(<body\s?.*?>)", "(<html\s?.*?>)");

        return $this->insertAfterTag($parent_tags, $html, $snippet_code);
    }

    private function insertNoindex($html)
    {
        $noindexMetaTag = '<meta name="robots" content="noindex">';
        $parent_tags = array("(<head\s?.*?>)");
        return $this->insertAfterTag($parent_tags, $html, $noindexMetaTag);
    }

    private function isNoindexLang($lang)
    {
        return in_array($lang, $this->store->settings['no_index_langs']);
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

    private function buildSnippetCode($adds_backend_error_mark)
    {
        $data_wovnio_params = array();
        $data_wovnio_params['key'] = $this->token;
        $data_wovnio_params['backend'] = 'true';
        $data_wovnio_params['currentLang'] = $this->headers->lang();
        $data_wovnio_params['defaultLang'] = $this->store->settings['default_lang'];
        $data_wovnio_params['urlPattern'] = $this->store->settings['url_pattern_name'];
        $data_wovnio_params['langCodeAliases'] = json_encode($this->store->settings['custom_lang_aliases']);
        $data_wovnio_params['langParamName'] = $this->store->settings['lang_param_name'];
        if (!empty($this->store->settings['site_prefix_path'])) {
            $data_wovnio_params['sitePrefixPath'] = $this->store->settings['site_prefix_path'];
        }
        if ($this->store->getCustomDomainLangs()) {
            $data_wovnio_params['customDomainLangs'] = json_encode($this->store->getCustomDomainLangs()->toHtmlSwapperHash());
        }

        $data_wovnio_info_params = array();
        $wovn_php_name = defined('WOVN_PHP_NAME') ? WOVN_PHP_NAME : 'WOVN.php';
        $wovn_php_version = defined('WOVN_PHP_VERSION') ? WOVN_PHP_VERSION : '';
        $data_wovnio_info_params['version'] = "{$wovn_php_name}_{$wovn_php_version}";

        $widget_url = $this->store->settings['widget_url'];
        $data_wovnio = htmlentities($this->buildParamsStr($data_wovnio_params));
        $data_wovnio_info = htmlentities($this->buildParamsStr($data_wovnio_info_params));
        $fallback_mark = $adds_backend_error_mark ? ' data-wovnio-type="fallback_snippet"' : '';

        return "<script src=\"$widget_url\" data-wovnio=\"$data_wovnio\" data-wovnio-info=\"$data_wovnio_info\"$fallback_mark async></script>";
    }

    private function buildParamsStr($params_array)
    {
        $params = array();
        foreach ($params_array as $key => $value) {
            $param_str = "$key=$value";
                array_push($params, $param_str);
        }
        return implode('&', $params);
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
        $hreflang_regex = "/<link [^>]*hreflang=[\"']?($lang_codes_with_pipe)[\"']?(\s[^>]*)?\>/iU";
        $html = $this->removeTagFromHtmlByRegex($html, $hreflang_regex);

        $hreflangTags = array();
        foreach ($lang_codes as $lang_code) {
            if ($this->isNoindexLang($lang_code)) {
                continue;
            }
            $href = $this->buildHrefLang($lang_code);
            array_push($hreflangTags, '<link rel="alternate" hreflang="' . Lang::iso6391Normalization($lang_code) . '" href="' . $href . '">');
        }

        $parent_tags = array("(<head\s?.*?>)", "(<body\s?.*?>)", "(<html\s?.*?>)");

        return $this->insertAfterTag($parent_tags, $html, implode('', $hreflangTags));
    }

    private function buildHrefLang($lang_code)
    {
        $url = $this->headers->urlKeepTrailingSlash;

        if ($this->store->hasDefaultLangAlias()) {
            $url = $this->headers->removeLang($url, $this->store->defaultLang());
            $url = Url::addLangCode($url, $this->store, $lang_code, $this->headers);
        } else {
            $customDomainLangs = $this->store->getCustomDomainLangs();
            $customDomainLang = $customDomainLangs ? $customDomainLangs->getCustomDomainLangByLang($lang_code) : false;
            $customDomainLangHasSource = $customDomainLang ? !!$customDomainLang->getSource() : false;

            if ($lang_code !== $this->store->defaultLang() || $customDomainLangHasSource) {
                $url = Url::addLangCode($url, $this->store, $lang_code, $this->headers);
            }
        }
        return htmlentities($url);
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
        if (strpos($originalText, HtmlReplaceMarker::$keyPrefix) !== false) {
            return;
        }

        $key = $marker->addCommentValue($originalText);
        $element->innertext = $key;
    }

    // PHP 5.3 doesn't allow calling private method inside anonymous functions,
    // so we use '_' for implicit visibility in the methods below
    // phpcs:disable Squiz.Scope.MethodScope.Missing
    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

    /**
     * @param SimpleHtmlDomNode $node
     */
    function _removeSnippet($node)
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
     * Note: Because php5.3 doesn't allow calling private method inside anonymous function,
     * Use `_` prefix to imply `private`
     *
     * @param SimpleHtmlDomNode $node
     */
    function _removeHreflang($node)
    {
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
     * Note: Because php5.3 doesn't allow calling private method inside anonymous function,
     * Use `_` prefix to imply `private`
     *
     * @param SimpleHtmlDomNode $node
     * @param HtmlReplaceMarker $marker
     */
    function _removeWovnIgnore($node, $marker)
    {
        if ($node->getAttribute('wovn-ignore') || $node->getAttribute('data-wovn-ignore')) {
            $this->putReplaceMarker($node, $marker);
        }
    }

    function _removeCustomIgnoreClass($node, $marker)
    {
        $class_attr = $node->getAttribute('class');
        if ($class_attr) {
            $classes_to_ignore = $this->store->settings['ignore_class'];
            $classes = array_filter(preg_split("/[\s]+/", $class_attr));
            $classes_intersect = array_intersect($classes_to_ignore, $classes);
            if (!empty($classes_intersect)) {
                $this->putReplaceMarker($node, $marker);
            }
        }
    }

    /**
     * Remove form elements to avoid CSRF token or flexible input's value
     *
     * Note: Because php5.3 doesn't allow calling private method inside anonymous function,
     * Use `_` prefix to imply `private`
     *
     * @param SimpleHtmlDomNode $node
     * @param HtmlReplaceMarker $marker
     */
    function _removeForm($node, $marker)
    {
        if (strtolower($node->tag) === 'form') {
            $this->putReplaceMarker($node, $marker);
            return;
        }

        if (strtolower($node->tag) === 'input' && strtolower($node->getAttribute('type')) == 'hidden') {
            $originalText = $node->getAttribute('value');
            if (strpos($originalText, HtmlReplaceMarker::$keyPrefix) !== false) {
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
     * Note: Because php5.3 doesn't allow calling private method inside anonymous function,
     * Use `_` prefix to imply `private`
     *
     * @param SimpleHtmlDomNode $node
     * @param HtmlReplaceMarker $marker
     */
    function _removeScript($node, $marker)
    {
        if (strtolower($node->tag) === 'script' && !preg_match('/type=["\']application\/ld\+json["\']/', $node->attribute)) {
            $this->putReplaceMarker($node, $marker);
        }
    }

    // phpcs:enable
}
