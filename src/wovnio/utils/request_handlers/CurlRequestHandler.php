<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'AbstractRequestHandler.php';

use Wovnio\Utils\RequestHandlers\AbstractRequestHandler;

class CurlRequestHandler extends AbstractRequestHandler
{
    public static function available()
    {
        if (!extension_loaded('curl')) {
            return false;
        }

        $used_functions = array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close');
        $can_use_functions = count(array_intersect(get_extension_funcs('curl'), $used_functions)) === count($used_functions);
        if (!$can_use_functions) {
            return false;
        }

        $supported_protocols = array('http', 'https');
        $curl_version = curl_version();
        $can_use_protocols = count(array_intersect($curl_version['protocols'], $supported_protocols)) === count($supported_protocols);

        return $can_use_protocols;
    }

    protected function post($url, $request_headers, $data, $timeout)
    {
        $curl_session = curl_init($url);

        curl_setopt_array($curl_session, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            // adds header to accept GZIP encoding and handles decoding response
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $request_headers
        ));

        $response = curl_exec($curl_session);
        $header_size = curl_getinfo($curl_session, CURLINFO_HEADER_SIZE);
        $headers = $response ? explode("\r\n", substr($response, 0, $header_size)) : array();

        if (curl_error($curl_session) !== '') {
            $curl_error_code = curl_errno($curl_session);
            $http_error_code = curl_getinfo($curl_session, CURLINFO_HTTP_CODE);

            curl_close($curl_session);

            return array(null, $headers, "[cURL] Request failed ($curl_error_code-$http_error_code).");
        }

        $response_body = substr($response, $header_size);

        curl_close($curl_session);

        return array($response_body, $headers, null);
    }
}
