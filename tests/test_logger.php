<?php

require_once 'logger.php';
use WebBasics\Logger;

define('NAME', 'Testlogger');
define('FORMAT', '%(level): %(message)');
define('LOGDIR', 'build/logs/');
define('LOGFILE', 'build/temp.log');

class LoggerTest extends PHPUnit_Extensions_OutputTestCase {
	function setUp() {
		$this->logger = new Logger();
		$this->logger->set_property('name', NAME);
		$this->logger->set_format(FORMAT);

		is_dir('build') || mkdir('build');
	}

	function assert_dumps($expected) {
		$this->assertEquals($this->logger->dumps(), $expected);
	}

	function test_set_directory() {
		$this->logger->set_directory('logs');
		$this->assertAttributeEquals('logs/', 'log_directory', $this->logger);
		$this->logger->set_directory('logs/');
		$this->assertAttributeEquals('logs/', 'log_directory', $this->logger);
	}

	function test_set_format() {
		$this->logger->set_format('foo');
		$this->assertAttributeEquals('foo', 'format', $this->logger);
	}

	function test_set_dump_format_success() {
		$this->logger->set_dump_format('html');
		$this->assertAttributeEquals('html', 'dump_format', $this->logger);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	function test_set_dump_format_failure() {
		$this->logger->set_dump_format('foo');
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
	function test_dump_plain() {
		$this->logger->warning('test message');
		$this->expectOutputString('WARNING: test message');
		$this->logger->dump();
	}

	/**
	 * @depends test_process_level
	 */
	function test_dump_html() {
		$this->logger->warning('test message');
		$this->logger->set_dump_format('html');
		$this->expectOutputString('<strong>Log:</strong><br /><pre>WARNING: test message</pre>');
		$this->logger->dump();
	}

	function test_save() {
		$this->logger->warning('test message');
		$this->logger->save(LOGFILE);
		$this->assertStringEqualsFile(LOGFILE, 'WARNING: test message');
		$this->logger->warning('another test message');
		$this->logger->save(LOGFILE);
		$this->assertStringEqualsFile(LOGFILE, "WARNING: test message\nWARNING: another test message");
		unlink(LOGFILE);
	}

	function find_logfile() {
		$files = scandir(LOGDIR);
		$this->assertEquals(3, count($files));
		return $files[2];
	}

	/**
	 * @depends test_save
	 */
	function test_dump_file_regular() {
		$this->logger->set_directory(LOGDIR);
		$this->logger->set_dump_format('file');

		$this->logger->warning('test message');
		$this->logger->dump();
		$filename = $this->find_logfile();
		$this->assertStringEqualsFile(LOGDIR . $filename, 'WARNING: test message');
		unlink(LOGDIR . $filename);
		$this->assertRegExp('/^log_\d{2}-\d{2}-\d{4}_\d{2}-\d{2}-\d{2}.log$/', $filename);
	}

	function test_handle_exception() {
		$this->logger->set_dump_format('none');
		$this->logger->handle_exception(new RuntimeException('test message'));
		$this->assertNotEquals($this->logger->dumps(), '');
	}
}

?>
