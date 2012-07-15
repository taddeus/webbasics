<?php
/**
 * Commonly used classes used in the WebBasics package.
 * 
 * @author Taddeus Kroes
 * @version 1.0
 * @date 13-07-2012
 */

namespace WebBasics;

require_once 'logger.php';

/**
 * Base class for instantiable classes in the WebBasics package.
 * 
 * The base class defines a static 'create' method that acts as a chainable
 * shortcut for the class constructor:
 * <code>
 * class Foo extends Base {
 *     function __contruct($bar, $baz) {
 *         $this->bar = bar;
 *         $this->baz = baz;
 *     }
 * }
 * 
 * $foo = Foo::create('bar', 'baz');
 * // is equivalent to:
 * $foo = new Foo('bar', 'baz');
 * </code>
 * 
 * The advantage of the 'create' constructor is that is allows chaining:
 * <code>
 * Foo::create('bar', 'baz')->method();
 * // as opposed to:
 * $foo = new Foo('bar', 'baz');
 * $foo->method();
 * </code>
 * 
 * @package WebBasics
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
 * @package WebBasics
 */
class FileNotFoundError extends \RuntimeException {
	/**
	 * Create a new FileNotFoundError instance.
	 * 
	 * Sets an error message of the form 'File "path/to/file.php" does not exist.'.
	 * 
	 * @param string $path Path to the file that does not exist.
	 * @param bool $is_dir Whether the path points to a directory (defaults to false).
	 */
	function __construct($path, $is_dir=false) {
		$this->message = sprintf('%s "%s" does not exist.', $is_dir ? 'Directory' : 'File', $path);
	}
}

/**
 * Format a string using parameters in an associative array.
 * 
 * <code>
 * echo asprintf('foo %(bar)', array('bar' => 'baz'));  // prints 'foo baz'
 * </code>
 * 
 * @param string $format The string to format.
 * @param array $params An associative array with parameters that are used in $format.
 * @package WebBasics
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