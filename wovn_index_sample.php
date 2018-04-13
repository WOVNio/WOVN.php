<?php
# Enable WOVN.php library
require_once("WOVN.php/src/wovn_interceptor.php");
# Check that the file is secure and
# Load the static file if it exists
$path = $_SERVER["REQUEST_URI"];

# Uncomment below when you set "path" as url_pattern_name
# $path = preg_replace('/^\/(?:ar|bg|zh-CHS|zh-CHT|da|nl|en|fi|fr|de|el|he|id|it|ja|ko|ms|my|ne|no|pl|pt|ru|es|sv|th|hi|tr|uk|vi)($|\/|\?)/', '$1', $path);

$real_final_path = realpath(dirname(__FILE__) . $path);
if (strpos($real_final_path, dirname(__FILE__)) !== false &&
    file_exists($real_final_path)) {
  include($real_final_path);
}
?>
