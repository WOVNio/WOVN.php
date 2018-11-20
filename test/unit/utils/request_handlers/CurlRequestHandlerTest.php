<?php
namespace Wovnio\Wovnphp\Tests\Unit\Utils\RequestHandlers;

require_once 'src/wovnio/utils/request_handlers/CurlRequestHandler.php';

class CurlRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testPost()
    {
        $api_url = 'http://api.wovn.io/a/b';
        $data =  array(
            'url' => 'http://wovn.io/a/b',
            'token' => 'Tok3n',
            'lang_code' => 'en',
            'url_pattern' => 'path',
            'body' => '{"hello": "world"}'
        );

        $expected_content = gzencode(http_build_query($data));
        $expected_content_length = strlen($expected_content);
        $expected_context = array(
            "Content-Type: application/octet-stream",
            "Content-Length: $expected_content_length"
        );

        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $expected_content,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $expected_context
        );

        $expected_response = '{"foo": "bar"}';
        $curl_request_handler = $this->createMockedCurlRequestHandler($api_url, $options, $expected_response);
        $timeout = 10;

        $response = $curl_request_handler->sendRequest('POST', $api_url, $data, $timeout);
        $this->assertEquals($expected_response, $response);
    }

    private function createMockedCurlRequestHandler($api_url, $options, $response)
    {
        $builder = $this->getMockBuilder('\Wovnio\Utils\RequestHandlers\CurlRequestHandler');
        $builder->setMethods(array('curlExec'));
        $curl_request_handler = $builder->getMock();
        if (method_exists($this, 'registerMockObject')) {
            $this->registerMockObject($curl_request_handler);
        } else {
            $this->mockObjects[] = $curl_request_handler;
        }

        $curl_request_handler->expects($this->once())
            ->method('curlExec')
            ->with(
                $this->equalTo($api_url),
                $this->equalTo($options)
            )
            ->willReturn($response);

        return $curl_request_handler;
    }
}
