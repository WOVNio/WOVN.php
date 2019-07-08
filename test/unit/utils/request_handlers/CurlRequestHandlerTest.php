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

    private function configureAvailability($curl_loaded, $curl_functions, $curl_protocols)
    {
        FunctionMockBuilder::buildFunctionMock('extension_loaded', $curl_loaded)->enable();
        FunctionMockBuilder::buildFunctionMock('get_extension_funcs', $curl_functions)->enable();
        FunctionMockBuilder::buildFunctionMock('curl_version', array('protocols' => $curl_protocols))->enable();
    }

    private function setAvailable()
    {
        $this->configureAvailability(
            true,
            array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close'),
            array('http', 'https')
        );
    }

    private function assertPostRequest($expected_response, $expected_headers, $expected_error)
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
        FunctionMockBuilder::buildFunctionMock('curl_exec', function ($curl_session) use (&$that, &$data, &$curl_response) {
            $that->assertEquals($data['url'], \curl_getinfo($curl_session, CURLINFO_EFFECTIVE_URL));

            return $curl_response;
        })->enable();
        FunctionMockBuilder::buildFunctionMock('curl_setopt_array', function ($curl_session, $options) use (&$that, &$data) {
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
        FunctionMockBuilder::buildFunctionMock('curl_error', $curl_error)->enable();
        FunctionMockBuilder::buildFunctionMock('curl_getinfo', function ($curl_session, $option) use ($expected_headers, $headers_string) {
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

    public function testAvailable()
    {
        $this->setAvailable();
        $this->assertTrue(CurlRequestHandler::available());
    }

    public function testNotAvailableBecauseExtensionNotLoaded()
    {
        $this->configureAvailability(
            false,
            array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close'),
            array('http', 'https')
        );
        $this->assertFalse(CurlRequestHandler::available());
    }

    public function testNotAvailableBecauseExtensionBecauseOfMissingFunctions()
    {
        $this->configureAvailability(
            true,
            array(),
            array('http', 'https')
        );
        $this->assertFalse(CurlRequestHandler::available());
    }

    public function testNotAvailableBecauseExtensionBecauseOfMissingProtocols()
    {
        $this->configureAvailability(
            true,
            array('curl_version', 'curl_init', 'curl_setopt_array', 'curl_exec', 'curl_getinfo', 'curl_close'),
            array()
        );
        $this->assertFalse(CurlRequestHandler::available());
    }

    public function testSendRequestPost()
    {
        $formatted_response = array(
            'body' =>'<html><head></head><body><h1>Félicitations !</h1></body></html>'
        );
        $expected_response = json_encode($formatted_response);
        $expected_headers = array(
            'HTTP/1.0 200',
            'Content-Encoding: gzip'
        );

        $this->assertPostRequest($expected_response, $expected_headers, null);
    }

    public function testSendRequestPostWithError()
    {
        $error_code = 500;
        $error_headers = array(
            "HTTP/1.0 $error_code"
        );
        $expected_error = "[cURL] Request failed (0-$error_code).";

        $this->assertPostRequest(null, $error_headers, $expected_error);
    }
}
