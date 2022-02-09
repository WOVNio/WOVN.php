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

    private $encoding;
    private $token;
    private $store;
    private $headers;
    private $marker;
    private $wovnWidgetUrls;

    /**
     * HtmlConverter constructor.
     * @param String $html
     * @param String $encoding
     * @param String $token project_token
     * @param Store $store
     * @param Headers $headers
     */
    public function __construct($encoding, $token, $store, $headers)
    {
        $this->encoding = $encoding;
        $this->token = $token;
        $this->store = $store;
        $this->headers = $headers;
        $this->marker = new HtmlReplaceMarker();
        $this->wovnWidgetUrls = array("j.wovn.io", "j.dev-wovn.io:3000", $this->store->settings['api_url'] . '/widget');
    }

    public function insertSnippetAndLangTags($html, $add_fallback_mark)
    {
        $converted_html = $html;
        $converted_html = $this->insertSnippet($converted_html, $add_fallback_mark);
        if ($this->store->settings['insert_hreflangs']) {
            $converted_html = $this->insertHreflangTags($converted_html);
        }
        if ($this->store->settings['translate_canonical_tag']) {
            $converted_html = $this->translateCanonicalTag($converted_html);
        }
        if ($this->isNoindexLang($this->headers->requestLang())) {
            $converted_html = $this->insertNoindex($converted_html);
        }
        $default_lang = $this->store->settings['default_lang'];
        $converted_html = $this->insertHtmlLangAttribute($converted_html, $default_lang);
        return $converted_html;
    }

    /**
     * Convert to appropriate HTML to send to Translation API
     * e.g.) remove wovn-ignore content
     *
     * @return array converted html and HtmlReplaceMarker
     */
    public function convertToAppropriateBodyForApi($html)
    {
        if ($this->encoding && in_array($this->encoding, self::$supportedEncodings)) {
            $encoding = $this->encoding;
        } else {
            // Encoding detection uses 30% of execution time for this method.
            $encoding = mb_detect_encoding($html, self::$supportedEncodings);
        }
        $converted_html = $html;

        $dom = SimpleHtmlDom::str_get_html($converted_html, $encoding, false, false, $encoding, false);
        if ($dom) {
            $this->replaceDom($dom, $this->marker);

            $converted_html = $dom->save();
            $converted_html = $this->removeBackendWovnIgnoreComment($converted_html, $this->marker);

            // Without clear(), Segmentation fault will be raised.
            // @see https://sourceforge.net/p/simplehtmldom/bugs/103/
            $dom->clear();
            unset($dom);
        }

        return $converted_html;
    }

    public function revertMarkers($marked_html)
    {
        $unmarked_html = $this->marker->revert($marked_html);
        return $unmarked_html;
    }

    private function replaceDom($dom, &$marker)
    {
        $self = $this;
        $html = null;
        $head = null;
        $body = null;

        $dom->iterateAll(function ($node) use (&$self, $marker, &$html, &$head, &$body) {
            if (strtolower($node->tag) == "html") {
                $html = $node;
            } elseif (strtolower($node->tag) == "head") {
                $head = $node;
            } elseif (strtolower($node->tag) == "body") {
                $body = $node;
            }
            $self->_removeWovnIgnore($node, $marker);
            $self->_removeCustomIgnoreClass($node, $marker);
            $self->_removeForm($node, $marker);
            // inside <script>, comment("<!--") is invalid
            $self->_removeScript($node, $marker);
        });
    }

    /**
     * Insert wovn's snippet to ensure snippet is always inserted.
     * When snippet is always inserted, do nothing
     *
     * @param string $html
     * @param bool $add_fallback_mark
     */
    private function insertSnippet($html, $add_fallback_mark)
    {
        $html = $this->removeSnippet($html);
        $snippet_code = $this->buildSnippetCode($add_fallback_mark);
        $parent_tags = array("(<head\s?.*?>)", "(<body\s?.*?>)", "(<html\s?.*?>)");

        return $this->insertAfterTag($parent_tags, $html, $snippet_code);
    }

    private function removeSnippet($html)
    {
        $snippet_regex = '@' .
        '<script[^>]*' . // open tag
        '(' .
        'src=\"[^">]*(' . implode("|", $this->wovnWidgetUrls) . ')[^">]*\"' . // src attribute
        '|' .
        'data-wovnio=\"[^">]+?\"' . // data-wovnio attribute
        ')' .
        '[^>]*><\/script>' . // close tag
        '@';
        return $this->removeTagFromHtmlByRegex($html, $snippet_regex);
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

    private function insertHtmlLangAttribute($html, $lang_code)
    {
        if (preg_match('/<html\s?.*?>/', $html, $matches)) {
            $html_open_tag = $matches[0];
            if (preg_match('/lang=["\']?[a-zA-Z-]*["\']?/', $html_open_tag)) {
                return $html;
            }
            $replacement = $html_open_tag;
            $replacement = str_replace('<html', "<html lang=\"$lang_code\"", $replacement);
            return str_replace($html_open_tag, $replacement, $html);
        }
        return $html;
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

    private function buildSnippetCode($add_fallback_mark)
    {
        $data_wovnio_params = array();
        $data_wovnio_params['key'] = $this->token;
        $data_wovnio_params['backend'] = 'true';
        $data_wovnio_params['currentLang'] = $this->headers->requestLang();
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
        $fallback_mark = $add_fallback_mark ? ' data-wovnio-type="fallback_snippet"' : '';

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

    private function translateCanonicalTag($html)
    {
        if ($this->isNoindexLang($this->headers->requestLang())) {
            return $html;
        }

        $canonical_tag_regexes = array(
            "/(<link[^>]*rel=\"canonical\"[^>]*href=\")([^\"]*)(\"[^>]*>)/",
            "/(<link[^>]*href=\")([^\"]*)(\"[^>]*rel=\"canonical\"[^>]*>)/"
        );

        $matched_regex_index = -1;
        foreach($canonical_tag_regexes as $index => $canonical_tag_regex) {
            preg_match($canonical_tag_regex, $html, $matches);
            if (count($matches) == 4) {
                $matched_regex_index = $index;
                break;
            }
        }
        if ($matched_regex_index == -1) {
            return $html;
        }
        $$canonical_tag_regex = $canonical_tag_regexes[$matched_regex_index];
        $original_canonical_url = $matches[2];
        $translated_canonical_url = $this->convertUrlToLanguage($original_canonical_url, $this->headers->requestLang());
        $replacement = '\1' . $translated_canonical_url . '\3';
        return preg_replace($canonical_tag_regex, $replacement, $html);
    }

    private function buildHrefLang($lang_code)
    {
        $url = $this->headers->urlKeepTrailingSlash;
        return $this->convertUrlToLanguage($url, $lang_code);
    }

    private function convertUrlToLanguage($url, $lang_code)
    {
        if ($this->store->hasDefaultLangAlias()) {
            $url = $this->headers->removeLang($url, $this->store->defaultLang());
            $url = Url::addLangCode($url, $this->store, $lang_code, $this->headers);
        } elseif ($lang_code !== $this->store->defaultLang() || $this->store->settings['url_pattern_name'] === 'custom_domain') {
            $url = Url::addLangCode($url, $this->store, $lang_code, $this->headers);
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

    /**
     * Note: Because php5.3 doesn't allow calling private method inside anonymous function,
     * Use `_` prefix to imply `private`
     *
     * @param SimpleHtmlDomNode $node
     * @param HtmlReplaceMarker $marker
     */
    public function _removeWovnIgnore($node, $marker)
    {
        if ($node->hasAttribute('wovn-ignore') || $node->hasAttribute('data-wovn-ignore')) {
            $this->putReplaceMarker($node, $marker);
        }
    }

    public function _removeCustomIgnoreClass($node, $marker)
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
    public function _removeForm($node, $marker)
    {
        if (strtolower($node->tag) === 'form') {
            $this->putReplaceMarker($node, $marker);
            return;
        }

        if (strtolower($node->tag) === 'input' && strtolower($node->getAttribute('type')) == 'hidden') {
            $originalText = $node->getAttribute('value');
            if (!$originalText || strpos($originalText, HtmlReplaceMarker::$keyPrefix) !== false) {
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
    public function _removeScript($node, $marker)
    {
        if (strtolower($node->tag) === 'script' && !preg_match('/type=["\']application\/ld\+json["\']/', $node->attribute)) {
            $this->putReplaceMarker($node, $marker);
        }
    }

    // phpcs:enable
}
