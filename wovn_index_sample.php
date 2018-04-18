<?php
# Enable WOVN.php library
require_once("WOVN.php/src/wovn_interceptor.php");
require_once("WOVN.php/src/wovn_helper.php");

# Try read specific files if request url is end of slash
$files = array(
  "index.html",
  "index.shtml",
  "index.htm",
  "index.php",
  "index.php3",
  "index.phtml"
);
$paths = wovn_helper_detect_paths(dirname(__FILE__), $_SERVER["REQUEST_URI"], $files);
$included = wovn_helper_include_by_paths($paths);

# Set 404 status code if file not included
if (!$included) {
  header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
  echo "Page Not Found";
}
