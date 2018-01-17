<?php
# Enable WOVN.php library
require_once("WOVN.php/src/wovn_interceptor.php");
# Check that the file is secure and
# Load the static file if it exists
$real_final_path = realpath(dirname(__FILE__) . $_SERVER["REQUEST_URI"]);
if (strpos($real_final_path, dirname(__FILE__)) !== false &&
    file_exists($real_final_path)) {
  include($real_final_path);
}
?>
