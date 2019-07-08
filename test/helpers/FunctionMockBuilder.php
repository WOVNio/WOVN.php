<?php

namespace Wovnio\Test\Helpers;

use phpmock\MockBuilder;

class FunctionMockBuilder extends MockBuilder
{
	public static function build_function_mock($name, $return, $namespace=null) {
		$func = is_callable($return) ? $return : function () use (&$return) { return $return; };
		if (!$namespace) {
      $trace = debug_backtrace();
			$namespace = preg_replace('/\\\\[^\\\\]*?$/', '', $trace[1]['class']);
		}
		$mock = new FunctionMockBuilder($name, $func, $namespace);

 		return $mock->build();
	}

 	function __construct($name, $func, $namespace='') {
		$this->setNamespace($namespace);
		$this->setName($name);
		$this->setFunction($func);
	}
}
