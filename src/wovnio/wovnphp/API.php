<?php
  namespace Wovnio\Wovnphp;

  require_once 'src/utils/request_handlers/RequestHandlerFactory.php';

  use Wovnio\Utils\RequestHandlers\RequestHandlerFactory;

  class API {
    const ACTION_TRANSLATE = 'translation';

    public static function url($store, $action) {
      return $store->settings['api_url'] . 'translation';
    }

    public static function translate($store, $headers, $original_content) {
      $translated_content = NULL;
      $api_url = self::url($store, self::ACTION_TRANSLATE);
      $timeout = $store->settings['api_timeout'];
      $data = array(
        'url' => $headers->url,
        'token' => $store->settings['project_token'],
        'lang_code' => $headers->lang(),
        'url_pattern' => $store->settings['url_pattern_name'],
        'body' => $original_content
      );

      try {
        $translation_response = RequestHandlerFactory::get()->sendRequest('POST', $api_url, $data, $timeout);
        $translated_content = json_decode($translation_response, true)['body'];
      } catch (\Exception $e) {
        error_log('****** WOVN++ LOGGER :: Failed to get translated content: ' . $e->getMessage() . ' ******');
      }

      return $translated_content;
    }
  }

