<?php
namespace Wovnio\Wovnphp\Tests\Unit;

trait AbstractRequestHandlerTrait
{
    public $arguments = array();
    public function sendRequest($method, $url, $data, $timeout = null)
    {
        array_push($this->arguments, array($method, $url, $data, $timeout));
        return $this->abstractSendRequest();
    }

    public abstract function abstractSendRequest();
}
