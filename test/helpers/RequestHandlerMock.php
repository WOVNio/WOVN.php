<?php
namespace Wovnio\Wovnphp\Tests\Unit;

class RequestHandlerMock
{
    public $arguments = array();
    public $return = array(null, null, null);
    public function __construct($response, $header = null, $error = null)
    {
        $this->return = array($response, $header, $error);
    }
    public function sendRequest($method, $url, $data, $timeout = null)
    {
        array_push($this->arguments, array($method, $url, $data, $timeout));
        return $this->return;
    }
}
