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
	
	function testCallHandlerSuccess() {
		$this->assertEquals(true, $this->router->callHandler('foo'));
		$this->assertEquals('bar', $this->router->callHandler('bar'));
		$this->assertEquals('baz', $this->router->callHandler('baz'));
		$this->assertEquals('barbaz', $this->router->callHandler('bazbar'));
	}
	
	function testCallHandlerFailure() {
		$this->assertFalse($this->router->callHandler('barfoo'));
	}
	
	function testCallHandlerSkip() {
		$foo = 'foo';
		$bar = function() use (&$foo) { $foo = 'bar'; return false; };
		$baz = function() { return; };
		$router = new Router(array('.*' => $bar, 'baz' => $baz));
		$router->callHandler('baz');
		$this->assertEquals('bar', $foo);
	}
	
	function testAddRoute() {
		$this->router->addRoute('(foobar)', 'test_handler_arg');
		$this->assertEquals('foobar', $this->router->callHandler('foobar'));
	}
}

?>