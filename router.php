<?php
/**
 * Functions for URL routing: given an URL, call the corresponding handler
 * function (the 'route' to the corresponding output).
 * 
 * @author Taddeus Kroes
 * @date 14-07-2012
 */

namespace webbasics;

require_once 'base.php';

/**
 * A Router is used to call a handler function with corresponding to an URL.
 * 
 * Simple example: a website with the pages 'home' and 'contact'.
 * <code>
 * function home() {
 *     return 'This is the home page.';
 * }
 * 
 * function contact() {
 *     return 'This is the contact page.';
 * }
 * 
 * $router = new Router(array(
 *     '/home' => 'home',
 *     '/contact' => 'contact'
 * ));
 * $response = $router->call_handler('/home');     // 'This is the home page.'
 * $response = $router->call_handler('/contact');  // 'This is the contact page.'
 * </code>
 * 
 * You can use regular expression patterns to specify an URL. Any matches are
 * passed to the handler function as parameters:
 * <code>
 * function page($pagename) {
 *     return "This is the $pagename page.";
 * }
 * 
 * $router = new Router(array(
 *     '/(home|contact)' => 'page'
 * ));
 * $response = $router->call_handler('/home');     // 'This is the home page.'
 * $response = $router->call_handler('/contact');  // 'This is the contact page.'
 * </code>
 * 
 * @package WebBasics
 */
class Router extends Base {
	/**
	 * The regex delimiter that is added to the begin and end of patterns.
	 * 
	 * @var string
	 */
	const DELIMITER = '%';
	
	/**
	 * An associative array of regex patterns pointing to handler functions.
	 * 
	 * @var array
	 */
	private $routes = array();
	
	/**
	 * Create a new Router instance.
	 * 
	 * @param array $routes An initial list of routes to set.
	 */
	function __construct(array $routes=array()) {
		foreach ($routes as $pattern => $handler)
			$this->add_route($pattern, $handler);
	}
	
	/**
	 * Add a route as a (pattern, handler) pair.
	 * 
	 * The pattern is regular expression pattern without delimiters. The
	 * function adds '%^' at the begin and '$%' at the end of the pattern as
	 * delimiters.
	 * 
	 * Any matches for groups in the pattern (which are contained in
	 * parentheses), the matches for these are passed to the handler function.
	 * 
	 * @param string $pattern A regex pattern to mach URL's against.
	 * @param mixed $handler The handler function to call when $pattern is matched.
	 * @throws \InvalidArgumentException If $handler is not callable.
	 */
	function add_route($pattern, $handler) {
		if (!is_callable($handler))
			throw new \InvalidArgumentException(sprintf('Handler for patterns "%s" is not callable.', $pattern));
		
		$this->routes[self::DELIMITER . '^' . $pattern . '$' . self::DELIMITER] = $handler;
	}
	
	/**
	 * Call the handler function corresponding to the specified url.
	 * 
	 * If any groups are in the matched regex pattern, a list of matches is
	 * passed to the handler function. If the handler function returns FALSE,
	 * the url has not been 'handled' and the next pattern will be checked for
	 * a match. Otherwise, the return value of the handler function is
	 * returned as the result.
	 * 
	 * @param string $url An url to match the saved patterns against.
	 * @return mixed FALSE if no pattern was matched, the return value of the
	 *               corresponding handler function otherwise.
	 */
	function call_handler($url) {
		foreach ($this->routes as $pattern => $handler) {
			if (preg_match($pattern, $url, $matches)) {
				array_shift($matches);
				$result = call_user_func_array($handler, $matches);
				
				if ($result !== false)
					return $result;
			}
		}
		
		return false;
	}
}

?>