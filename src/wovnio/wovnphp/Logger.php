<?php
namespace Wovnio\Wovnphp;

/**
 * Logger implementation inspired by PSR3: https://www.php-fig.org/psr/psr-3/.
 * Unlike PSR3, the `log` function is private and there are no constants for
 * representing log levels.
 */
class Logger
{
  private static $logger = null;

  public $prefix;
  public $quiet;

  public static function get()
  {
    if (self::$logger === null) {
      self::$logger = new Logger();
    }

    return self::$logger;
  }

  public static function set($logger)
  {
    self::$logger = $logger;
  }

  public function __construct($quiet = false, $prefix = 'WOVN.php')
  {
    $this->prefix = $prefix;
    $this->quiet = $quiet;
  }

  public function emergency($message, $context = array())
  {
    $this->log('EMERGENCY', $message, $context);
  }

  public function alert($message, $context = array())
  {
    $this->log('ALERT', $message, $context);
  }

  public function critical($message, $context = array())
  {
    $this->log('CRITICAL', $message, $context);
  }

  public function error($message, $context = array())
  {
    $this->log('ERROR', $message, $context);
  }

  public function warning($message, $context = array())
  {
    $this->log('WARNING', $message, $context);
  }

  public function notice($message, $context = array())
  {
    $this->log('NOTICE', $message, $context);
  }

  public function info($message, $context = array())
  {
    $this->log('INFO', $message, $context);
  }

  public function debug($message, $context = array())
  {
    $this->log('DEBUG', $message, $context);
  }

  private function log($level, $message, $context)
  {
    if (!$this->quiet) {
      error_log("$this->prefix [$level] " . $this->interpolate($message, $context));
    }
  }

  private function interpolate($message, $context)
  {
    $replacements = array();

    foreach ($context as $key => $value) {
      if ($key === 'exception') {
        $replacements['{' . $key . '}'] = $value->getMessage();
      } elseif (!is_array($value) && (!is_object($value) || method_exists($value, '__toString'))) {
        $replacements['{' . $key . '}'] = $value;
      }
    }

    return strtr($message, $replacements);
  }
}
