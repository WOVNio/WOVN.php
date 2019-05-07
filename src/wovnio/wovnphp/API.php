<?php
namespace Wovnio\Wovnphp;

require_once DIRNAME(__FILE__) . '../../utils/request_handlers/RequestHandlerFactory.php';

use \Wovnio\Wovnphp\Logger;
use \Wovnio\Html\HtmlConverter;
use \Wovnio\Utils\RequestHandlers\RequestHandlerFactory;

if (!defined('WOVN_PHP_VERSION')) {
    define('WOVN_PHP_VERSION', '0.1.10');
}
if (!defined('WOVN_PHP_NAME')) {
    define('WOVN_PHP_NAME', 'WOVN.php');
}

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
        $data = array_merge($store->settings, array(
            'url' => $headers->urlKeepTrailingSlash,
            'lang_code' => $headers->lang(),
            'product' => WOVN_PHP_NAME,
            'version' => WOVN_PHP_VERSION,
            'body' => $converted_html
        ));

        if (count($store->settings['custom_lang_aliases']) > 0) {
            $data['custom_lang_aliases'] = json_encode($store->settings['custom_lang_aliases']);
        }

        try {
            $response = RequestHandlerFactory::get()->sendRequest('POST', $api_url, $data, $timeout);
            if ($response === null) {
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

    private static function makeAPICall($store, $headers)
    {
        return $headers->lang() != $store->settings['default_lang'] || !$store->settings['disable_api_request_for_default_lang'];
    }
}
