<?php
# Enable WOVN.php library
require_once("WOVN.php/src/wovn_interceptor.php");
require_once("WOVN.php/src/wovn_helper.php");

$parsed_url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if ($parsed_url) {
    $paths = wovn_helper_detect_paths(dirname(__FILE__), $parsed_url);

    # SSI USER: please swap comments on the two lines below
    # (also see the SSI comment in the 404 section below)
    $included = wovn_helper_include_by_paths($paths);
    # $included = wovn_helper_include_by_paths_with_ssi($paths);
} else {
    $included = false;
}

# Set 404 status code if file not included
if (!$included) {
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");

    # Look for 404.html file in the root directory
    $paths_404 = array(dirname(__FILE__) . "/404.html");

    # SSI USER: please swap comments on the two lines below as you did above
    $included_404 = wovn_helper_include_by_paths($paths_404);
    # $included_404 = wovn_helper_include_by_paths_with_ssi($paths_404);

    if (!$included_404) {
        echo "Page Not Found";
    }
}
