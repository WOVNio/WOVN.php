<?php


namespace Wovnio\Utils\HTTPHeaderParser;


class HTTPHeaderParser
{
    public static function parseRawHeader($rawHeader) {
        if (!$rawHeader) {
            return array();
        }

        $rawHeader = explode("\r\n", $rawHeader);
        $parsedHeaders = array('status' => $rawHeader[0]);
        foreach ($rawHeader as $value) {
            $exploded = explode(':', $value, 2);
            if (sizeof($exploded) == 2) {
                $parsedHeaders[trim($exploded[0])] = trim($exploded[1]);
            }
        }
        return $parsedHeaders;
    }

    public static function parseRawResponse($rawResponse, $headerSize) {
        if (!$rawResponse || !$headerSize) {
            return array();
        }

        $headers = substr($rawResponse, 0, $headerSize);
        return self::parseRawHeader($headers);
    }
}
