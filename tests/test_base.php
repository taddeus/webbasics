<?php

require_once 'base.php';

class BaseExtension extends BasicWeb\Base {
	function __construct($foo, $bar) {
		$this->foo = $foo;
		$this->bar = $bar;
	}
}

class BaseTest extends PHPUnit_Framework_TestCase {
	function test_create() {
		$this->assertEquals(BaseExtension::create('a', 'b'), new BaseExtension('a', 'b'));
	}
	
	function test_asprintf() {
		$this->assertEquals(BasicWeb\asprintf('%(foo) baz', array('foo' => 'bar')), 'bar baz');
		$this->assertEquals(BasicWeb\asprintf('%(foo) baz %(foo)',
			array('foo' => 'bar')), 'bar baz bar');
		$this->assertEquals(BasicWeb\asprintf('%(bar) baz %(foo)',
			array('foo' => 'bar', 'bar' => 'foobar')), 'foobar baz bar');
	}
}

?>