<?php

require_once 'utils.php';
use webbasics as wb;

class UtilsTest extends PHPUnit_Framework_TestCase {
	function testAsprintf() {
		$this->assertEquals(webbasics\asprintf('%(foo) baz', array('foo' => 'bar')), 'bar baz');
		$this->assertEquals(webbasics\asprintf('%(foo) baz %(foo)',
			array('foo' => 'bar')), 'bar baz bar');
		$this->assertEquals(webbasics\asprintf('%(bar) baz %(foo)',
			array('foo' => 'bar', 'bar' => 'foobar')), 'foobar baz bar');
	}
	
	function testCamelizeSimple() {
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