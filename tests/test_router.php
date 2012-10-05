<?php

require_once 'router.php';
use webbasics\Router;

function test_handler_no_args() {
	return true;
}

function test_handler_arg($arg) {
	return $arg;
}

function test_handler_args($arg0, $arg1) {
	return $arg1 . $arg0;
}

class RouterTest extends PHPUnit_Framework_TestCase {
	function setUp() {
		$this->router = new Router(array(
			'foo' => 'test_handler_no_args',
			'(ba[rz])' => 'test_handler_arg',
			'(ba[rz])(ba[rz])' => 'test_handler_args',
		));
	}
	
	function test_call_handler_success() {
		$this->assertEquals(true, $this->router->call_handler('foo'));
		$this->assertEquals('bar', $this->router->call_handler('bar'));
		$this->assertEquals('baz', $this->router->call_handler('baz'));
		$this->assertEquals('barbaz', $this->router->call_handler('bazbar'));
	}
	
	function test_call_handler_failure() {
		$this->assertFalse($this->router->call_handler('barfoo'));
	}
	
	function test_call_handler_skip() {
		$foo = 'foo';
		$bar = function() use (&$foo) { $foo = 'bar'; return false; };
		$baz = function() { return; };
		$router = new Router(array('.*' => $bar, 'baz' => $baz));
		$router->call_handler('baz');
		$this->assertEquals('bar', $foo);
	}
	
	function test_add_route() {
		$this->router->add_route('(foobar)', 'test_handler_arg');
		$this->assertEquals('foobar', $this->router->call_handler('foobar'));
	}
}

?>