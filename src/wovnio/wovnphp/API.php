<?php
namespace Wovnio\Wovnphp;

require_once DIRNAME(__FILE__) . '../../utils/request_handlers/RequestHandlerFactory.php';
require_once 'custom_domain/CustomDomainLangUrlHandler.php';

use \Wovnio\Wovnphp\Logger;
use \Wovnio\Html\HtmlConverter;
use \Wovnio\Utils\RequestHandlers\RequestHandlerFactory;

class API
{
    public static function url($store, $headers, $original_content)
    {
        $token = $store->settings['project_token'];
        $path = $headers->pathnameKeepTrailingSlash;
        $lang = $headers->lang();
        $body_hash = md5($original_content);
        ksort($store->settings);
        $settings_hash = md5(serialize($store->settings));
        $cache_key = rawurlencode("(token=$token&settings_hash=$settings_hash&body_hash=$body_hash&path=$path&lang=$lang)");
        return $store->settings['api_url'] . 'translation?cache_key=' . $cache_key;
    }

    public static function translate($store, $headers, $original_content)
    {
        $api_url = self::url($store, $headers, $original_content);
        $encoding = $store->settings['encoding'];
        $token = $store->settings['project_token'];

        $converter = new HtmlConverter($original_content, $encoding, $token, $store, $headers);
        if (self::makeAPICall($store, $headers) === false) {
            list($translated_content) = $converter->insertSnippetAndHreflangTags(false);
            return $translated_content;
        }

        $saves_memory = $store->settings['save_memory_by_sending_wovn_ignore_content'];
        if ($saves_memory) {
            list($converted_html, $marker) = $converter->insertSnippetAndHreflangTags(true);
        } else {
            list($converted_html, $marker) = $converter->convertToAppropriateBodyForApi();
        }

        $timeout = $store->settings['api_timeout'];
        $computedUrl = self::computeSourceVirtualUrl($headers->urlKeepTrailingSlash, $store, $headers->lang());
        $data = array(
            'url' => $computedUrl,  // rewrite URL to use source lang's "virtual" url.
            'token' => $token,
            'lang_code' => $headers->lang(),
            'url_pattern' => $store->settings['url_pattern_name'],
            'lang_param_name' => $store->settings['lang_param_name'],
            'product' => WOVN_PHP_NAME,
            'version' => WOVN_PHP_VERSION,
            'body' => $converted_html
        );

        if (count($store->settings['custom_lang_aliases']) > 0) {
            $data['custom_lang_aliases'] = json_encode($store->settings['custom_lang_aliases']);
        }
        if (count($store->settings['no_index_langs']) > 0) {
            $data['no_index_langs'] = json_encode($store->settings['no_index_langs']);
        }
        if (!empty($store->settings['site_prefix_path'])) {
            $data['site_prefix_path'] = $store->settings['site_prefix_path'];
        }
        if ($store->getCustomDomainLangs()) {
            $data['custom_domain_langs'] = json_encode($store->getCustomDomainLangs()->toHtmlSwapperHash());
        }

        try {
            $request_handler = RequestHandlerFactory::getBestAvailableRequestHandler();
            if ($request_handler === null) {
                return $marker->revert($converted_html);
            }
            list($response, $headers, $error) = $request_handler->sendRequest('POST', $api_url, $data, $timeout);
            if ($response === null) {
                if ($error) {
                    header("X-Wovn-Error: $error");
                }
                return $marker->revert($converted_html);
            }

            $translation_response = json_decode($response, true);
            if (array_key_exists('body', $translation_response)) {
                return $marker->revert($translation_response['body']);
            } else {
                return $marker->revert($converted_html);
            }
        } catch (\Exception $e) {
            Logger::get()->error('Failed to get translated content: {exception}.', array('exception' => $e));

            return $marker->revert($converted_html);
        }
    }

    private static function computeSourceVirtualUrl($uri, $store, $lang)
    {
        $urlPatternName = $store->settings['url_pattern_name'];
        $customDomainLangs = $store->getCustomDomainLangs();

        if ($urlPatternName == 'custom_domain' && $customDomainLangs) {
            $currentLangDomainLang = $customDomainLangs->getSourceCustomDomainByLang($lang);
            $default_lang = $store->settings['default_lang'];
            if ($currentLangDomainLang->getSource()) {
                $defaultCustomDomainLang = $currentLangDomainLang->getSource();
            } else {
                $defaultCustomDomainLang = $customDomainLangs->getCustomDomainLangByLang($default_lang);
            }
            return CustomDomainLangUrlHandler::changeToNewCustomDomainLang($uri, $currentLangDomainLang, $defaultCustomDomainLang);
        } else {
            return $uri;
        }
    }

    private static function makeAPICall($store, $headers)
    {
        return $headers->lang() != $store->settings['default_lang'] || !$store->settings['disable_api_request_for_default_lang'];
    }
}
