<?php
/**
 * A tool for autoloading PHP classes within a root directory and namespace.
 * 
 * @author Taddeus Kroes
 * @date 13-07-2012
 */

namespace webbasics;

require_once 'base.php';

/**
 * Object to automatically load classes within a root directory.
 * 
 * Simple example: all classes are located in the 'classes' directory.
 * <code>
 * $loader = webbasics\Autoloader::getInstance();
 * $loader->addDirectory('classes');  // Add "classes" directory to global class path
 * $loader->loadClass('FooBar');      // Includes file "classes/FooBar.php"
 * </code>
 * 
 * The Autoloader instance registers itself to the SPL autoload stack, so
 * that explicit 'include' statements for classes are not necessary anymore.
 * Therefore, the call to loadClass() in the example above is not necessary:
 * <code>
 * webbasics\Autoloader::getInstance()->addDirectory('classes');
 * $foobar = new FooBar();  // File "classes/FooBar.php" is automatically included
 * </code>
 * 
 * Namespaces are assumed to indicate subdirectories:
 * <code>
 * webbasics\Autoloader::getInstance()->addDirectory('classes');
 * $bar = new foo\Bar();      // Includes "classes/foo/Bar.php"
 * $baz = new foo\bar\Baz();  // Includes "classes/foo/bar/Baz.php"
 * </code>
 * 
 * To load classes within some namespace efficiently, directories can be
 * assigned to a root namespace:
 * <code>
 * $loader = webbasics\Autoloader::getInstance();
 * $loader->addDirectory('models', 'models');
 * 
 * // File "models/Foo.php"
 * namespace models;
 * class Foo extends ActiveRecord\Model { ... }
 * </code>
 * 
 * Exception throwing can be enabled in case a class does not exist:
 * <code>
 * $loader = webbasics\Autoloader::getInstance();
 * $loader->addDirectory('classes');
 * $loader->setThrowExceptions(true);
 * 
 * try {
 *     new Foo();
 * } catch (webbasics\AutoloadError $e) {
 *     // "classes/Foo.php" does not exist
 * }
 * </code>
 * 
 * @package WebBasics
 */
class Autoloader extends Base implements Singleton {
	/**
	 * Namespaces mapping to lists of directories.
	 * @var array
	 */
	private $directories = array('\\' => array());
	
	/**
	 * Whether to throw an exception if a class file does not exist.
	 * @var bool
	 */
	private $throw_exceptions = false;
	
	/**
	 * @see Singleton::$instance
	 */
	private static $instance;
	
	/**
	 * @see Singleton::getInstance()
	 */
	static function getInstance() {
		if (self::$instance === null)
			self::$instance = new self;
		
		return self::$instance;
	}
	
	/**
	 * Create a new Autoloader instance.
	 * 
	 * Registers the {@link loadClass()} function to the SPL autoload stack.
	 */
	private function __construct() {
		spl_autoload_register(array($this, 'loadClass'), true);
	}
	
	/**
	 * Set whether to throw an exception when a class file does not exist.
	 * 
	 * @param bool $throw Whether to throw exceptions.
	 */
	function setThrowExceptions($throw) {
		$this->throw_exceptions = (bool)$throw;
	}
	
	/**
	 * Whether an exception is thrown if a class file does not exist.
	 * 
	 * @return bool
	 */
	function getThrowExceptions() {
		return $this->throw_exceptions;
	}
	
	/**
	 * Add a new directory to look in while looking for  a class within the given namespace.
	 */
	function addDirectory($directory, $namespace='\\') {
		$directory = self::pathWithSlash($directory);
		
		if ($namespace[0] != '\\')
			$namespace = '\\' . $namespace;
		
		if (!isset($this->directories[$namespace]))
			$this->directories[$namespace] = array();
		
		if (!in_array($directory, $this->directories[$namespace]))
			$this->directories[$namespace][] = $directory;
	}
	
	/**
	 * Strip a namespace from the beginning of a class name.
	 * 
	 * @param string $namespace The namespace to strip.
	 * @param string $classname The name of the class to strip the namespace from.
	 * @return string The stripped class name.
	 */
	private static function stripNamespace($namespace, $classname) {
		if ($namespace != '\\')
			$namespace .= '\\';
		
		$begin = substr($classname, 0, strlen($namespace));
		
		if ($begin == $namespace)
			$classname = substr($classname, strlen($namespace));
		
		return $classname;
	}
	
	/**
	 * Create the path to a class file.
	 * 
	 * Any namespace prepended to the class name is split on '\', the
	 * namespace levels are used to indicate directory names.
	 * 
	 * @param string $classname The name of the class to create the file path of.
	 */
	private static function createPath($classname) {
		$parts = array_filter(explode('\\', $classname));
		$path = '';
		
		if (count($parts) > 1)
			$path .= implode('/', array_slice($parts, 0, count($parts) - 1)) . '/';
		
		return $path . end($parts) . '.php';
	}
	
	/**
	 * Load a class.
	 * 
	 * Any namespace prepended to the class name is split on '\', the
	 * namespace levels are used to indicate directory names.
	 * 
	 * @param string $classname The name of the class to load, including pepended namespace.
	 * @return bool Whether the class file could be found.
	 * @throws ClassNotFoundError If the class file does not exist.
	 * @todo Unit test reverse-order namespace traversal
	 */
	function loadClass($classname) {
		// Prepend at least the root namespace
		if ($classname[0] != '\\')
			$classname = '\\' . $classname;
		
		// Find namespace directory
		$parts = array_filter(explode('\\', $classname));
		
		// Try larger namespaces first, getting smaller and smaller up to the global namespace
		for ($i = count($parts); $i >= 0; $i--) {
			$namespace = '\\' . implode('\\', array_slice($parts, 0, $i));
			
			// If the namespace is mapped to a list of directories, attempt to
			// load the class file from there
			if (isset($this->directories[$namespace])) {
				foreach ($this->directories[$namespace] as $directory) {
					$class = self::stripNamespace($namespace, $classname);
					$path = $directory . self::createPath($class);
					
					if (file_exists($path)) {
						require_once $path;
						return true;
					}
				}
			}
		}
		
		if ($this->throw_exceptions)
			throw new ClassNotFoundError($classname);
		
		return false;
	}
}

/** 
 * Exception, thrown when a class file could not be found.
 */
class ClassNotFoundError extends FormattedException {
	function __construct($classname) {
		parent::__construct('could not load class "%s"', $classname);
	}
}

?>