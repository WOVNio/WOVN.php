<?php
// This namespace cannot be Wovnio\Wovnphp\Tests\Helpers because it must
// redefine function in the scope of Wovnio\Wovnphp objects.
namespace Wovnio\Utils\RequestHandlers;

$mockCurl = false;
$extension_loaded = null;
$get_extension_funcs = null;
$curl_version = null;

/** MOCK HELPERS **************************************************************/

function mockCurl($curl_loaded, $curl_functions, $curl_protocols)
{
    global $mockCurl;
    global $extension_loaded;
    global $get_extension_funcs;
    global $curl_version;

    $mockCurl = true;
    $extension_loaded = $curl_loaded;
    $get_extension_funcs = $curl_functions;
    $curl_version = array('protocols' => $curl_protocols);
}

function restoreCurl() {
    global $mockCurl;
    global $extension_loaded;
    global $get_extension_funcs;
    global $curl_version;

    $mockCurl = false;
    $extension_loaded = null;
    $get_extension_funcs = null;
    $curl_version = null;
}

/** MOCKED FUNCTIONS **********************************************************/

// phpcs:disable Squiz.NamingConventions.ValidFunctionName.NotCamelCaps

function extension_loaded()
{
    global $mockCurl;
    global $extension_loaded;

    return $mockCurl ? $extension_loaded : call_user_func_array('\curl_loaded', func_get_args());
}

function get_extension_funcs()
{
    global $mockCurl;
    global $get_extension_funcs;

    return $mockCurl ? $get_extension_funcs : call_user_func_array('\get_extension_funcs', func_get_args());
}

function curl_version()
{
    global $mockCurl;
    global $curl_version;

    return $mockCurl ? $curl_version : call_user_func_array('\curl_version', func_get_args());
}
// phpcs:enable
