<?php

require_once 'base.php';
use Minimalistic\asprintf;

class BaseExtension extends Minimalistic\Base {
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
		$this->assertEquals(Minimalistic\asprintf('%(foo) baz', array('foo' => 'bar')), 'bar baz');
		$this->assertEquals(Minimalistic\asprintf('%(foo) baz %(foo)',
			array('foo' => 'bar')), 'bar baz bar');
		$this->assertEquals(Minimalistic\asprintf('%(bar) baz %(foo)',
			array('foo' => 'bar', 'bar' => 'foobar')), 'foobar baz bar');
	}
}

?>