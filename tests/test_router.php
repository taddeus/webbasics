<?php

require_once 'router.php';
use webbasics\Router;
use webbasics\RouteHandler;

function test_handler_no_args() {
	return true;
}

function test_handler_arg(array $args) {
	return $args[0];
}

function test_handler_args(array $args) {
	list($arg0, $arg1) = $args;
	return $arg1 . $arg0;
}

class TestHandler implements RouteHandler {
	function handleRequest(array $data) {
		return $data[0];
	}
}

class InterfacelessHandler {
	function handleRequest(array $data) {}
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
	
	function testAddRouteCallableSuccess() {
		$this->router->addRoute('(foobar)', 'test_handler_arg');
		$this->assertEquals('foobar', $this->router->callHandler('foobar'));
	}
	
	/**
	 * @expectedException \InvalidArgumentException
	 */
	function testAddRouteCallableFailure() {
		$this->router->addRoute('(foobar)', 'non_existing_function');
	}
	
	function testAddRouteHandlerSuccess() {
		$this->router->addRoute('(foobar)', 'TestHandler');
		$this->assertEquals('foobar', $this->router->callHandler('foobar'));
	}
	
	/**
	 * @expectedException \InvalidArgumentException
	 */
	function testAddRouteHandlerNoString() {
		$this->router->addRoute('(foobar)', new TestHandler);
	}
	
	/**
	 * @expectedException \InvalidArgumentException
	 */
	function testAddRouteHandlerNonExisting() {
		$this->router->addRoute('(foobar)', 'NonExistingHandler');
	}
	
	/**
	 * @expectedException \InvalidArgumentException
	 */
	function testAddRouteHandlerWithoutInterface() {
		$this->router->addRoute('(foobar)', 'InterfacelessHandler');
	}
}

?>