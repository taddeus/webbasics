<?php
/**
 * Commonly used classes used in the WebBasics package.
 * 
 * @author Taddeus Kroes
 * @date 13-07-2012
 */

namespace webbasics;

require_once 'utils.php';
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
	
	/**
	 * Append a slash ('/') to the given directory name, if it is not already there.
	 * 
	 * @param string $directory The directory to append a slash to.
	 * @return string
	 */
	static function pathWithSlash($directory) {
		return $directory[strlen($directory) - 1] == '/' ? $directory : $directory . '/';
	}
}

/**
 * Exception with sprintf()-like constructor for error message formatting.
 * 
 * @package WebBasics
 * @link http://php.net/sprintf
 */
class FormattedException extends \Exception {
	/**
	 * Constructor, sets a formatted error message.
     * @link http://php.net/sprintf
	 */
	function __construct() {
		$args = func_get_args();
		$this->message = call_user_func_array('sprintf', $args);
	}
}

/**
 * Exception, thrown when a required file does not exist.
 * 
 * @package WebBasics
 */
class FileNotFoundError extends \Exception {
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
 * The Singleton interface mshould be implemented by classes that allow only
 * one instance.
 * 
 * The instance must be saved statically after the constructor has been
 * called. When getInstance() is called another time, this instance is
 * returned.
 */
interface Singleton {
	/**
	 * Create a new singleton instance, and save it in the static $instance variable.
	 * 
	 * @return object An existing instance from the $instance variable, or a new instance.
	 */
	public static function getInstance();
}

?>