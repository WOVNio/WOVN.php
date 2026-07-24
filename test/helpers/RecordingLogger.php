<?php
namespace Wovnio\Wovnphp;

require_once 'src/wovnio/wovnphp/Logger.php';

/**
 * Test logger that records the messages passed to `error` so tests can assert
 * whether the error path was taken. Other levels are swallowed.
 */
class RecordingLogger extends Logger
{
    public $errors = array();

    public function __construct()
    {
        parent::__construct('RecordingLogger');
    }

    public function error($message, $context = array())
    {
        $this->errors[] = $message;
    }

    public function warning($message, $context = array())
    {
    }

    public function info($message, $context = array())
    {
    }

    public function debug($message, $context = array())
    {
    }
}
