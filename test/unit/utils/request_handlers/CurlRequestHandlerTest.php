<?php
namespace Wovnio\Utils\RequestHandlers;

require_once 'test/helpers/FunctionMockBuilder.php';
require_once 'src/wovnio/utils/request_handlers/CurlRequestHandler.php';

use phpmock\Mock;
use Wovnio\Utils\RequestHandlers\CurlRequestHandler;
use Wovnio\Test\Helpers\FunctionMockBuilder;

use phpmock\MockBuilder;

class CurlRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mock::disableAll();
    }

    public function tearDown()
    {
        Mock::disableAll();
    }

    private function configure_availability($curl_loaded, $curl_functions, $curl_protocols)
    {
        FunctionMockBuilder::build_function_mock('extension_loaded', $curl_loaded)->enable();
        FunctionMockBuilder::build_function_mock('get_extension_funcs', $curl_functions)->enable();
        FunctionMockBuilder::build_function_mock('curl_version', array('protocols' => $curl_protocols))->enable();
    }

    private function set_available()
    {
        $this->configure_availability(
            true,
            array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close'),
            array('http', 'https')
        );
    }

    private function assert_post_request($expected_response, $expected_headers, $expected_error)
    {
        $sut = new CurlRequestHandler();
        $body = '<html><head></head><body><h1>Congratulations!</h1></body></html>';
        $data = array(
            'url' => 'https://api.wovn.io/v0/translations/?cache_key=12232015',
            'token' => 'v0Y3u',
            'lang_code' => 'fr',
            'url_pattern' => 'path',
            'lang_param_name' => 'wovn',
            'product' => WOVN_PHP_NAME,
            'version' => WOVN_PHP_VERSION,
            'body' => $body
        );
        $headers_string = implode("\r\n", $expected_headers);
        $curl_response = $headers_string . $expected_response;
        $curl_error = $expected_error ? 'ERROR :|' : '';

        $that = &$this;
        FunctionMockBuilder::build_function_mock('curl_exec', function ($curl_session) use (&$that, &$data, &$curl_response) {
            $that->assertEquals($data['url'], \curl_getinfo($curl_session, CURLINFO_EFFECTIVE_URL));

            return $curl_response;
        })->enable();
        FunctionMockBuilder::build_function_mock('curl_setopt_array', function ($curl_session, $options) use (&$that, &$data) {
            $formatted_data = http_build_query($data);
            $compressed_data = gzencode($formatted_data);
            $content_length = strlen($compressed_data);

            $that->assertEquals(array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 1,
                CURLOPT_ENCODING => 'gzip',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $compressed_data,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/octet-stream',
                    "Content-Length: $content_length",
                )
            ), $options);

            return \curl_setopt_array($curl_session, $options);
        })->enable();
        FunctionMockBuilder::build_function_mock('curl_error', $curl_error)->enable();
        FunctionMockBuilder::build_function_mock('curl_getinfo', function ($curl_session, $option) use ($expected_headers, $headers_string) {
            switch ($option) {
                case CURLINFO_HEADER_SIZE:
                    return strlen($headers_string);
                case CURLINFO_HTTP_CODE:
                    preg_match('{HTTP\/\S*\s(\d{3})}', $expected_headers[0], $match);

                    return $match[1];
            }

            return \curl_info($curl_session, $option);
        })->enable();

        list($response, $response_headers, $response_error) = $sut->sendRequest('POST', $data['url'], $data, 1);

        $this->assertEquals($expected_response, $response);
        $this->assertEquals($expected_headers, $response_headers);
        $this->assertEquals($expected_error, $response_error);
    }

    public function test_available()
    {
        $this->set_available();
        $this->assertTrue(CurlRequestHandler::available());
    }

    public function test_not_available_because_extension_not_loaded()
    {
        $this->configure_availability(
            false,
            array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close'),
            array('http', 'https')
        );
        $this->assertFalse(CurlRequestHandler::available());
    }

    public function test_not_available_because_extension_because_of_missing_functions()
    {
        $this->configure_availability(
            true,
            array(),
            array('http', 'https')
        );
        $this->assertFalse(CurlRequestHandler::available());
    }

    public function test_not_available_because_extension_because_of_missing_protocols()
    {
        $this->configure_availability(
            true,
            array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close'),
            array()
        );
        $this->assertFalse(CurlRequestHandler::available());
    }

    public function test_send_request_post()
    {
        $formatted_response = array(
            'body' =>'<html><head></head><body><h1>Félicitations !</h1></body></html>'
        );
        $expected_response = json_encode($formatted_response);
        $expected_headers = array(
            'HTTP/1.0 200',
            'Content-Encoding: gzip'
        );

        $this->assert_post_request($expected_response, $expected_headers, null);
    }

    public function test_send_request_post_with_error()
    {
        $error_code = 500;
        $error_headers = array(
            "HTTP/1.0 $error_code"
        );
        $expected_error = "[cURL] Request failed (0-$error_code).";

        $this->assert_post_request(null, $error_headers, $expected_error);
    }
}
