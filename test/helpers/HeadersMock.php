<?php
// This namespace cannot be Wovnio\Wovnphp\Tests\Helpers because it must
// redefine function in the scope of Wovnio\Wovnphp objects.
namespace Wovnio\Wovnphp;

$mockHeadersSent = false;
$headersSentMock = null;

$mockApacheResponseHeaders = false;
$functionExistsForApacheResponseHeadersMock = null;
$apacheResponseHeadersMock = null;

$mockHeader = false;
$receivedHeaders = null;

/** MOCK HELPERS **************************************************************/

function mockHeadersSent($mockValue)
{
    global $mockHeadersSent;
    global $headersSentMock;

    $mockHeadersSent = true;
    $headersSentMock = $mockValue;
}

function restoreHeadersSent()
{
    global $mockHeadersSent;
    global $headersSentMock;

    $mockHeadersSent = false;
    $headersSentMock = null;
}

function mockApacheResponseHeaders($functionExists, $mockValue = array())
{
    global $mockApacheResponseHeaders;
    global $functionExistsForApacheResponseHeadersMock;
    global $apacheResponseHeadersMock;

    $mockApacheResponseHeaders = true;
    $functionExistsForApacheResponseHeadersMock = $functionExists;
    $apacheResponseHeadersMock = $mockValue;
}

function restoreApacheResponseHeaders()
{
    global $mockApacheResponseHeaders;
    global $functionExistsForApacheResponseHeadersMock;
    global $apacheResponseHeadersMock;

    $mockApacheResponseHeaders = false;
    $functionExistsForApacheResponseHeadersMock = null;
    $apacheResponseHeadersMock = null;
}

function mockHeader()
{
    global $mockHeader;
    global $receivedHeaders;

    $mockHeader = true;
    $receivedHeaders = array();
}

function restoreHeader()
{
    global $mockHeader;
    global $receivedHeaders;

    $mockHeader = false;
    $receivedHeaders = null;
}

function getHeadersReceivedByHeaderMock()
{
    global $receivedHeaders;

    return $receivedHeaders;
}

/** MOCKED FUNCTIONS **********************************************************/

// phpcs:disable Squiz.NamingConventions.ValidFunctionName.NotCamelCaps

function headers_sent()
{
    global $mockHeadersSent;
    global $headersSentMock;

    if ($mockHeadersSent) {
        return $headersSentMock;
    } else {
        return call_user_func_array('\headers_sent', func_get_args());
    }
}

function function_exists($funcName)
{
    global $mockApacheResponseHeaders;
    global $functionExistsForApacheResponseHeadersMock;

    if ($mockApacheResponseHeaders && $funcName === 'apache_response_headers') {
        return $functionExistsForApacheResponseHeadersMock;
    } else {
        return call_user_func_array('\function_exists', func_get_args());
    }
}

function apache_response_headers()
{
    global $mockApacheResponseHeaders;
    global $apacheResponseHeadersMock;

    if ($mockApacheResponseHeaders) {
        return $apacheResponseHeadersMock;
    } else {
        return call_user_func_array('\apache_response_headers', func_get_args());
    }
}

function header($h)
{
    global $mockHeader;
    global $receivedHeaders;

    if ($mockHeader) {
        array_push($receivedHeaders, $h);
    } else {
        return call_user_func_array('\header', func_get_args());
    }
}

// phpcs:enable
