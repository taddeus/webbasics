<?php
/**
 * Commonly used classes used in the BasicWeb package.
 * 
 * @author Taddeus Kroes
 * @version 1.0
 * @date 13-07-2012
 */

namespace BasicWeb;

require_once 'logger.php';

/**
 * Base class for instantiable classes in the BasicWeb package.
 * 
 * The base class defines a static 'create' method that acts as a chainable
 * shortcut for the class constructor.
 * 
 * @package BasicWeb
 */
abstract class Base {
	/**
	 * Create a new object of the called class.
	 * 
	 * This function provides a chainable constructor, which is not possible
	 * using plain PHP code.
	 * 
	 * @returns mixed
	 */
	final static function create(/* [ arg0 [ , ... ] ] */) {
		$args = func_get_args();
		$class = get_called_class();
		$rc = new \ReflectionClass($class);
		
		return $rc->newInstanceArgs($args);
	}
}

/**
 * Exception, thrown when a required file does not exist.
 * 
 * @package BasicWeb
 */
class FileNotFoundError extends \RuntimeException {
	/**
	 * Create a new FileNotFoundError instance.
	 * 
	 * Sets an error message of the form 'File "path/to/file.php" does not exist.'.
	 * 
	 * @param string $path Path to the file that does not exist.
	 */
	function __construct($path) {
		$this->message = sprintf('File "%s" does not exist.', $path);
	}
}

/**
 * Format a string of the form 'foo %(bar)' with given parameters like array('bar' => 'some value').
 * 
 * @param string $format The string to format.
 * @param array $params An associative array with parameters that are used in the format.
 */
function asprintf($format, array $params) {
	return preg_replace_callback(
		'/%\(([a-z-_ ]*)\)/i',
		function ($matches) use ($params) {
			return (string)$params[$matches[1]];
		},
		$format
	);
}

?>