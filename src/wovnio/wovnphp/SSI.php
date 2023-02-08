<?php
namespace Wovnio\Wovnphp;

require_once(__DIR__ . '/../../wovn_helper.php');

// https://httpd.apache.org/docs/2.4/howto/ssi.html
// Server-side Include (SSI) is an Apache feature that allows adding limited dynamic content to HTML pages.
// For example,
//   - including other files <!--#include virtual="/footer.html" -->
//   - adding the current date <!--#echo var="DATE_LOCAL" -->
// Normally, this is done by Apache itself. But because we want to translate this extra content, we need to
// "manually" perform the SSI ourselves e.g. fetching the included files HTML and concatting it.
// Note that we have only implemented the "include" part of SSI. There are other functions of SSI like date insertion that we have not implemented.
class SSI
{
    // This is reading the "top level" page e.g. the original request.
    public static function readFile($includePath, $rootDir = null)
    {
        $rootDir = $rootDir ? $rootDir : dirname(dirname(dirname(dirname(__DIR__))));
        $limit = 10; // limit to 10 times nested SSI includes
        return self::readFileRecursive($includePath, $rootDir, $limit, $_GET);
    }

    private static function readFileRecursive($includePath, $rootDir, &$limit, $includeUrlQueryParams)
    {
        $ssiIncludeRegexp = '/<!--#include\s+virtual="(.+?)"\s*-->/';
        $includeDir = dirname($includePath);
        $code = self::getContents($includePath, $includeUrlQueryParams);
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
                $queryString = isset($pathAndQueryString[1]) ? $pathAndQueryString[1] : '';

                // the URL in the SSI include should not inherit the query parameters from the original request. They are a separate "request".
                $queryParams = array();
                if ($queryString) {
                    parse_str($queryString, $queryParams);
                }

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
                    return SSI::readFileRecursive($path, $rootDir, $limit, $queryParams);
                } else {
                    return '<!-- File not found: ' . $path . ' -->';
                }
            }, $code);
        }

        return $code;
    }

    private static function getContents($includePath, $queryParams)
    {
        ob_start();

        $copyOfQuery = $_GET;
        $_GET = $queryParams;

        include $includePath;

        $_GET = $copyOfQuery;
        $query_str_post = http_build_query($_GET);

        return ob_get_clean();
    }
}
