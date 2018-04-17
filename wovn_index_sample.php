<?php
# Enable WOVN.php library
require_once("WOVN.php/src/wovn_interceptor.php");

# Detect paths from REQUEST_URI
$request_path = dirname(__FILE__) . $_SERVER["REQUEST_URI"];
$local_path = realpath($request_path);
$valid = $local_path && strpos($local_path, dirname(__FILE__)) === 0;
if ($valid && is_file($local_path)) {
  $detect_paths = array($local_path);
} else if( $valid && is_dir($local_path)) {
  $local_dir = substr($local_path, 0, strlen($local_path)) == '/' ? $local_path : $local_path . '/';
  $detect_paths = array(
    $local_dir . "index.html",
    $local_dir . "index.shtml",
    $local_dir . "index.htm",
    $local_dir . "index.php",
    $local_dir . "index.cgi",
    $local_dir . "index.fcgi",
    $local_dir . "index.pl",
    $local_dir . "index.php3",
    $local_dir . "index.phtml",
    $local_dir . "index.pcgi"
  );
} else {
  $detect_paths = array();
}

# Check that the file is secure and
# Load the static file if it exists
$hit = false;
foreach ($detect_paths as $detect_path) {
  $path = realpath($detect_path);
  if ($path && is_file($path)) {
    $hit = true;
    include($path);
    break;
  }
}

# Set 404 status code if file not included
if (!$hit && array_key_exists("SERVER_PROTOCOL", $_SERVER)) {
  header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
}
?>
