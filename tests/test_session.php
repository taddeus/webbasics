<?php

require_once 'SingletonTestCase.php';
require_once 'session.php';
use webbasics\Session;

// Turn on outbut buffering to circumvent "headers already sent" error
ob_start();

class SessionTest extends SingletonTestCase {
	private $session;
	
	function getClassName() {
		return 'webbasics\Session';
	}
	
	function setUp() {
		parent::setUp();
		$this->session = @Session::getInstance();
	}
	
	function tearDown() {
		$_SESSION = array();
	}
	
	function testSessionStarted() {
		$this->assertNotEquals('', session_id());
	}
	
	function testSetSingle() {
		$this->session->set('foo', 'bar');
		$this->assertArrayHasKey('foo', $_SESSION);
		$this->assertEquals('bar', $_SESSION['foo']);
	}
	
	function testSetMultiple() {
		$this->session->set(array('foo' => 'bar', 'bar' => 'baz'));
		$this->assertArrayHasKey('foo', $_SESSION);
		$this->assertEquals('bar', $_SESSION['foo']);
		$this->assertArrayHasKey('bar', $_SESSION);
		$this->assertEquals('baz', $_SESSION['bar']);
	}
	
	function testGetSingle() {
		$_SESSION['foo'] = 'bar';
		$this->assertEquals('bar', $this->session->get('foo'));
	}
	
	function testGetMultiple() {
		$_SESSION['foo'] = 'bar';
		$_SESSION['bar'] = 'baz';
		$this->assertEquals(array('bar', 'baz'), $this->session->get(array('foo', 'bar')));
	}
	
	function testIsRegistered() {
		$_SESSION['foo'] = 'bar';
		$this->assertTrue($this->session->isRegistered('foo'));
		$this->assertFalse($this->session->isRegistered('bar'));
	}
	
	/**
	 * @depends testIsRegistered
	 */
	function testAreRegistered() {
		$_SESSION['foo'] = 'bar';
		$_SESSION['bar'] = 'baz';
		$this->assertTrue($this->session->areRegistered(array('foo', 'bar')));
		$this->assertFalse($this->session->areRegistered(array('foo', 'baz')));
	}
	
	function testRegenerateId() {
		$old_id = session_id();
		$this->session->regenerateId();
		$this->assertNotEquals($old_id, session_id());
	}
	
	function testClear() {
		$_SESSION['foo'] = 'bar';
		$this->session->clear();
		$this->assertEmpty($_SESSION);
	}
	
	/**
	 * @depends testClear
	 */
	function testDestroyClear() {
		$_SESSION['foo'] = 'bar';
		$this->session->destroy();
		$this->assertEmpty($_SESSION);
		$this->assertEquals('', session_id());
	}
}

?>