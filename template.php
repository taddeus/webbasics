<?php
/**
 * HTML template rendering functions.
 * 
 * @author Taddeus Kroes
 * @version 1.0
 * @date 14-07-2012
 */

namespace WebBasics;

require_once 'node.php';

/**
 * A Template object represents a template file.
 * 
 * A template file contains 'blocks' that can be rendered zero or more times.
 * Each block has a set of properties that can be accessed using curly
 * brackets ('{' and '}'). Curly brackets may contain macro's to minimize
 * common view logic in controllers.
 * 
 * Example template 'page.tpl':
 * <code>
 * &lt;html&gt;
 *     &lt;head&gt;
 *         &lt;title&gt;{page_title}&lt;/title&gt;
 *     &lt;/head&gt;
 *     &lt;body&gt;
 *         &lt;h1&gt;{page_title}&lt;/h1&gt;
 *         &lt;div id="content"&gt;{page_content}&lt;/div&gt;
 *         &lt;div id="ads"&gt;
 *             {block:ad}
 *             &lt;div class="ad"&gt;{ad_content}&lt;/div&gt;
 *             {end}
 *         &lt;/div&gt;
 *     &lt;/body&gt;
 * &lt;/html&gt;
 * </code>
 * And the corresponding PHP code:
 * <code>
 * $tpl = new Template('page');
 * $tpl->set(array(
 *     'page_title' => 'Some title',
 *     'page_content' => 'Lorem ipsum ...'
 * ));
 * 
 * foreach( array('Some ad', 'Another ad', 'More ads') as $ad )
 *     $tpl->add('ad')->set('ad_content', $ad);
 * 
 * echo $tpl->render();
 * </code>
 * The output will be:
 * <code>
 * &lt;html&gt;
 *     &lt;head&gt;
 *         &lt;title&gt;Some title&lt;/title&gt;
 *     &lt;/head&gt;
 *     &lt;body&gt;
 *         &lt;h1&gt;Some title&lt;/h1&gt;
 *         &lt;div id="content"&gt;Some content&lt;/div&gt;
 *         &lt;div id="ads"&gt;
 *             &lt;div class="ad"&gt;Some ad&lt;/div&gt;
 *             &lt;div class="ad"&gt;Another ad&lt;/div&gt;
 *             &lt;div class="ad"&gt;More ads&lt;/div&gt;
 *         &lt;/div&gt;
 *     &lt;/body&gt;
 * &lt;/html&gt;
 * </code>
 * 
 * @package WebBasics
 */
class Template extends Node {
	/**
	 * Default extension of template files.
	 * 
	 * @var array
	 */
	const DEFAULT_EXTENSION = '.tpl';
	
	/**
	 * Root directories from which template files are included.
	 * 
	 * @var array
	 */
	private static $include_path = array();
	
	/**
	 * The path the template was found in.
	 * 
	 * @var string
	 */
	private $path;
	
	/**
	 * The content of the template file.
	 * 
	 * @var string
	 */
	private $file_content;
	
	/**
	 * The block structure of the template file.
	 * 
	 * @var Node
	 */
	private $root_block;
	
	/**
	 * Create a new Template object, representing a template file.
	 * 
	 * Template files are assumed to have the .tpl extension. If no extension
	 * is specified, '.tpl' is appended to the filename.
	 * 
	 * @param string $filename The path to the template file from one of the root directories.
	 */
	function __construct($filename) {
		// Add default extension if none is found
		strpos($filename, '.') === false && $filename .= self::DEFAULT_EXTENSION;
		
		$look_in = count(self::$include_path) ? self::$include_path : array('.');
		$found = false;
		
		foreach( $look_in as $root ) {
			$path = $root.$filename;
			
			if( file_exists($path) ) {
				$this->path = $path;
				$this->file_content = file_get_contents($path);
				$found = true;
				break;
			}
		}
		
		if( !$found ) {
			throw new \RuntimeException(
				sprintf("Could not find template file \"%s\", looked in folders:\n%s",
					$filename, implode("\n", $look_in))
			);
		}
		
		$this->parse_blocks();
	}
	
	/**
	 * Get the path to the template file (including one of the include paths).
	 * 
	 * @return string The path to the template file.
	 */
	function get_path() {
		return $this->path;
	}
	
	/**
	 * Parse the content of the template file into a tree structure of blocks
	 * and variables.
	 * 
	 * @throws ParseError If an {end} tag is not used properly.
	 */
	private function parse_blocks() {
		$current = $root = new Node('block');
		$after = $this->file_content;
		$line_count = 0;
		
		while( preg_match('/(.*?)\{([^}]+)}(.*)/s', $after, $matches) ) {
			list($before, $brackets_content, $after) = array_slice($matches, 1);
			$line_count += substr_count($before, "\n");
			
			// Everything before the new block belongs to its parent
			$current->add('html')->set('content', $before);
			
			if( $brackets_content == 'end' ) {
				// {end} encountered, go one level up in the tree
				if( $current->is_root() )
					throw new ParseError($this, 'unexpected {end}', $line_count + 1);
				
				$current = $current->get_parent();
			} elseif( substr($brackets_content, 0, 6) == 'block:' ) {
				// {block:...} encountered
				$block_name = substr($brackets_content, 6);
				// Go one level deeper into the tree
				$current = $current->add('block')->set('name', $block_name);
			} else {
				// Variable or something else
				$current->add('variable')->set('content', $brackets_content);
			}
		}
		
		$line_count += substr_count($after, "\n");
		
		if( $current !== $root )
			throw new ParseError($this, 'missing {end}', $line_count + 1);
		
		// Add the last remaining content to the root node
		$root->add('html')->set('content', $after);
		
		$this->root_block = $root;
	}
	
