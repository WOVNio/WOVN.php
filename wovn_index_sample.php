<?php
# Enable WOVN.php library
require_once("WOVN.php/src/wovn_interceptor.php");
require_once("WOVN.php/src/wovn_helper.php");

# SSI USERS: set to true to translate SSI content
$wovn_use_ssi = false;

$wovn_included = false;
$wovn_parsed_url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if ($wovn_parsed_url) {
    $wovn_paths = wovn_helper_detect_paths(dirname(__FILE__), $wovn_parsed_url);

    if ($wovn_use_ssi) {
        $wovn_included = wovn_helper_include_by_paths_with_ssi($wovn_paths);
    } else {
        $wovn_file_to_include = wovn_helper_get_first_file_path($wovn_paths);
        if ($wovn_file_to_include) {
            chdir(dirname($wovn_file_to_include));
            include($wovn_file_to_include);
            $wovn_included = true;
        }
    }
}

# Set 404 status code if file not included
if (!$wovn_included) {
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");

    # Look for 404.html file in the root directory
    $wovn_paths_404 = array(dirname(__FILE__) . "/404.html");

    $wovn_included_404 = false;
    if ($wovn_use_ssi) {
        $wovn_included_404 = wovn_helper_include_by_paths_with_ssi($wovn_paths_404);
    } else {
        $wovn_file_to_include = wovn_helper_get_first_file_path($wovn_paths_404);
        if ($wovn_file_to_include) {
            chdir(dirname($wovn_file_to_include));
            include($wovn_file_to_include);
            $wovn_included_404 = true;
        }
    }

    if (!$wovn_included_404) {
        echo "Page Not Found";
    }
}
