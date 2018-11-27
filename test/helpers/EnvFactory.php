<?php
namespace Wovnio\Test\Helpers;

class EnvFactory
{
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