	/**
	 * Replace blocks and variables in the template's content.
	 * 
	 * @return string The template's content, with replaced blocks and variables.
	 */
	function render() {
		// Use recursion to parse all blocks from the root level
		return self::render_block($this->root_block, $this);
	}
	
	/**
	 * Render a single block, recursively parsing its sub-blocks with a given data scope.
	 * 
	 * @param Node $block The block to render.
	 * @param Node $data The data block to search in for the variable values.
	 * @return string The rendered block.
	 * @uses replace_variable()
	 */
	private static function render_block(Node $block, Node $data) {
		$html = '';
		
		foreach( $block->get_children() as $child ) {
			switch( $child->get_name() ) {
				case 'html':
					$html .= $child->get('content');
					break;
				case 'block':
					$block_name = $child->get('name');
					
					foreach( $data->find($block_name) as $child_data )
						$html .= self::render_block($child, $child_data);
					
					break;
				case 'variable':
					$html .= self::replace_variable($child->get('content'), $data);
			}
		}
		
		return $html;
	}
	
	/**
	 * Replace a variable name if it exists within a given data scope.
	 * 
	 * Applies any of the following macro's:
	 * 
	 * --------
	 * Variable
	 * --------
	 * <code>{var_name[:func1:func2:...]}</code>
	 * *var_name* can be of the form *foo.bar*. In this case, *foo* is the
	 * name of an object or associative array variable. *bar* is a property
	 * name to get of the object, or the associative index to the array.
	 * *func1*, *func2*, etc. are helper functions that are executed in the
	 * same order as listed. The retuen value of each helper function replaces
	 * the previous variable value.
	 * 
	 * ------------
	 * If-statement
	 * ------------
	 * <code>{if:condition:success_variable[:else:failure_variable]}</code>
	 * *condition* is evaluated to a boolean. If it evaluates to TRUE, the
	 * value of *success_variable* is used. Otherwise, the value of
	 * *failure_variable* is used (defaults to an empty string if no
	 * else-statement is specified).
	 * 
	 * @param string $variable The variable to replace.
	 * @param Node $data The data block to search in for a value.
	 * @return string The variable's value if it exists, the original string
	 *                with curly brackets otherwise.
	 * @throws \UnexpectedValueException If a helper function is not callable.
	 */
	private static function replace_variable($variable, Node $data) {
		// If-(else-)statement
		if( preg_match('/^if:([^:]*):(.*?)(?::else:(.*))?$/', $variable, $matches) ) {
			$condition = $data->get($matches[1]);
			$if = $data->get($matches[2]);
			
			if( $condition )
				return $if;
			
			return count($matches) > 3 ? self::replace_variable($matches[3], $data) : '';
		}
		
		// Default: variable with optional helper functions
		$parts = explode(':', $variable);
		$name = $parts[0];
		$helper_functions = array_slice($parts, 1);
		
		if( strpos($name, '.') !== false ) {
			// Variable of the form 'foo.bar'
			list($variable_name, $property) = explode('.', $name, 2);
			$object = $data->get($variable_name);
			
			if( is_object($object) && property_exists($object, $property) ) {
				// Object property
				$value = $object->$property;
			} elseif( is_array($object) && isset($object[$property]) ) {
				// Associative array index
				$value = $object[$property];
			}
		}
		
		// Default: Simple variable name
		if( !isset($value) )
			$value = $data->get($name);
		
		// Don't continue if the variable name is not found in the data block
		if( $value !== null ) {
			// Apply helper functions to the variable's value iteratively
			foreach( $helper_functions as $func ) {
				if( !is_callable($func) ) {
					throw new \UnexpectedValueException(
						sprintf('Helper function "%s" is not callable.', $func)
					);
				}
				
				$value = $func($value);
			}
			
			return $value;
		}
		
		return '{'.$variable.'}';
	}
	
	/**
	 * Remove all current include paths.
	 */
	static function clear_include_path() {
		self::$include_path = array();
	}
	
	/**
	 * Replace all include paths by a single new one.
	 * 
	 * @param string $path The new path to set as root.
	 * @uses clear_include_path()
	 */
	static function set_root($path) {
		self::clear_include_path();
		self::add_root($path);
	}
	
	/**
	 * Add a new include path.
	 * 
	 * @param string $path The path to add.
	 * @throws FileNotFoundError If the path does not exist.
	 */
	static function add_root($path) {
		if( $path[strlen($path) - 1] != '/' )
			$path .= '/';
		
		if( !is_dir($path) )
			throw new FileNotFoundError($path, true);
		
		self::$include_path[] = $path;
	}
}

/**
 * Error, thrown when an error occurs during the parsing of a template file.
 * 
 * @package WebBasics
 */
class ParseError extends \RuntimeException {
	/**
	 * Constructor.
	 * 
	 * Sets an error message with the path to the template file and a line number.
	 * 
	 * @param Template $tpl The template in which the error occurred.
	 * @param string $message A message describing the error.
	 * @param int $line The line number at which the error occurred.
	 */
	function __construct(Template $tpl, $message, $line) {
		$this->message = sprintf('Parse error in file %s, line %d: %s',
			$tpl->get_path(), $line, $message);
	}
}

?>