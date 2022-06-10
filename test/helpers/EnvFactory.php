<?php
namespace Wovnio\Test\Helpers;

class EnvFactory
{
    // Convert https://site.com/foo/?query into REQUEST_URI, SERVER_NAME etc variables
    public static function makeEnvFromUrl($requestUrl)
    {
        $parsed_url = parse_url($requestUrl);
        $path_and_query = $parsed_url['path'];
        $env = array(
            'HTTP_SCHEME' => $parsed_url['scheme'],
            'HTTP_HOST' => $parsed_url['host'],
        );

        if ($parsed_url['scheme'] === 'https') {
            $env['HTTPS'] = 'on';
        }

        if (array_key_exists('query', $parsed_url)) {
            $env['QUERY_STRING'] = $parsed_url['query'];
            $path_and_query .= '?' . $parsed_url['query'];
        }
        $env['REQUEST_URI'] = $path_and_query;
        return $env;
    }

    public static function fromFixture($fixture, $envOverwrite = array())
    {
        $iniFilename = self::fixture2Filename($fixture);
        $iniFile = parse_ini_file(dirname(__FILE__) . '/../fixtures/env/' . $iniFilename);

        return array_merge($iniFile['env'], $envOverwrite);
    }

    private static function fixture2Filename($fixture)
    {
        if ($fixture) {
            return preg_replace('/(.ini)?$/', '.ini', $fixture);
        }

        return 'default.ini';
    }
}
