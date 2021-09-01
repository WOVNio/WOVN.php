<?php
namespace Wovnio\Wovnphp;

require_once(__DIR__ . '/../../wovn_helper.php');

class SSI
{
    public static function readFile($includePath, $rootDir = null)
    {
        $rootDir = $rootDir ? $rootDir : dirname(dirname(dirname(dirname(__DIR__))));
        $limit = 10; // limit to 10 times nested SSI includes
        return self::readFileRecursive($includePath, $rootDir, $limit);
    }

    public static function readFileRecursive($includePath, $rootDir, &$limit)
    {
        $ssiIncludeRegexp = '/<!--#include\s+virtual="(.+?)"\s*-->/';
        $includeDir = dirname($includePath);
        $code = self::getContents($includePath);
        $fixSSIPath = function ($path, $dir) {
            if (!is_file($path)) {
                $candidates = wovn_helper_detect_paths($dir, $path);

                foreach ($candidates as $candidate) {
                    if (is_file($candidate)) {
                        return $candidate;
                    }
                }
            }

            return $path;
        };

        while (preg_match($ssiIncludeRegexp, $code)) {
            $code = preg_replace_callback($ssiIncludeRegexp, function ($match) use ($rootDir, $includeDir, $limit, $fixSSIPath) {
                $pathAndQueryString = explode('?', $match[1]);
                $ssiPath = $pathAndQueryString[0];
                if (substr($ssiPath, 0, 1) == '/') {
                    $path = $fixSSIPath($ssiPath, $rootDir);
                } else {
                    $path = $fixSSIPath($ssiPath, $includeDir);
                }
                --$limit;
                if ($limit <= 0) {
                    return '<!-- File does not include by limitation: ' . $path . '-->';
                }

                if (file_exists($path)) {
                    return SSI::readFileRecursive($path, $rootDir, $limit);
                } else {
                    return '<!-- File not found: ' . $path . ' -->';
                }
            }, $code);
        }

        return $code;
    }

    private static function getContents($includePath)
    {
        ob_start();
        include $includePath;

        return ob_get_clean();
    }
}
