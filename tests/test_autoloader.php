<?php

require_once 'SingletonTestCase.php';
require_once 'autoloader.php';
use webbasics\Autoloader;

define('PATH', 'tests/_files/');

class AutoloaderTest extends SingletonTestCase {
	function getClassName() {
		return 'webbasics\Autoloader';
	}
	
	function tearDown() {
		Autoloader::getInstance()->setThrowExceptions(false);
	}
	
	function testStripNamespace() {
		$rmethod = new ReflectionMethod('webbasics\Autoloader', 'stripNamespace');
		$rmethod->setAccessible(true);
		$this->assertEquals('Bar', $rmethod->invoke(null, 'foo', 'foo\Bar'));
		$this->assertEquals('Bar', $rmethod->invoke(null, '\foo', '\foo\Bar'));
	}
	
	function testAddDirectory() {
		$autoloader = Autoloader::getInstance();
		$rprop = new ReflectionProperty($autoloader, 'directories');
		$rprop->setAccessible(true);
		
		$autoloader->addDirectory(PATH);
		$this->assertEquals(array(
			'\\' => array(PATH)
		), $rprop->getValue($autoloader));
		
		$autoloader->addDirectory(PATH);
		$this->assertEquals(array(
			'\\' => array(PATH)
		), $rprop->getValue($autoloader));
		
		$autoloader->addDirectory('foo');
		$this->assertEquals(array(
			'\\' => array(PATH, 'foo/')
		), $rprop->getValue($autoloader));
		
		$autoloader->addDirectory('bar');
		$this->assertEquals(array(
			'\\' => array(PATH, 'foo/', 'bar/')
		), $rprop->getValue($autoloader));
		
		$autoloader->addDirectory('foodir', 'foo');
		$this->assertEquals(array(
			'\\' => array(PATH, 'foo/', 'bar/'),
			'\foo' => array('foodir/')
		), $rprop->getValue($autoloader));
		
		$autoloader->addDirectory('foobardir', 'foobar');
		$this->assertEquals(array(
			'\\' => array(PATH, 'foo/', 'bar/'),
			'\foo' => array('foodir/'),
			'\foobar' => array('foobardir/')
		), $rprop->getValue($autoloader));
	}
	
	function testCreatePath() {
		$rmethod = new ReflectionMethod('webbasics\Autoloader', 'createPath');
		$rmethod->setAccessible(true);
		$this->assertEquals($rmethod->invoke(null, 'Foo'), 'Foo.php');
		$this->assertEquals($rmethod->invoke(null, '\Foo'), 'Foo.php');
		$this->assertEquals($rmethod->invoke(null, 'foo\Bar'), 'foo/Bar.php');
		$this->assertEquals($rmethod->invoke(null, 'foo\Bar\Baz'), 'foo/Bar/Baz.php');
		$this->assertEquals($rmethod->invoke(null, 'fooBar\Baz'), 'fooBar/Baz.php');
		$this->assertEquals($rmethod->invoke(null, 'foo_bar\Baz'), 'foo_bar/Baz.php');
	}
	
	function testThrowExceptions() {
		$autoloader = Autoloader::getInstance();
		$this->assertFalse($autoloader->getThrowExceptions());
		$autoloader->setThrowExceptions(true);
		$this->assertTrue($autoloader->getThrowExceptions());
	}
	
	/**
	 * @depends testCreatePath
	 * @depends testThrowExceptions
	 */
	function testLoadClassNotFound() {
		$this->assertFalse(Autoloader::getInstance()->loadClass('foobar'));
	}
	
	/**
	 * @depends testLoadClassNotFound
	 * @expectedException webbasics\ClassNotFoundError
	 */
	function testLoadClassNotFoundException() {
		$autoloader = Autoloader::getInstance();
		$autoloader->setThrowExceptions(true);
		$autoloader->loadClass('foobar');
	}
	
	/**
	 * @depends testLoadClassNotFound
	 */
	function testLoadClassSuccess() {
		$autoloader = Autoloader::getInstance();
		$this->assertTrue($autoloader->loadClass('Foo'));
		$this->assertTrue(class_exists('Foo', false));
		$this->assertTrue($autoloader->loadClass('Foo\Bar'));
		$this->assertTrue(class_exists('Foo\Bar', false));
	}
}

?>