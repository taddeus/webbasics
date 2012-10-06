<?php

abstract class SingletonTestCase extends PHPUnit_Framework_TestCase {
	private $rclass;
	
	abstract function getClassName();
	
	function setUp() {
		$this->rclass = new ReflectionClass($this->getClassName());
	}
	
	function testConstructorPrivateness() {
		$rmethod = new ReflectionMethod($this->getClassName(), '__construct');
		$this->assertTrue($rmethod->isPrivate());
	}
	
	function testInstanceVariable() {
		$this->assertTrue($this->rclass->hasProperty('instance'));
		$rprop = new ReflectionProperty($this->getClassName(), 'instance');
		$this->assertTrue($rprop->isPrivate());
		$this->assertTrue($rprop->isStatic());
	}
	
	function testGetInstanceMethod() {
		$this->assertTrue($this->rclass->hasMethod('getInstance'));
		$rmethod = new ReflectionMethod($this->getClassName(), 'getInstance');
		$this->assertTrue($rmethod->isPublic());
		$this->assertTrue($rmethod->isStatic());
		$this->assertEquals(0, $rmethod->getNumberOfParameters());
	}
}