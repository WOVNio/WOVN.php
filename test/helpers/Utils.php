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

    public static function fetchURL($url, $timeout = 3)
    {
        $return = new stdClass;
        $return->headers = array();
        $return->body = null;
        $return->error = null;
        $return->statusCode = null;
        $return->curl_errno = null;

        $curl_session = curl_init($url);

        curl_setopt_array($curl_session, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array(),
            CURLOPT_PATH_AS_IS => true,
        ));

        $response = curl_exec($curl_session);
        $header_size = curl_getinfo($curl_session, CURLINFO_HEADER_SIZE);
        $return->headers = $response ? explode("\r\n", substr($response, 0, $header_size)) : array();
        $return->statusCode = curl_getinfo($curl_session, CURLINFO_HTTP_CODE);

        if (curl_error($curl_session) !== '') {
            $return->curl_errno = curl_errno($curl_session);

            curl_close($curl_session);

            return $return;
        }

        $return->body = substr($response, $header_size);

        curl_close($curl_session);

        return $return;
    }
}
