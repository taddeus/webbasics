<?php

require_once 'utils.php';
use webbasics as wb;

class UtilsTest extends PHPUnit_Framework_TestCase {
	function testCamelize() {
		$this->assertEquals('fooBarBaz', wb\camelize('foo bar baz'));
		$this->assertEquals('fooBarBaz', wb\camelize('foo_bar_baz'));
		$this->assertEquals('fooBarBaz', wb\camelize('foo-bar-baz'));
		$this->assertEquals('fooBarBaz', wb\camelize('foo_barBaz'));
	}
	
	/*
	 * @depends testCamelize
	 */
	function testCamelizePascalCase() {
		$this->assertEquals('FooBarBaz', wb\camelize('foo_bar_baz', true));
	}
}

?>