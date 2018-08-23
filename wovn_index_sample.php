<?php
# Enable WOVN.php library
require_once("WOVN.php/src/wovn_helper.php");

# Try read specific files if request url is end of slash
$files = array(
  "index.html",
  "index.shtml",
  "index.htm",
  "index.php",
  "index.php3",
  "index.phtml",
  "app.php"
);
$paths = wovn_helper_detect_paths(dirname(__FILE__), parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), $files);
# SSI USER: please swap comments on the two lines below
$included = wovn_helper_include_by_paths($paths);
# $included = wovn_helper_include_by_paths_with_ssi($paths);

# Set 404 status code if file not included
if (!$included) {
  header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");

  # Look for 404.html file in the root directory
  $paths_404 = [dirname(__FILE__) . "/404.html", ];
  $included_404 = wovn_helper_include_by_paths($paths_404);

  if (!$included_404) {
    echo "Page Not Found";
  }
}
