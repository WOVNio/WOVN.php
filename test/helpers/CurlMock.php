<?php
// This namespace cannot be Wovnio\Wovnphp\Tests\Helpers because it must
// redefine function in the scope of Wovnio\Wovnphp objects.
namespace Wovnio\Utils\RequestHandlers;

$is_curl_mocked = false;
$mocked_extension_loaded = null;
$mocked_get_extension_funcs = null;
$mocked_curl_version = null;

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

function mockCurlExec($curl_response, $callback)
{
    global $is_curl_exec_mocked, $mocked_curl_response, $curl_exec_callback;
    $is_curl_exec_mocked = true;
    $mocked_curl_response = $curl_response;
    $curl_exec_callback = $callback;
}

function restoreCurlExec()
{
    global $is_curl_exec_mocked, $mocked_curl_response, $curl_exec_callback;
    $is_curl_exec_mocked = false;
    $mocked_curl_response = null;
    $curl_exec_callback = null;
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

// phpcs:enable
