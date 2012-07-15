<?php

require_once 'logger.php';
use WebBasics\Logger;

define('NAME', 'Testlogger');
define('FORMAT', '%(level): %(message)');

class LoggerTest extends PHPUnit_Extensions_OutputTestCase {
	function setUp() {
		$this->logger = new Logger();
		$this->logger->set_property('name', NAME);
		$this->logger->set_format(FORMAT);
	}
	
	function assert_dumps($expected) {
		$this->assertEquals($this->logger->dumps(), $expected);
	}
	
	function test_get_format() {
		$this->assertEquals($this->logger->get_format(), FORMAT);
	}
	
	function test_get_level() {
		$this->assertEquals($this->logger->get_level(), Logger::WARNING);
		$this->assertEquals($this->logger->get_level_name(), 'WARNING');
	}
	
	/**
	 * @depends test_get_level
	 */
	function test_set_level() {
		$this->logger->set_level('info');
		$this->assertEquals($this->logger->get_level(), Logger::INFO);
		$this->logger->set_level('DEBUG');
		$this->assertEquals($this->logger->get_level(), Logger::DEBUG);
		$this->logger->set_level('WaRnInG');
		$this->assertEquals($this->logger->get_level(), Logger::WARNING);
		$this->logger->set_level(Logger::ERROR);
		$this->assertEquals($this->logger->get_level(), Logger::ERROR);
	}
	
	function test_format() {
		$this->logger->error('test message');
		$this->assert_dumps('ERROR: test message');
	}
	
	function test_set_property() {
		$this->logger->set_property('name', 'Logger');
		$this->assertEquals($this->logger->get_formatted_property('name'), 'Logger');
	}
	
	/**
	 * @depends test_format
	 */
	function test_clear() {
		$this->logger->warning('test message');
		$this->logger->clear();
		$this->assert_dumps('');
	}
	
	/**
	 * @depends test_set_level
	 * @depends test_clear
	 */
	function test_process_level() {
		$this->logger->info('test message');
		$this->assert_dumps('');
		$this->logger->warning('test message');
		$this->assert_dumps('WARNING: test message');
		$this->logger->critical('test message');
		$this->assert_dumps("WARNING: test message\nCRITICAL: test message");
		$this->logger->clear();
		$this->logger->set_level('debug');
		$this->logger->debug('test message');
		$this->assert_dumps('DEBUG: test message');
	}
	
	function test_get_formatted_property() {
		$this->assertEquals($this->logger->get_formatted_property('name'), NAME);
		$this->assertEquals($this->logger->get_formatted_property('loglevel'), 'WARNING');
		$this->assertRegExp('/^\d{2}-\d{2}-\d{4}$/',
			$this->logger->get_formatted_property('date'));
		$this->assertRegExp('/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}$/',
			$this->logger->get_formatted_property('datetime'));
		$this->assertRegExp('/^\d{2}:\d{2}:\d{2}$/',
			$this->logger->get_formatted_property('time'));
		$this->setExpectedException('\InvalidArgumentException');
		$this->logger->get_formatted_property('foo');
	}
	
	function test_dumps_property_format() {
		$this->logger->warning('test message');
		$this->logger->set_format('%(name): %(level): %(message)');
		$this->assert_dumps(NAME.': WARNING: test message');
	}
	
	/**
	 * @depends test_process_level
	 */
	function test_dump() {
		$this->logger->warning('test message');
		$this->expectOutputString('WARNING: test message');
		$this->logger->dump();
	}
	
	function test_handle_exception() {
		$this->logger->handle_exception(new Exception('test message'));
		$this->assertNotEquals($this->logger->dumps(), '');
	}
}

?>