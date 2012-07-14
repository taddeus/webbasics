<?php

require_once 'autoloader.php';
use BasicWeb\Autoloader;

define('PATH', 'tests/_files/');

class AutoloaderTest extends PHPUnit_Framework_TestCase {
	function setUp() {
		$this->autoloader = new Autoloader(PATH);
	}
	
	function test_path_with_slash() {
		$this->assertEquals(Autoloader::path_with_slash('dirname'), 'dirname/');
		$this->assertEquals(Autoloader::path_with_slash('dirname/'), 'dirname/');
	}
	
	/**
	 * @depends test_path_with_slash
	 */
	function test_set_root_directory() {
		$this->autoloader->set_root_directory('tests');
		$this->assertEquals($this->autoloader->get_root_directory(), 'tests/');
	}
	
	function test_classname_to_filename() {
		$this->assertEquals(Autoloader::classname_to_filename('Foo'), 'foo');
		$this->assertEquals(Autoloader::classname_to_filename('FooBar'), 'foo_bar');
		$this->assertEquals(Autoloader::classname_to_filename('fooBar'), 'foo_bar');
		$this->assertEquals(Autoloader::classname_to_filename('FooBarBaz'), 'foo_bar_baz');
	}
	
	/**
	 * @depends test_classname_to_filename
	 */
	function test_create_path() {
		$this->assertEquals($this->autoloader->create_path('Foo'), PATH.'foo.php');
		$this->assertEquals($this->autoloader->create_path('\Foo'), PATH.'foo.php');
		$this->assertEquals($this->autoloader->create_path('Foo\Bar'), PATH.'foo/bar.php');
		$this->assertEquals($this->autoloader->create_path('Foo\Bar\Baz'), PATH.'foo/bar/baz.php');
		$this->assertEquals($this->autoloader->create_path('FooBar\Baz'), PATH.'foo_bar/baz.php');
	}
	
	/**
	 * @depends test_create_path
	 * @expectedException BasicWeb\FileNotFoundError
	 * @expectedExceptionMessage File "tests/_files/foobar.php" does not exist.
	 */
	function test_load_class_not_found() {
		$this->autoloader->load_class('foobar');
	}
	
	/**
	 * @depends test_load_class_not_found
	 */
	function test_load_class() {
		$this->assertTrue($this->autoloader->load_class('Foo'));
		$this->assertTrue(class_exists('Foo', false));
		$this->assertTrue($this->autoloader->load_class('Foo\Bar'));
		$this->assertTrue(class_exists('Foo\Bar', false));
	}
	
	/**
	 * @depends test_load_class
	 */
	function test_register() {
		$this->autoloader->register();
		$this->assertTrue(class_exists('Baz'));
	}
	
	function test_throw_errors() {
		$this->assertTrue($this->autoloader->get_throw_errors());
		$this->autoloader->set_throw_errors(false);
		$this->assertFalse($this->autoloader->get_throw_errors());
	}
	
	/**
	 * @depends test_register
	 * @depends test_throw_errors
	 */
	function test_register_prepend() {
		$second_loader = new Autoloader(PATH.'second');
		$this->autoloader->register();
		$second_loader->register(true);  // Prepend so that the second loader attemps to load Bar first
		$second_loader->set_throw_errors(false);
		$this->assertInstanceOf('Foo', new FooBaz());
	}
}

?>