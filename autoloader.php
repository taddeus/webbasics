<?php
/**
 * 
 * 
 * @author Taddeus Kroes
 * @version 1.0
 * @date 13-07-2012
 */

namespace Minimalistic;

require_once 'base.php';

/**
 * Object that to automatically load classes within a root directory.
 * Simple example: all classes are located in the 'classes' directory.
 * <code>
 * $loader = new Autoloader('classes');
 * $loader->load_class('FooBar');  // Includes file 'classes/foo_bar.php'
 * </code>
 * 
 * An Autoloader instance can register itself to the SPL autoload stack, so
 * that explicit 'include' statements for classes are not necessary anymore.
 * Applied to the example above:
 * <code>
 * $loader = new Autoloader('classes');
 * $loader->register();
 * $foobar = new FooBar();  // File 'classes/foo_bar.php' is automatically included
 * </code>
 * 
 * Namespaces are assumed to indicate subdirectories:
 * <code=php>
 * Autoloader::create('classes')->register();
 * $bar = new Foo\Bar();      // Includes 'classes/foo/bar.php'
 * $baz = new Foo\Bar\Baz();  // Includes 'classes/foo/bar/baz.php'
 * </code>
 * 
 * Multiple autoloaders can be registered at the same time. Be sure to disable
 * exceptions on the previously added loaders!
 * <code>
 * <code>
 * File structure:
 * classes/
 *   | foo.php  // Contains class 'Foo'
 * other_classes/
 *   | bar.php  // Contains class 'Bar'
 * </code>
 * Autoloader::create('classes', false)->register();
 * Autoloader::create('other_classes')->register();
 * $foo = new Foo();  // Includes 'classes/foo.php'
 * $bar = new Bar();  // Includes 'other_classes/bar.php', since 'classes/bar.php' does not exist
 * $baz = new Baz();  // Throws an exception, since 'other_classes/baz.php' does not exist
 * </code>
 * 
 * @package Minimalistic
 */
class Autoloader extends Base {
	/**
	 * The root directory to look in.
	 * 
	 * @var string
	 */
	private $root_directory;
	
	/**
	 * The namespace classes in the root directory are expected to be in.
	 * 
	 * This namespace is removed from loaded class names.
	 * 
	 * @var string
	 * @todo implement this
	 */
	private $root_namespace = '';
	
	/**
	 * Whether to throw an exception when a class file does not exist.
	 * 
	 * @var bool
	 */
	private $throw_errors;
	
	/**
	 * Create a new Autoloader instance.
	 * 
	 * @param string $directory Root directory of the autoloader.
	 * @param bool $throw Whether to throw an exception when a class file does not exist.
	 */
	function __construct($directory, $throw=true) {
		$this->set_root_directory($directory);
		$this->set_throw_errors($throw);
	}
	
	/**
	 * Set whether to throw an exception when a class file does not exist.
	 * 
	 * @param bool $throw Whether to throw exceptions.
	 */
	function set_throw_errors($throw) {
		$this->throw_errors = !!$throw;
	}
	
	/**
	 * Whether an exception is thrown when a class file does not exist.
	 * 
	 * @return bool
	 */
	function get_throw_errors() {
		return $this->throw_errors;
	}
	
	/**
	 * Set the root directory from which classes are loaded.
	 * 
	 * @param string $directory The new root directory.
	 */
	function set_root_directory($directory) {
		$this->root_directory = self::path_with_slash($directory);
	}
	
	/**
	 * Get the root directory from which classes are loaded.
	 * 
	 * @return string
	 */
	function get_root_directory() {
		return $this->root_directory;
	}
	
	/**
	 * Append a slash ('/') to the given directory name, if it is not already there.
	 * 
	 * @param string $directory The directory to append a slash to.
	 * @return string
	 */
	static function path_with_slash($directory) {
		return $directory[strlen($directory) - 1] == '/' ? $directory : $directory.'/';
	}
	
	/**
	 * Convert a class name to a file name.
	 * 
	 * Uppercase letters are converted to lowercase and prepended
	 * by an underscore ('_').
	 * 
	 * @param string $classname The class name to convert.
	 * @return string
	 */
	static function classname_to_filename($classname) {
		return strtolower(preg_replace('/(?<=.)([A-Z])/', '_\\1', $classname));
	}
	
	/**
	 * Create the path to a class file.
	 * 
	 * Any namespace prepended to the class name is split on '\', the
	 * namespace levels are used to indicate directory names.
	 * 
	 * @param string $classname The name of the class to create the file path of.
	 */
	function create_path($classname) {
		$namespaces = array_filter(explode('\\', $classname));
		$dirs = array_map('self::classname_to_filename', $namespaces);
		$path = $this->root_directory;
		
		if( count($dirs) > 1 )
			$path .= implode('/', array_slice($dirs, 0, count($dirs) - 1)).'/';
		
		$path .= end($dirs).'.php';
		return strtolower($path);
	}
	
	/**
	 * Load a class.
	 * 
	 * Any namespace prepended to the class name is split on '\', the
	 * namespace levels are used to indicate directory names.
	 * 
	 * @param string $classname The name of the class to load, including pepended namespace.
	 * @param bool $throw Whether to throw an exception if the class file does not exist.
	 * @return bool
	 * @throws FileNotFoundError If the class file does not exist.
	 */
	function load_class($classname, $throw=true) {
		$path = $this->create_path($classname);
		
		if( !file_exists($path) ) {
			if( !$throw || !$this->throw_errors )
				return false;
			
			throw new FileNotFoundError($path);
		}
		
		require_once $path;
		return true;
	}
	
	/**
	 * Register the autoloader object to the SPL autoload stack.
	 * 
	 * @param bool $prepend Whether to prepend the autoloader function to
	 *                      the stack, instead of appending it.
	 */
	function register($prepend=false) {
		spl_autoload_register(array($this, 'load_class'), true, $prepend);
	}
}

?>