<?php
namespace Wovnio\Wovnphp;

require_once DIRNAME(__FILE__) . '../../utils/request_handlers/RequestHandlerFactory.php';
require_once 'custom_domain/CustomDomainLangUrlHandler.php';

use \Wovnio\Wovnphp\Logger;
use \Wovnio\Html\HtmlConverter;
use \Wovnio\Utils\RequestHandlers\RequestHandlerFactory;

class API
{
    public static function url($store, $headers, $original_content, $request_options)
    {
        $token = $store->settings['project_token'];
        $path = $headers->pathnameKeepTrailingSlash;
        $lang = $headers->requestLang();
        $body_hash = md5($original_content);
        ksort($store->settings);
        $settings_hash = md5(serialize($store->settings));
        $cache_key_string = "(token=$token&settings_hash=$settings_hash&body_hash=$body_hash&path=$path&lang=$lang)";
        if ($request_options->getCacheDisableMode() || $request_options->getDebugMode()) {
            $cache_key_string = $cache_key_string . "&timestamp=" . time();
        }
        $cache_key = rawurlencode($cache_key_string);

        return $store->settings['api_url'] . '/v0/translation?cache_key=' . $cache_key;
    }

    public static function translate($store, $headers, $original_content, $request_options)
    {
        $encoding = $store->settings['encoding'];
        $token = $store->settings['project_token'];
        $default_lang = $store->settings['default_lang'];

        $converter = new HtmlConverter($encoding, $token, $store, $headers);
        if (self::makeAPICall($store, $headers) === false) {
            $translated_content = $converter->insertSnippetAndLangTags($original_content, false);
            return $translated_content;
        }

        $saves_memory = $store->settings['save_memory_by_sending_wovn_ignore_content'];
        $converted_html = $original_content;
        if (!$saves_memory) {
            $converted_html = $converter->convertToAppropriateBodyForApi($converted_html);
        }
        $converted_html = $converter->insertSnippetAndLangTags($converted_html, true);

        $timeout = $headers->isSearchEngineBot() ? $store->settings['api_timeout_search_engine_bots'] : $store->settings['api_timeout'];
        $computedUrl = self::getUriRepresentation($headers->urlKeepTrailingSlash, $store, $headers->requestLang());
        $data = array(
            'url' => $computedUrl,  // rewrite URL to use source lang's "virtual" url.
            'browser_url' => $headers->originalUrl,
            'token' => $token,
            'lang_code' => $headers->requestLang(),
            'url_pattern' => $store->settings['url_pattern_name'],
            'lang_param_name' => $store->settings['lang_param_name'],
            'product' => WOVN_PHP_NAME,
            'version' => WOVN_PHP_VERSION,
            'body' => $converted_html,
            'translate_canonical_tag' => $store->settings['translate_canonical_tag']
        );

        if (count($store->settings['custom_lang_aliases']) > 0) {
            $data['custom_lang_aliases'] = json_encode($store->settings['custom_lang_aliases']);
        }
        if (count($store->settings['no_index_langs']) > 0) {
            $data['no_index_langs'] = json_encode($store->settings['no_index_langs']);
        }
        if (count($store->settings['no_hreflang_langs']) > 0) {
            $data['no_hreflang_langs'] = json_encode($store->settings['no_hreflang_langs']);
        }
        if (!empty($store->settings['site_prefix_path'])) {
            $data['site_prefix_path'] = $store->settings['site_prefix_path'];
        }
        if (isset($store->settings['insert_hreflangs'])) {
            $data['insert_hreflangs'] = json_encode($store->settings['insert_hreflangs']);
        }
        if (isset($store->settings['preserve_relative_urls'])) {
            $data['preserve_relative_urls'] = $store->settings['preserve_relative_urls'];
        }
        if ($store->getCustomDomainLangs()) {
            $data['custom_domain_langs'] = json_encode($store->getCustomDomainLangs()->toHtmlSwapperHash());
        }
        if (function_exists('http_response_code')) {
            $data['page_status_code'] = http_response_code();
        }
        if ($request_options->getDebugMode()) {
            $data['debug_mode'] = 'true';
        }

        try {
            $request_handler = RequestHandlerFactory::getBestAvailableRequestHandler($store);
            if ($request_handler === null) {
                return $marker->revert($converted_html);
            }
            $api_url = self::url($store, $headers, $converted_html, $request_options);
            list($response, $headers, $error) = $request_handler->sendRequest('POST', $api_url, $data, $timeout);

            $requestUUID = 'NO_UUID';
            if ($headers) {
                $status = array_key_exists('status', $headers) ? $headers['status'] : 'STATUS_UNKNOWN';
                $data['body'] = "[Hidden]";
                Logger::get()->info("API call to html-swapper finished: {$status}.");
                Logger::get()->info("API call payload: " . json_encode($data));
            }

            if ($response === null) {
                if ($error) {
                    header("X-Wovn-Error: $error");
                    Logger::get()->error("API call error: {$error}.");
                }
                return $converter->revertMarkers($converted_html);
            }

            $translation_response = json_decode($response, true);
            if ($translation_response && array_key_exists('body', $translation_response)) {
                return $converter->revertMarkers($translation_response['body']);
            } else {
                return $converter->revertMarkers($converted_html);
            }
        } catch (\Exception $e) {
            Logger::get()->error('Failed to get translated content: {exception}.', array('exception' => $e));

            return $converter->revertMarkers($converted_html);
        }
    }

    private static function getUriRepresentation($uri, $store, $lang)
    {
        $urlPatternName = $store->settings['url_pattern_name'];

        if ($urlPatternName == 'custom_domain') {
            $customDomainLangs = $store->getCustomDomainLangs();
            if ($customDomainLangs) {
                return $customDomainLangs->computeSourceVirtualUrl($uri, $lang, $store->settings['default_lang']);
            } else {
                return $uri;
            }
        } else {
            return $uri;
        }
    }

    private static function makeAPICall($store, $headers)
    {
        return $headers->requestLang() != $store->settings['default_lang'] || !$store->settings['disable_api_request_for_default_lang'];
    }
}
