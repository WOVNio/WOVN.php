<?php
  namespace Wovnio\Wovnphp;

  require_once DIRNAME(__FILE__) . '../../utils/request_handlers/RequestHandlerFactory.php';

  use Wovnio\Html\HtmlConverter;
  use Wovnio\Utils\RequestHandlers\RequestHandlerFactory;

  class API {
    public static function url($store, $headers, $original_content) {
      $token = $store->settings['project_token'];
      $path = $headers->pathname;
      $lang = $headers->lang();
      $body_hash = md5($original_content);
      ksort($store->settings);
      $settings_hash = md5(serialize($store->settings));
      $cache_key = rawurlencode("(token=$token&settings_hash=$settings_hash&body_hash=$body_hash&path=$path&lang=$lang)");
      return $store->settings['api_url'] . 'translation?cache_key=' . $cache_key;
    }

    public static function translate($store, $headers, $original_content) {
      $translated_content = NULL;
      $api_url = self::url($store, $headers, $original_content);

      $encoding = $store->settings['encoding'];
      $token = $store->settings['project_token'];

      if (self::makeAPICall($store, $headers)) {
        $converted_html = $original_content;
        $timeout = $store->settings['api_timeout'];
        $data = array(
          'url' => $headers->url,
          'token' => $token,
          'lang_code' => $headers->lang(),
          'url_pattern' => $store->settings['url_pattern_name'],
          'body' => $converted_html
        );

        if (count($store->settings['custom_lang_aliases']) > 0) {
          $data['custom_lang_aliases'] = json_encode($store->settings['custom_lang_aliases']);
        }

        try {
          $translation_response = json_decode(RequestHandlerFactory::get()->sendRequest('POST', $api_url, $data, $timeout), true);
          $translated_content = $translation_response['body'];
        } catch (\Exception $e) {
          error_log('****** WOVN++ LOGGER :: Failed to get translated content: ' . $e->getMessage() . ' ******');
        }
      }
      else {
        $converter = new HtmlConverter($original_content, $encoding, $token, $store, $headers);
        list($converted_html) = $converter->insertSnippetAndHreflangTags();
        $translated_content = $converted_html;
      }

      return $translated_content;
    }

    private static function makeAPICall($store, $headers) {
      return $headers->lang() != $store->settings['default_lang'] || !$store->settings['disable_api_request_for_default_lang'];
    }
  }

