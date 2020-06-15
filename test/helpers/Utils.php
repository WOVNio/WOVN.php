<?php
namespace Wovnio\Test\Helpers;

use stdClass;

class Utils
{
    public static function cleanUpDirectory($path)
    {
        $scanned = array_diff(scandir($path), array('..', '.'));
        foreach ($scanned as $fileName) {
            $filePath = $path . '/' . $fileName;
            $rmOption = is_dir($filePath) ? '-rf' : '-f';

            exec(sprintf('rm %s %s', $rmOption, escapeshellarg($filePath)));
        }
    }

    public static function writeFile($filePath, $contents)
    {
        $content = is_array($contents) ? implode("\n", $contents) : $contents;
        file_put_contents($filePath, $content);
    }

    public static function fetchURL($url, $timeout = 3)
    {
        $return = new stdClass;
        $return->headers = array();
        $return->body = null;
        $return->error = null;
        $return->statusCode = null;

        $http_context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'timeout' => $timeout,
                'ignore_errors' => true,
            )
        ));

        $return->body = @file_get_contents($url, false, $http_context);
        $response_headers = $http_response_header;

        if (preg_match('{HTTP\/\S*\s(\d{3})}', $response_headers[0], $match)) {
            $return->statusCode = $match[1];
        }

        foreach ($response_headers as $value) {
            if (preg_match('{([^:]+): (.+)}', $value, $match)) {
                $return->headers[ $match[0] ] = $match[1];
            }
        }

        return $return;
    }
}
