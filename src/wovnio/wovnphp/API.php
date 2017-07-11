<?php
  namespace Wovnio\Wovnphp;

  require_once DIRNAME(__FILE__) . '../../utils/request_handlers/RequestHandlerFactory.php';

  use Wovnio\Utils\RequestHandlers\RequestHandlerFactory;

  class API {
    const ACTION_TRANSLATE = 'translation';

    public static function url($store, $action) {
      return $store->settings['api_url'] . 'translation';
    }

    public static function translate($store, $headers, $original_content) {
      $translated_content = NULL;
      $token = $store->settings['project_token'];
      $settings_digest = md5(serialize(asort($store->settings)));
      $content_digest = md5($original_content);
      $api_url = self::url($store, self::ACTION_TRANSLATE)
                 . '?token=' . $token
                 . '&settings_digest=' . $settings_digest
                 . '&content_digest=' . $content_digest;
      $timeout = $store->settings['api_timeout'];
      $data = array(
        'url' => $headers->url,
        'token' => $token,
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

