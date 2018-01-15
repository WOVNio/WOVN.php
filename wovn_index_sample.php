<?php
# Enable WOVN.php library
require_once("WOVN.php/src/wovn_interceptor.php");
# Load the static file if it exists
if (file_exists(dirname(__FILE__) . $_SERVER["REQUEST_URI"])) {
  include(dirname(__FILE__) . $_SERVER["REQUEST_URI"]);
}
?>
