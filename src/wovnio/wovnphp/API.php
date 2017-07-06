<?php
  namespace Wovnio\Wovnphp;

  require_once DIRNAME(__FILE__) . '../../utils/request_handlers/RequestHandlerFactory.php';

  use Wovnio\Utils\RequestHandlers\RequestHandlerFactory;

  class API {
    public static function url($store, $headers, $original_content) {
      $token = $store->settings['project_token'];
      $path = $headers->pathname;
      $lang = $headers->lang();
      $body_hash = md5($original_content);
      $cache_key = rawurlencode("(token=$token&body_hash=$body_hash&path=$path&lang=$lang)");
      return $store->settings['api_url'] . 'translation?cache_key=' . $cache_key;
    }

    public static function translate($store, $headers, $original_content) {
      $translated_content = NULL;
      $api_url = self::url($store, $headers, $original_content);
      $timeout = $store->settings['api_timeout'];
      $data = array(
        'url' => $headers->url,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => $store->settings['url_pattern_name'],
        'body' => $original_content
      );

      try {
        $translation_response = json_decode(RequestHandlerFactory::get()->sendRequest('POST', $api_url, $data, $timeout), true);
        $translated_content = $translation_response['body'];
      } catch (\Exception $e) {
        error_log('****** WOVN++ LOGGER :: Failed to get translated content: ' . $e->getMessage() . ' ******');
      }

      return $translated_content;
    }
  }

