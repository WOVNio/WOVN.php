<?php
// This namespace cannot be Wovnio\Wovnphp\Tests\Helpers because it must
// redefine function in the scope of Wovnio\Wovnphp objects.
namespace Wovnio\Utils\RequestHandlers;

$is_curl_mocked = false;
$mocked_extension_loaded = null;
$mocked_get_extension_funcs = null;
$mocked_curl_version = null;
$is_curl_exec_mocked = false;

/** MOCK HELPERS **************************************************************/

function mockCurl($curl_loaded, $curl_functions, $curl_protocols)
{
    global $is_curl_mocked, $mocked_extension_loaded, $mocked_get_extension_funcs, $mocked_curl_version;

    $is_curl_mocked = true;
    $mocked_extension_loaded = $curl_loaded;
    $mocked_get_extension_funcs = $curl_functions;
    $mocked_curl_version = array('protocols' => $curl_protocols);
}

function restoreCurl()
{
    global $is_curl_mocked, $mocked_extension_loaded, $mocked_get_extension_funcs, $mocked_curl_version;

    $is_curl_mocked = false;
    $mocked_extension_loaded = null;
    $mocked_get_extension_funcs = null;
    $mocked_curl_version = null;
}

/**
 * Mocks a cURL session. $curl_response is the raw response (headers + body), as returned by
 * curl_exec with CURLOPT_HEADER, or false for a transport failure.
 */
function mockCurlExec($curl_response, $http_code, $curl_error = '', $curl_errno = 0)
{
    global $is_curl_exec_mocked, $mocked_curl_response, $mocked_curl_http_code, $mocked_curl_error, $mocked_curl_errno;
    $is_curl_exec_mocked = true;
    $mocked_curl_response = $curl_response;
    $mocked_curl_http_code = $http_code;
    $mocked_curl_error = $curl_error;
    $mocked_curl_errno = $curl_errno;
}

function restoreCurlExec()
{
    global $is_curl_exec_mocked, $mocked_curl_response, $mocked_curl_http_code, $mocked_curl_error, $mocked_curl_errno;
    $is_curl_exec_mocked = false;
    $mocked_curl_response = null;
    $mocked_curl_http_code = null;
    $mocked_curl_error = null;
    $mocked_curl_errno = null;
}

/** MOCKED FUNCTIONS **********************************************************/

// phpcs:disable Squiz.NamingConventions.ValidFunctionName.NotCamelCaps

function extension_loaded()
{
    global $is_curl_mocked, $mocked_extension_loaded;
    return $is_curl_mocked ? $mocked_extension_loaded : call_user_func_array('\curl_loaded', func_get_args());
}

function get_extension_funcs()
{
    global $is_curl_mocked, $mocked_get_extension_funcs;
    return $is_curl_mocked ? $mocked_get_extension_funcs : call_user_func_array('\get_extension_funcs', func_get_args());
}

function curl_version()
{
    global $is_curl_mocked, $mocked_curl_version;
    return $is_curl_mocked ? $mocked_curl_version : call_user_func_array('\curl_version', func_get_args());
}

function curl_init()
{
    global $is_curl_exec_mocked;
    return $is_curl_exec_mocked ? 'mocked_curl_session' : call_user_func_array('\curl_init', func_get_args());
}

function curl_setopt_array()
{
    global $is_curl_exec_mocked;
    return $is_curl_exec_mocked ? true : call_user_func_array('\curl_setopt_array', func_get_args());
}

function curl_exec()
{
    global $is_curl_exec_mocked, $mocked_curl_response;
    return $is_curl_exec_mocked ? $mocked_curl_response : call_user_func_array('\curl_exec', func_get_args());
}

function curl_getinfo($curl_session, $option)
{
    global $is_curl_exec_mocked, $mocked_curl_response, $mocked_curl_http_code;
    if (!$is_curl_exec_mocked) {
        return call_user_func_array('\curl_getinfo', func_get_args());
    }

    switch ($option) {
        case CURLINFO_HTTP_CODE:
            return $mocked_curl_http_code;
        case CURLINFO_HEADER_SIZE:
            $header_end = $mocked_curl_response === false ? false : strpos($mocked_curl_response, "\r\n\r\n");
            return $header_end === false ? 0 : $header_end + 4;
    }
}

function curl_error()
{
    global $is_curl_exec_mocked, $mocked_curl_error;
    return $is_curl_exec_mocked ? $mocked_curl_error : call_user_func_array('\curl_error', func_get_args());
}

function curl_errno()
{
    global $is_curl_exec_mocked, $mocked_curl_errno;
    return $is_curl_exec_mocked ? $mocked_curl_errno : call_user_func_array('\curl_errno', func_get_args());
}

function curl_close()
{
    global $is_curl_exec_mocked;
    if (!$is_curl_exec_mocked) {
        call_user_func_array('\curl_close', func_get_args());
    }
}

// phpcs:enable
