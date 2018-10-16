<?php
require_once(__DIR__ . '/wovnio/wovnphp/SSI.php');
use Wovnio\Wovnphp\SSI;

function reduce_slashes($path)
{
    # Reduces a sequence of slashes to a single slash
    # e.g. '///./////.///' -> '/././'
    $i = 0;
    while ($i + 1 < strlen($path)) {
        if ($path[$i] == '/' && $path[$i + 1] == '/') {
            $path = substr($path, 0, $i) . substr($path, $i + 1, strlen($path));
        } else {
            $i++;
        }
    }
    return $path;
}

function remove_dots_from_path($path)
{
    # Removes '/./ in paths and resolves '/../' in paths
    # From https://tomnomnom.com/posts/realish-paths-without-realpath
    # See also http://php.net/manual/en/function.realpath.php#84012
    $path = reduce_slashes($path);

    $path_parts = explode('/', $path);
    $tmp_out = array();
    foreach ($path_parts as $part) {
        if ($part == '.') {
            continue;
        }
        if ($part == '..') {
            array_pop($tmp_out);
            continue;
        }
        $tmp_out[] = $part;
    }
    return implode('/', $tmp_out);
}

function wovn_helper_default_index_files()
{
    if (defined('WOVNPHP_DEFAULT_INDEX_FILE')) {
        return array(WOVNPHP_DEFAULT_INDEX_FILE);
    }

    return array(
        "index.html",
        "index.shtml",
        "index.htm",
        "index.php",
        "index.php3",
        "index.phtml",
        "app.php"
    );
}

function wovn_helper_detect_paths($local_dir, $path_of_url)
{
    $base_dir = realpath(remove_dots_from_path($local_dir));
    $request_path = $base_dir . '/' . $path_of_url;
    $local_path = realpath(remove_dots_from_path($request_path));
    $inside_base_dir = $local_path && strpos($local_path, $base_dir) === 0;
    $local_path = $inside_base_dir ? $local_path : false;

    if ($local_path && is_file($local_path)) {
        return array($local_path);
    } elseif (is_dir($local_path)) {
        $local_dir = substr($local_path, 0, strlen($local_path)) === '/' ? $local_path : $local_path . '/';
        $detect_paths = array();
        foreach (wovn_helper_default_index_files() as $index_file) {
            array_push($detect_paths, $local_dir . $index_file);
        }
        return $detect_paths;
    } else {
        return array();
    }
}

function wovn_helper_include_by_paths($paths)
{
    foreach ($paths as $path) {
        if (is_file($path)) {
            include($path);
            return true;
        }
    }
    return false;
}

function wovn_helper_include_by_paths_with_ssi($paths)
{
    foreach ($paths as $path) {
        if (is_file($path)) {
            echo SSI::readFile($path);
            return true;
        }
    }
    return false;
}
