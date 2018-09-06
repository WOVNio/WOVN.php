<?php
namespace Wovnio\Wovnphp;

$mockHeadersSent = false;
$headersSentMock = null;

$mockApacheResponseHeaders = false;
$functionExistsForApacheResponseHeadersMock = null;
$apacheResponseHeadersMock = null;

$mockHeader = false;
$receivedHeaders = null;

/** MOCK HELPERS **************************************************************/

function mock_headers_sent($mockValue) {
  global $mockHeadersSent;
  global $headersSentMock;

  $mockHeadersSent = true;
  $headersSentMock = $mockValue;
}

function restore_headers_sent() {
  global $mockHeadersSent;
  global $headersSentMock;

  $mockHeadersSent = false;
  $headersSentMock = null;
}

function mock_apache_response_headers($functionExists, $mockValue = array()) {
  global $mockApacheResponseHeaders;
  global $functionExistsForApacheResponseHeadersMock;
  global $apacheResponseHeadersMock;

  $mockApacheResponseHeaders = true;
  $functionExistsForApacheResponseHeadersMock = $functionExists;
  $apacheResponseHeadersMock = $mockValue;
}

function restore_apache_response_headers() {
  global $mockApacheResponseHeaders;
  global $functionExistsForApacheResponseHeadersMock;
  global $apacheResponseHeadersMock;

  $mockApacheResponseHeaders = false;
  $functionExistsForApacheResponseHeadersMock = null;
  $apacheResponseHeadersMock = null;
}

function mock_header() {
  global $mockHeader;
  global $receivedHeaders;

  $mockHeader = true;
  $receivedHeaders = array();
}

function restore_header() {
  global $mockHeader;
  global $receivedHeaders;

  $mockHeader = false;
  $receivedHeaders = null;
}

function get_headers_received_by_header_mock() {
  global $receivedHeaders;

  return $receivedHeaders;
}

/** MOCKED FUNCTIONS **********************************************************/

function headers_sent() {
  global $mockHeadersSent;
  global $headersSentMock;

  if ($mockHeadersSent) {
     return $headersSentMock;
  } else {
    return call_user_func_array('\headers_sent', func_get_args());
  }
}

function function_exists($funcName) {
  global $mockApacheResponseHeaders;
  global $functionExistsForApacheResponseHeadersMock;

  if ($mockApacheResponseHeaders && $funcName === 'apache_response_headers') {
     return $functionExistsForApacheResponseHeadersMock;
  } else {
    return call_user_func_array('\function_exists', func_get_args());
  }
}

function apache_response_headers() {
  global $mockApacheResponseHeaders;
  global $apacheResponseHeadersMock;

  if ($mockApacheResponseHeaders) {
     return $apacheResponseHeadersMock;
  } else {
    return call_user_func_array('\apache_response_headers', func_get_args());
  }
}

function header($h) {
  global $mockHeader;
  global $receivedHeaders;

  if ($mockHeader) {
     array_push($receivedHeaders, $h);
  } else {
    return call_user_func_array('\header', func_get_args());
  }
}

