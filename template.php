<?php
/**
 * HTML template rendering functions.
 * 
 * @author Taddeus Kroes
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
 *         &lt;title&gt;{$page_title}&lt;/title&gt;
 *     &lt;/head&gt;
 *     &lt;body&gt;
 *         &lt;h1&gt;{$page_title}&lt;/h1&gt;
 *         &lt;div id="content"&gt;{$page_content}&lt;/div&gt;
 *         &lt;div id="ads"&gt;
 *             {block:ad}
 *             &lt;div class="ad"&gt;{$ad_content}&lt;/div&gt;
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
 * The variables of the form *{$variable}* that are used in the template
 * above, are examples of expressions. An expression is always enclosed in
 * curly brackets: *{expression}*. The grammar of all expressions that are
 * currently supported can be described as follows:
 * <code>
 * &lt;expression&gt; : {&lt;exp&gt;}
 * &lt;exp&gt; : &lt;nested_exp&gt;
 *       | &lt;nested_exp&gt;?&lt;nested_exp&gt;:&lt;nested_exp&gt;  # Conditional statement
 * &lt;nested_exp&gt; : &lt;variable&gt;
 *              | &lt;nested_exp&gt;||&lt;nested_exp&gt;  # Default value
 *              | &lt;function&gt;(&lt;nested_exp&gt;)    # Static function call
 *              | &lt;constant&gt;
 *              | &lt;html&gt;
 * &lt;variable&gt; : $&lt;name&gt;             # Regular variable
 *            | $&lt;name&gt;.&lt;name&gt;      # Object attribute or associative array value
 *            | $&lt;name&gt;.&lt;name&gt;()    # Method call (no arguments allowed)
 * &lt;function&gt; : &lt;name&gt;          # Global function
 *            | &lt;name&gt;::&lt;name&gt;  # Static class method
 * &lt;constant&gt; : An all-caps PHP constant: [A-Z0-9_]+
 * &lt;html&gt; : A string without parentheses, pipes, curly brackets or semicolons: [^()|{}:]*
 * &lt;name&gt; : A non-empty variable/method name consisting of [a-zA-Z0-9-_]+
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
				$current->add('expression')->set('content', $brackets_content);
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
	 * @uses evaluate_expression()
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
				case 'expression':
					$html .= self::evaluate_expression($child->get('content'), $data);
			}
		}
		
		return $html;
	}
	
	/**
	 * Evaluate a <variable> expression.
	 * 
	 * This function is a helper for {@link evaluate_expression()}.
	 * 
	 * @param array $matches Regex matches for variable pattern.
	 * @return string The evaluation of the variable.
	 * @param Node $data A data tree containing variable values to use.
	 * @throws \BadMethodCallException If an error occured while calling a variable method.
	 * @throws \OutOfBoundsException If an unexisting array key is requested.
	 * @throws \UnexpectedValueException In some other error situations.
	 */
	private static function evaluate_variable(array $matches, Node $data) {
		$variable = $matches[1];
		$value = $data->get($variable);
		
		if( count($matches) == 3 ) {
			// $<name>.<name>
			$attribute = $matches[2];
			
			if( $value === null ) {
				throw new \UnexpectedValueException(
					sprintf('Cannot get attribute "%s.%s": value is NULL', $variable, $attribute)
				);
			}
			
			$attr_error = function($error, $class='\UnexpectedValueException') use ($attribute, $variable) {
				throw new $class(
					sprintf('Cannot get attribute "%s.%s": %s', $variable, $attribute, $error)
				);
			};
			
			if( is_array($value) ) {
				isset($value[$attribute]) || $attr_error('no such key', '\OutOfBoundsException');
				$value = $value[$attribute];
			} elseif( is_object($value) ) {
				isset($value->$attribute) || $attr_error('no such attribute');
				$value = $value->$attribute;
			} else {
				$attr_error('variable is no array or object');
			}
		} elseif( count($matches) == 4 ) {
			// $<name>.<name>()
			$method = $matches[2];
			
			if( $value === null ) {
				throw new \UnexpectedValueException(
					sprintf('Cannot call method "%s.%s()": object is NULL', $variable, $method)
				);
			}
			
			$method_error = function($error) use ($method, $variable) {
				throw new \BadMethodCallException(
					sprintf('Cannot call method "%s.%s()": %s', $variable, $method, $error)
				);
			};
			
			if( is_object($value) ) {
				method_exists($value, $method) || $method_error('no such method');
				$value = $value->$method();
			} else {
				$method_error('variable is no object');
			}
		}
		
		return $value;
	}
	
	/**
	 * Evaluate a conditional expression.
	 * 
	 * This function is a helper for {@link evaluate_expression()}.
	 * 
	 * @param array $matches Regex matches for conditional pattern.
	 * @param Node $data A data tree containing variable values to use for
	 *                   variable expressions.
	 * @return string The evaluation of the condition.
	 */
	private static function evaluate_condition(array $matches, Node $data) {
		if( self::evaluate_expression($matches[1], $data, false) ) {
			// Condition evaluates to true: return 'if' evaluation
			return self::evaluate_expression($matches[2], $data, false);
		} elseif( count($matches) == 4 ) {
			// <nested_exp>?<nested_exp>:<nested_exp>
			return self::evaluate_expression($matches[3], $data, false);
		}
		
		// No 'else' specified: evaluation is an empty string
		return '';
	}
	
	/**
	 * Evaluate a static function call expression.
	 * 
	 * This function is a helper for {@link evaluate_expression()}.
	 * 
	 * @param array $matches Regex matches for function pattern.
	 * @param Node $data A data tree containing variable values to use for
	 *                   variable expressions.
	 * @return string The evaluation of the function call.
	 * @throws \BadFunctionCallException If the function is undefined.
	 */
	private static function evaluate_function(array $matches, Node $data) {
		$function = $matches[1];
		$parameter = $matches[2];
		
		if( !is_callable($function) ) {
			throw new \BadFunctionCallException(
				sprintf('Cannot call function "%s": function is not callable', $function)
			);
		}
		
		$parameter_value = self::evaluate_expression($parameter, $data, false);
		
		return call_user_func($function, $parameter_value);
	}
	
	/**
	 * Evaluate a PHP-constant expression.
	 * 
	 * This function is a helper for {@link evaluate_expression()}.
	 * 
	 * @param string $constant The name of the PHP constant.
	 * @param bool $root_level Whether the expression was enclosed in curly
	 *                         brackets (FALSE for sub-expressions);
	 * @return string The evaluation of the constant if it is defined, the
	 *                original constant name otherwise.
	 */
	private static function evaluate_constant($constant, $root_level) {
		if( defined($constant) )
			return constant($constant);
		
		return $root_level ? '{' . $constant . '}' : $constant;
	}
	
	/**
	 * Evaluate an expression.
	 * 
	 * The curly brackets should already have been stripped before passing an
	 * expression to this method.
	 * 
	 * @param string $expression The expression to evaluate.
	 * @param Node $data A data tree containing variable values to use for
	 *                   variable expressions.
	 * @param bool $root_level Whether the expression was enclosed in curly
	 *                         brackets (FALSE for sub-expressions);
	 * @return string The evaluation of the expression if present, the
	 *                original string enclosed in curly brackets otherwise.
	 */
	private static function evaluate_expression($expression, Node $data, $root_level=true) {
		if( $expression ) {
			$name = '[a-zA-Z0-9-_]+';
			$function = "$name(?:::$name)?";
			
			if( preg_match("/^([^?]*?)\s*\?([^:]*)(?::(.*))?$/", $expression, $matches) ) {
				// <nested_exp>?<nested_exp> | <nested_exp>?<nested_exp>:<nested_exp>
				return self::evaluate_condition($matches, $data);
			} elseif( preg_match("/^\\$($name)(?:\.($name)(\(\))?)?$/", $expression, $matches) ) {
				// $<name> | $<name>.<name> | $<name>.<name>()
				return self::evaluate_variable($matches, $data);
			} elseif( preg_match("/^($function)\((.+?)\)?$/", $expression, $matches) ) {
				// <function>(<nested_exp>)
				return self::evaluate_function($matches, $data);
			} elseif( preg_match("/^([A-Z0-9_]+)$/", $expression, $matches) ) {
				// <constant>
				return self::evaluate_constant($expression, $root_level);
			} elseif( ($split_at = strpos($expression, '||', 1)) !== false ) {
				// <nested_exp>||<nested_exp>
				try {
					return self::evaluate_expression(substr($expression, 0, $split_at), $data, false);
				} catch(\RuntimeException $e) {
					return self::evaluate_expression(substr($expression, $split_at + 2), $data, false);
				}
			}
		}
		
		// No expression: return original string
		return $root_level ? '{' . $expression . '}' : $expression;
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