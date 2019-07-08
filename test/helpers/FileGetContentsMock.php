<?php
// This namespace cannot be Wovnio\Wovnphp\Tests\Helpers because it must
// redefine function in the scope of Wovnio\Wovnphp objects.
namespace Wovnio\Utils\RequestHandlers;

$mock_file_get_contents = false;
$ini_get_allow_url_fopen = false;

/** MOCK HELPERS **************************************************************/

function mockFileGetContents($allow_url_fopen)
{
    global $mock_file_get_contents;
    global $ini_get_allow_url_fopen;

    $mock_file_get_contents = true;
    $ini_get_allow_url_fopen = $allow_url_fopen;
}

function restoreFileGetContents()
{
    global $mock_file_get_contents;
    global $ini_get_allow_url_fopen;

    $mock_file_get_contents = false;
    $ini_get_allow_url_fopen = false;
}

/** MOCKED FUNCTIONS **********************************************************/

// phpcs:disable Squiz.NamingConventions.ValidFunctionName.NotCamelCaps

function ini_get($setting_name)
{
    global $mock_file_get_contents;
    global $ini_get_allow_url_fopen;

    return ($mock_file_get_contents && $setting_name === 'allow_url_fopen') ? $ini_get_allow_url_fopen : call_user_func_array('\ini_get', func_get_args());
}

// phpcs:enable
