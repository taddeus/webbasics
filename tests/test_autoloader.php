<?php

require_once 'autoloader.php';
use webbasics\Autoloader;

define('PATH', 'tests/_files/');

class AutoloaderTest extends PHPUnit_Framework_TestCase {
	function setUp() {
		$this->autoloader = new Autoloader(PATH);
	}
	
	function testSetRootNamespace() {
		$this->assertAttributeEquals('\\', 'root_namespace', $this->autoloader);
		$this->autoloader->setRootNamespace('Foo');
		$this->assertAttributeEquals('Foo\\', 'root_namespace', $this->autoloader);
		$this->autoloader->setRootNamespace('Foo\\');
		$this->assertAttributeEquals('Foo\\', 'root_namespace', $this->autoloader);
	}
	
	/**
	 * @depends testSetRootNamespace
	 */
	function testGetRootNamespace() {
		$this->autoloader->setRootNamespace('Foo');
		$this->assertEquals($this->autoloader->getRootNamespace(), 'Foo\\');
	}
	
	/**
	 * @depends testSetRootNamespace
	 */
	function testConstructRootNamespace() {
		$autoloader = new Autoloader(PATH, 'Foo');
		$this->assertAttributeEquals('Foo\\', 'root_namespace', $autoloader);
	}
	
	/**
	 * @depends testSetRootNamespace
	 */
	function testStripRootNamespace() {
		$strip = new ReflectionMethod('webbasics\Autoloader', 'stripRootNamespace');
		$strip->setAccessible(true);
		
		$this->autoloader->setRootNamespace('Foo');
		$this->assertEquals($strip->invoke($this->autoloader, 'Foo\Bar'), 'Bar');
	}
	
	function testSetRootDirectory() {
		$this->autoloader->setRootDirectory('tests');
		$this->assertEquals($this->autoloader->getRootDirectory(), 'tests/');
	}
	
	function testClassnameToFilename() {
		$this->assertEquals(Autoloader::classnameToFilename('Foo'), 'foo');
		$this->assertEquals(Autoloader::classnameToFilename('FooBar'), 'foo_bar');
		$this->assertEquals(Autoloader::classnameToFilename('fooBar'), 'foo_bar');
		$this->assertEquals(Autoloader::classnameToFilename('FooBarBaz'), 'foo_bar_baz');
	}
	
	/**
	 * @depends testClassnameToFilename
	 */
	function testCreatePath() {
		$this->assertEquals($this->autoloader->createPath('Foo'), PATH.'foo.php');
		$this->assertEquals($this->autoloader->createPath('\Foo'), PATH.'foo.php');
		$this->assertEquals($this->autoloader->createPath('Foo\Bar'), PATH.'foo/bar.php');
		$this->assertEquals($this->autoloader->createPath('Foo\Bar\Baz'), PATH.'foo/bar/baz.php');
		$this->assertEquals($this->autoloader->createPath('FooBar\Baz'), PATH.'foo_bar/baz.php');
	}
	
	function testThrowErrors() {
		$this->assertFalse($this->autoloader->getThrowErrors());
		$this->autoloader->setThrowErrors(true);
		$this->assertTrue($this->autoloader->getThrowErrors());
	}
	
	/**
	 * @depends testCreatePath
	 * @depends testThrowErrors
	 */
	function testLoadClassNotFound() {
		$this->assertFalse($this->autoloader->loadClass('foobar'));
	}
	
	/**
	 * @depends testLoadClassNotFound
	 * @expectedException webbasics\FileNotFoundError
	 * @expectedExceptionMessage File "tests/_files/foobar.php" does not exist.
	 */
	function testLoadClassNotFoundError() {
		$this->autoloader->setThrowErrors(true);
		$this->autoloader->loadClass('foobar');
	}
	
	/**
	 * @depends testLoadClassNotFound
	 * @expectedException webbasics\FileNotFoundError
	 * @expectedExceptionMessage File "tests/_files/foobar.php" does not exist.
	 */
	function testLoadClassNotFoundNoerrorOverwrite() {
		$this->autoloader->loadClass('foobar', true);
	}
	
	/**
	 * @depends testLoadClassNotFound
	 */
	function testLoadClassNotFoundErrorOverwrite() {
		$this->autoloader->setThrowErrors(true);
		$this->assertFalse($this->autoloader->loadClass('foobar', false));
	}
	
	/**
	 * @depends testLoadClassNotFound
	 */
	function testLoadClass() {
		$this->assertTrue($this->autoloader->loadClass('Foo'));
		$this->assertTrue(class_exists('Foo', false));
		$this->assertTrue($this->autoloader->loadClass('Foo\Bar'));
		$this->assertTrue(class_exists('Foo\Bar', false));
	}
	
	/**
	 * @depends testLoadClass
	 * @depends testStripRootNamespace
	 */
	function testLoadClassRootNamespace() {
		$autoloader = new Autoloader(PATH.'foo');
		$autoloader->setRootNamespace('Foo');
		$this->assertTrue($autoloader->loadClass('Bar'));
		$this->assertTrue(class_exists('Foo\Bar', false));
	}
	
	/**
	 * @depends testLoadClass
	 */
	function testRegister() {
		$this->autoloader->register();
		$this->assertTrue(class_exists('Baz'));
	}
	
	/**
	 * @depends testRegister
	 * @depends testThrowErrors
	 */
	function testRegisterPrepend() {
		$second_loader = new Autoloader(PATH.'second');
		$this->autoloader->register();
		$second_loader->register(true);  // Prepend so that the second loader attemps to load Bar first
		$this->assertInstanceOf('Foo', new FooBaz());
	}
}

?>