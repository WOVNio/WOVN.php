<?php
namespace Wovnio\Wovnphp;

class CustomDomainLang
{
    private $host;
    private $path;
    private $lang;

    public function __construct($host, $path, $lang)
    {
        $this->host = $host;
        $this->path = substr($path, -1) === '/' ? $path : $path . '/';
        $this->lang = $lang;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function isMatch($parsedUrl)
    {
        $host = $parsedUrl['host'];
        $path = array_key_exists('path', $parsedUrl) ? $parsedUrl['path'] : '/';
        return strtolower($host) === strtolower($this->host) && $this->pathIsEqualOrSubsetOf($this->path, $path);
    }

    private function pathIsEqualOrSubsetOf($path1, $path2)
    {
        // split by delimiter and remove spaces and empty strings
        $path1Segments = array_filter(array_map('trim', explode('/', $path1)), 'strlen');
        $path2Segments = array_filter(array_map('trim', explode('/', $path2)), 'strlen');

        $length = count($path1Segments);
        $diff = array_diff_assoc(
            array_slice($path1Segments, 0, $length, false),
            array_slice($path2Segments, 0, $length, false)
        );
        return empty($diff);
    }

    public function getHostAndPathWithoutTrailingSlash()
    {
        $hostAndPath = $this->host . $this->path;
        return substr($hostAndPath, -1) === '/' ? substr($hostAndPath, 0, -1) : $hostAndPath;
    }
}
