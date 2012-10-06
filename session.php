<?php
/*
 * 
 * 
 * @author Taddeus Kroes
 * @date 05-10-2012
 */

namespace webbasics;

require_once 'base.php';

class Session implements Singleton {
	private static $instance;
	
	static function getInstance() {
		if (self::$instance === null)
			self::$instance = new self;
		
		return self::$instance;
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	private function __construct() {
		session_start();
	}
	
	function set($names, $value=null) {
		if (is_array($names)) {
			foreach ($names as $name => $value)
				$this->set($name, $value);
		} else {
			$_SESSION[$names] = $value;
		}
	}
	
	function get($names) {
		if (is_array($names)) {
			$values = array();
			
			foreach ($names as $name)
				$values[] = $_SESSION[$name];
			
			return $values;
		}
		
		return $_SESSION[$names];
	}
	
	function isRegistered($name) {
		return isset($_SESSION[$name]);
	}
	
	function areRegistered(array $names) {
		foreach ($names as $name) {
			if (!isset($_SESSION[$name]))
				return false;
		}
		
		return true;
	}
	
	function regenerateId() {
		session_regenerate_id();
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	function close() {
		session_write_close();
	}
	
	function clear() {
		$_SESSION = array();
	}
	
	function destroy($clear=false) {
		if ($clear)
			$this->clear();
		
		session_destroy();
		self::$instance = null;
	}
}

?>