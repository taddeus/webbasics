<?php

require_once 'base.php';
use webbasics\Base;

class BaseExtension extends Base {
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
		$this->assertEquals(webbasics\asprintf('%(foo) baz', array('foo' => 'bar')), 'bar baz');
		$this->assertEquals(webbasics\asprintf('%(foo) baz %(foo)',
			array('foo' => 'bar')), 'bar baz bar');
		$this->assertEquals(webbasics\asprintf('%(bar) baz %(foo)',
			array('foo' => 'bar', 'bar' => 'foobar')), 'foobar baz bar');
	}
	
	function test_path_with_slash() {
		$this->assertEquals(Base::path_with_slash('dirname'), 'dirname/');
		$this->assertEquals(Base::path_with_slash('dirname/'), 'dirname/');
	}
}

?>