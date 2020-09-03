<?php


namespace Wovnio\Wovnphp\Core;

use Wovnio\Wovnphp\Core\RequestHandlers\RequestHandlerFactory;

class WovnAPICaller
{
    private $option; // The WovnOption object
    private $originalHTML; // The original HTML as a string, in a format appropriate for API
    private $request; // The WovnRequest object

    public function __constructor($option, $originalHTML, $request)
    {
    }

    public function translate()
    {
    }

    private function constructApiCallUrl()
    {
        $token = $this->option->get('project_token');
        $wovnUrl = $this->request->wovnUrl();
        $path = $wovnUrl->getNoQuery();
        $lang = $this->request->lang();
        $body_hash = md5($this->originalHTML);
        $settings_hash = $this->option->getMD5Hash();
        $cache_key = rawurlencode("(token=$token&settings_hash=$settings_hash&body_hash=$body_hash&path=$path&lang=$lang)");
        return $this->option->get('api_url') . 'translation?cache_key=' . $cache_key;
    }

    private function apiSwap()
    {
        $api_url = $this->constructApiCallUrl();
        $wovnUrl = $this->request->wovnUrl();
        $url = $wovnUrl->getNoQuery();

        $data = array(
            'url' => $url,
            'token' => $this->option->get('project_token'),
            'lang_code' => $this->request->lang(),
            'url_pattern' => $this->option->get('url_pattern_name'),
            'lang_param_name' => $this->option->get('lang_param_name'),
            'product' => WOVN_PHP_NAME,
            'version' => WOVN_PHP_VERSION,
            'body' => $this->originalHTML
        );

        if ($this->option->get('custom_lang_aliases')) {
            $data['custom_lang_aliases'] = json_encode($this->option->get('custom_lang_aliases'));
        }
        if ($this->option->get('no_index_langs')) {
            $data['no_index_langs'] = json_encode($this->option->get('no_index_langs'));
        }
        if (!empty($store->settings['site_prefix_path'])) {
            $data['site_prefix_path'] = $store->settings['site_prefix_path'];
        }

        $timeout = $this->option->get('api_timeout');

        try {
            $request_handler = RequestHandlerFactory::getBestAvailableRequestHandler();
            if ($request_handler === null) {
                throw new WovnAPIException('No available request handler.');
            }
            list($response, $headers, $error) = $request_handler->sendRequest('POST', $api_url, $data, $timeout);
            if ($response === null) {
                if ($error) {
                    header("X-Wovn-Error: $error");
                }
                throw new WovnAPIException("Remote Server Error: {$error}.");
            }
            $translation_response = json_decode($response, true);
            if (array_key_exists('body', $translation_response)) {
                return $translation_response['body'];
            } else {
                throw new WovnAPIException('No translation body found.');
            }
        } catch (\Exception $e) {
            throw new WovnAPIException("Failed to get translated content: {$e}.");
        }
    }
}
