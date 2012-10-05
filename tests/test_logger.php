<?php

require_once 'logger.php';
use webbasics\Logger;

define('NAME', 'Testlogger');
define('FORMAT', '%(level): %(message)');
define('LOGDIR', 'build/logs/');
define('LOGFILE', 'build/temp.log');

class LoggerTest extends PHPUnit_Extensions_OutputTestCase {
	function setUp() {
		$this->logger = new Logger();
		$this->logger->setProperty('name', NAME);
		$this->logger->setFormat(FORMAT);

		is_dir('build') || mkdir('build');
	}

	function assertDumps($expected) {
		$this->assertEquals($this->logger->dumps(), $expected);
	}

	function testSetDirectory() {
		$this->logger->setDirectory('logs');
		$this->assertAttributeEquals('logs/', 'log_directory', $this->logger);
		$this->logger->setDirectory('logs/');
		$this->assertAttributeEquals('logs/', 'log_directory', $this->logger);
	}

	function testSetFormat() {
		$this->logger->setFormat('foo');
		$this->assertAttributeEquals('foo', 'format', $this->logger);
	}

	function testSetDumpFormatSuccess() {
		$this->logger->setDumpFormat('html');
		$this->assertAttributeEquals('html', 'dump_format', $this->logger);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	function testSetDumpFormatFailure() {
		$this->logger->setDumpFormat('foo');
	}

	function testGetFormat() {
		$this->assertEquals($this->logger->getFormat(), FORMAT);
	}

	function testGetLevel() {
		$this->assertEquals($this->logger->getLevel(), Logger::WARNING);
		$this->assertEquals($this->logger->getLevelName(), 'WARNING');
	}

	/**
	 * @depends testGetLevel
	 */
	function testSetLevel() {
		$this->logger->setLevel('info');
		$this->assertEquals($this->logger->getLevel(), Logger::INFO);
		$this->logger->setLevel('DEBUG');
		$this->assertEquals($this->logger->getLevel(), Logger::DEBUG);
		$this->logger->setLevel('WaRnInG');
		$this->assertEquals($this->logger->getLevel(), Logger::WARNING);
		$this->logger->setLevel(Logger::ERROR);
		$this->assertEquals($this->logger->getLevel(), Logger::ERROR);
	}

	function testFormat() {
		$this->logger->error('test message');
		$this->assertDumps('ERROR: test message');
	}

	function testSetProperty() {
		$this->logger->setProperty('name', 'Logger');
		$this->assertEquals($this->logger->getFormattedProperty('name'), 'Logger');
	}

	/**
	 * @depends testFormat
	 */
	function testClear() {
		$this->logger->warning('test message');
		$this->logger->clear();
		$this->assertDumps('');
	}

	/**
	 * @depends testSetLevel
	 * @depends testClear
	 */
	function testProcessLevel() {
		$this->logger->info('test message');
		$this->assertDumps('');
		$this->logger->warning('test message');
		$this->assertDumps('WARNING: test message');
		$this->logger->critical('test message');
		$this->assertDumps("WARNING: test message\nCRITICAL: test message");
		$this->logger->clear();
		$this->logger->setLevel('debug');
		$this->logger->debug('test message');
		$this->assertDumps('DEBUG: test message');
	}

	function testGetFormattedProperty() {
		$this->assertEquals($this->logger->getFormattedProperty('name'), NAME);
		$this->assertEquals($this->logger->getFormattedProperty('loglevel'), 'WARNING');
		$this->assertRegExp('/^\d{2}-\d{2}-\d{4}$/',
			$this->logger->getFormattedProperty('date'));
		$this->assertRegExp('/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}$/',
			$this->logger->getFormattedProperty('datetime'));
		$this->assertRegExp('/^\d{2}:\d{2}:\d{2}$/',
			$this->logger->getFormattedProperty('time'));
		$this->setExpectedException('\InvalidArgumentException');
		$this->logger->getFormattedProperty('foo');
	}

	function testDumpsPropertyFormat() {
		$this->logger->warning('test message');
		$this->logger->setFormat('%(name): %(level): %(message)');
		$this->assertDumps(NAME.': WARNING: test message');
	}

	/**
	 * @depends testProcessLevel
	 */
	function testDumpPlain() {
		$this->logger->warning('test message');
		$this->expectOutputString('WARNING: test message');
		$this->logger->dump();
	}

	/**
	 * @depends testProcessLevel
	 */
	function testDumpHtml() {
		$this->logger->warning('test message');
		$this->logger->setDumpFormat('html');
		$this->expectOutputString('<strong>Log:</strong><br /><pre>WARNING: test message</pre>');
		$this->logger->dump();
	}

	function testSave() {
		$this->logger->warning('test message');
		$this->logger->save(LOGFILE);
		$this->assertStringEqualsFile(LOGFILE, 'WARNING: test message');
		$this->logger->warning('another test message');
		$this->logger->save(LOGFILE);
		$this->assertStringEqualsFile(LOGFILE, "WARNING: test message\nWARNING: another test message");
		unlink(LOGFILE);
	}

	function findLogfile() {
		$files = scandir(LOGDIR);
		$this->assertEquals(3, count($files));
		return $files[2];
	}

	/**
	 * @depends testSave
	 */
	function testDumpFileRegular() {
		$this->logger->setDirectory(LOGDIR);
		$this->logger->setDumpFormat('file');

		$this->logger->warning('test message');
		$this->logger->dump();
		$filename = $this->findLogfile();
		$this->assertStringEqualsFile(LOGDIR . $filename, 'WARNING: test message');
		unlink(LOGDIR . $filename);
		$this->assertRegExp('/^log_\d{2}-\d{2}-\d{4}_\d{2}-\d{2}-\d{2}.log$/', $filename);
	}

	function testHandleException() {
		$this->logger->setDumpFormat('none');
		$this->logger->handleException(new RuntimeException('test message'));
		$this->assertNotEquals($this->logger->dumps(), '');
	}
}

?>
