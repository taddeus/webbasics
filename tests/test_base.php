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
	function testCreate() {
		$this->assertEquals(BaseExtension::create('a', 'b'), new BaseExtension('a', 'b'));
	}
	
	function testPathWithSlash() {
		$this->assertEquals(Base::pathWithSlash('dirname'), 'dirname/');
		$this->assertEquals(Base::pathWithSlash('dirname/'), 'dirname/');
	}
}

?>