<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'AbstractRequestHandler.php';
require_once DIRNAME(__FILE__) . '../../HTTPHeaderParser.php';

use Wovnio\Utils\HTTPHeaderParser\HTTPHeaderParser;
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

        // If body size is over 1024 bytes, cURL will add 'Expect: 100-continue' header automatically.
        // And wait until the response from html-swapper is returned.
        // This takes always 1[s].
        // So, it is better to disable 'Expect: 100-continue'.
        array_push($request_headers, 'Expect:');

        $curlOptions = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            // adds header to accept GZIP encoding and handles decoding response
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $request_headers
        );

        $proxyHost = $this->store->outboundProxyHost();
        if ($proxyHost) {
            $curlOptions[CURLOPT_PROXY] = $proxyHost;
        }
        $proxyPort = $this->store->outboundProxyPort();
        if ($proxyPort) {
            $curlOptions[CURLOPT_PROXYPORT] = $proxyPort;
        }

        curl_setopt_array($curl_session, $curlOptions);

        $response = curl_exec($curl_session);
        $header_size = curl_getinfo($curl_session, CURLINFO_HEADER_SIZE);
        $headers = $response ? explode("\r\n", substr($response, 0, $header_size)) : array();
        $parsedHeaders = HTTPHeaderParser::parseRawResponse($response, $header_size);
        if (curl_error($curl_session) !== '') {
            $curl_error_code = curl_errno($curl_session);
            $http_error_code = curl_getinfo($curl_session, CURLINFO_HTTP_CODE);

            curl_close($curl_session);

            return array(null, $headers, "[cURL] Request failed ($curl_error_code-$http_error_code).");
        }

        $response_body = substr($response, $header_size);

        curl_close($curl_session);

        return array($response_body, $parsedHeaders, null);
    }
}
