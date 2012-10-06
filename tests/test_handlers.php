<?php

require_once 'handlers.php';

class MyHandler extends webbasics\BaseHandler {
	function get(array $args=array()) {
		return 'get ' . implode('-', $args);
	}
	
	function getFoo() {
		return 'getFoo';
	}
	
	function getBar(array $args) {
		return 'getBar ' . implode('-', $args);
	}
	
	function post(array $args=array()) {
		return 'post ' . implode('-', $args);
	}
	
	function postFoo() {
		return 'postFoo';
	}
	
	function postBar(array $args) {
		return 'postBar ' . implode('-', $args);
	}
}

class HandlersTest extends PHPUnit_Framework_TestCase {
	private $myhandler;
	
	function setUp() {
		$this->myhandler = new MyHandler;
	}
	
	function testBaseHandlerGetNoMethodNoArgs() {
		$this->assertHandlesGet('get ', $this->myhandler, array());
	}
	
	function testBaseHandlerGetNoMethodArgs() {
		$this->assertHandlesGet('get baz', $this->myhandler, array('baz'));
	}
	
	function testBaseHandlerGetMethodNoArgs() {
		$this->assertHandlesGet('getFoo', $this->myhandler, array('foo'));
	}
	
	function testBaseHandlerGetMethodArgs() {
		$this->assertHandlesGet('getBar foo-baz', $this->myhandler, array('bar', 'foo', 'baz'));
	}
	
	function testBaseHandlerPostNoMethodNoArgs() {
		$this->assertHandlesPost('post ', $this->myhandler, array());
	}
	
	function testBaseHandlerPostNoMethodArgs() {
		$this->assertHandlesPost('post baz', $this->myhandler, array('baz'));
	}
	
	function testBaseHandlerPostMethodNoArgs() {
		$this->assertHandlesPost('postFoo', $this->myhandler, array('foo'));
	}
	
	function testBaseHandlerPostMethodArgs() {
		$this->assertHandlesPost('postBar foo-baz', $this->myhandler, array('bar', 'foo', 'baz'));
	}
	
	function assertHandlesGet($result, $handler, array $args=array()) {
		$this->assertHandlesMethod($result, $handler, $args, 'GET');
	}
	
	function assertHandlesPost($result, $handler, array $args=array()) {
		$this->assertHandlesMethod($result, $handler, $args, 'POST');
	}
	
	function assertHandlesMethod($result, $handler, array $args, $request_method) {
		$_SERVER['REQUEST_METHOD'] = $request_method;
		$this->assertEquals($result, $handler->handleRequest($args));
	}
}

?>