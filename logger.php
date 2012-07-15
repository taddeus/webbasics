<?php
/**
 * Logging functions.
 * 
 * @author Taddeus Kroes
 * @version 1.0
 * @date 13-07-2012
 */

namespace WebBasics;

/**
 * Logger class. 
 * 
 * A Logger object provides five functions to process log messages.
 * 
 * @package WebBasics
 */
class Logger {
	const CRITICAL = 0;
	const ERROR = 1;
	const WARNING = 2;
	const INFO = 3;
	const DEBUG = 4;
	static $level_names = array('CRITICAL', 'ERROR', 'WARNING', 'INFO', 'DEBUG');
	
	const DEFAULT_FORMAT = '%(datetime): %(level): %(message)';
	
	private $properties = array();
	private $output = array();
	private $format = self::DEFAULT_FORMAT;
	private $level = self::WARNING;
	
	function set_format($format) {
		$this->format = (string)$format;
	}
	
	function get_format() {
		return $this->format;
	}
	
	function get_level() {
		return $this->level;
	}
	
	function get_level_name() {
		return self::$level_names[$this->level];
	}
	
	function set_level($level) {
		if( is_string($level) ) {
			$level = strtoupper($level);
			
			if( !defined('self::'.$level) )
				throw new \InvalidArgumentException(sprintf('Invalid debug level %s.', $level));
			
			$level = constant('self::'.$level);
		}
		
		if( $level < self::CRITICAL || $level > self::DEBUG )
			throw new \InvalidArgumentException(sprintf('Invalid debug level %d.', $level));
		
		$this->level = $level;
	}
	
	function set_property($name, $value) {
		$this->properties[$name] = (string)$value;
	}
	
	function critical($message) {
		$this->process($message, self::CRITICAL);
	}
	
	function error($message) {
		$this->process($message, self::ERROR);
	}
	
	function warning($message) {
		$this->process($message, self::WARNING);
	}
	
	function info($message) {
		$this->process($message, self::INFO);
	}
	
	function debug($message) {
		$this->process($message, self::DEBUG);
	}
	
	private function process($message, $level) {
		if( $level <= $this->level )
			$this->output[] = array($message, $level);
	}
	
	function dumps() {
		$logger = $this;
		$output = '';
		
		foreach( $this->output as $i => $tuple ) {
			list($message, $level) = $tuple;
			$i && $output .= "\n";
			$output .= preg_replace_callback(
				'/%\(([a-z-_ ]*)\)/i',
				function ($matches) use ($logger, $message, $level) {
					$name = $matches[1];
					
					if( $name == 'message' )
						return $message;
					
					if( $name == 'level' )
						return Logger::$level_names[$level];
					
					return $logger->get_formatted_property($matches[1]);
				},
				$this->format
			);
		}
		
		return $output;
	}
	
	function dump() {
		echo $this->dumps();
	}
	
	function clear() {
		$this->output = array();
	}
	
	function save($path) {
		file_put_contents($path, $this->dumps());
	}
	
	function handle_exception(\Exception $e) {
		if( $e === null )
			return;
		
		$message = sprintf("Uncaught %s in file %s, line %d: %s\n\n%s", get_class($e),
			$e->getFile(), $e->getLine(), $e->getMessage(), $e->getTraceAsString());
		$this->critical($message);
		$this->dump();
	}
	
	function set_as_exception_handler() {
		set_exception_handler(array($this, 'handle_exception'));
	}
	
	function get_formatted_property($property) {
		if( isset($this->properties[$property]) )
			return $this->properties[$property];
		
		switch( $property ) {
			case 'loglevel':
				return $this->get_level_name();
			case 'date':
				return strftime('%d-%m-%Y');
			case 'time':
				return strftime('%H:%M:%S');
			case 'datetime':
				return strftime('%d-%m-%Y %H:%M:%S');
		}
		
		throw new \InvalidArgumentException(sprintf('Invalid logging property "%s".', $property));
	}
}

?>