<?php
require_once(__DIR__ . '/wovn_interceptor.php');
require_once(__DIR__ . '/wovnio/wovnphp/SSI.php');
use Wovnio\Wovnphp\SSI;

function wovn_helper_detect_paths($base_dir, $path_of_url, $files) {
  $request_path = $base_dir . $path_of_url;
  $local_path = realpath($request_path);
  $inside_base_dir = $local_path && strpos($local_path, $base_dir) === 0;
  $local_path = $inside_base_dir ? $local_path : $base_dir;
  if (is_file($local_path)) {
    return array($local_path);
  } else if (is_dir($local_path)) {
    $local_dir = substr($local_path, 0, strlen($local_path)) === '/' ? $local_path : $local_path . '/';
    $detect_paths = array();
    foreach ($files as $file) {
      array_push($detect_paths, $local_dir . $file);
    }
    return $detect_paths;
  } else {
    return array();
  }
}

function wovn_helper_include_by_paths($paths) {
  foreach ($paths as $path) {
    if (is_file($path)) {
      include($path);
      return true;
    }
  }
  return false;
}

function wovn_helper_include_by_paths_with_ssi($paths) {
  foreach ($paths as $path) {
    if (is_file($path)) {
      echo SSI::include($path);
      return true;
    }
  }
  return false;
}
