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
 * A Router is used to call a handler with corresponding to an URL.
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
 * $response = $router->callHandler('/home');     // 'This is the home page.'
 * $response = $router->callHandler('/contact');  // 'This is the contact page.'
 * </code>
 * 
 * You can use regular expression patterns to specify an URL. Any matches are
 * passed to the handler function in a single parameter:
 * <code>
 * function page(array $data) {
 *     $pagename = $data[0];
 *     return "This is the $pagename page.";
 * }
 * 
 * $router = new Router(array(
 *     '/(home|contact)' => 'page'
 * ));
 * $response = $router->callHandler('/home');     // 'This is the home page.'
 * $response = $router->callHandler('/contact');  // 'This is the contact page.'
 * </code>
 * 
 * Instead of functions, you can implement the RouteHandler interface in a
 * handler class. The router will create an instance of the handler class and call *handleRequest*
 * <code>
 * class MyHandler implements RouteHandler {
 *     function handleRequest(array $data) {
 *         $pagename = $data[0];
 *         return "This is the $pagename page.";
 *     }
 * }
 * 
 * $router = new Router(array(
 *     '/(home|contact)' => 'MyHandler'
 * ));
 * $response = $router->callHandler('/home');     // 'This is the home page.'
 * $response = $router->callHandler('/contact');  // 'This is the contact page.'
 * </code>
 * 
 * The WebBasics library provides a set of base handler classes implementing
 * the RouteHandler interface. These are sufficient for most usage cases.
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
			$this->addRoute($pattern, $handler);
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
	 * @param RouteHandler|callable $handler The handler function to call when $pattern is matched.
	 * @throws \InvalidArgumentException If $handler is not callable.
	 */
	function addRoute($pattern, $handler) {
		if (is_callable($handler)) {
			$handler = array('callable', $handler);
		} else if (!is_string($handler)) {
			throw new \InvalidArgumentException('Handler should be callable or class name.');
		} else if (!class_exists($handler)) {
			throw new \InvalidArgumentException(sprintf(
				'Handler class "%s" does not exist.', $handler
			));
		} else if (!in_array(__NAMESPACE__ . '\RouteHandler', class_implements($handler))) {
			throw new \InvalidArgumentException(sprintf(
				'Handler class "%s" should implement the RouteHandler interface.', $handler
			));
		} else {
			$handler = array('class', $handler);
		}
		
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
	function callHandler($url) {
		foreach ($this->routes as $pattern => $tuple) {
			if (preg_match($pattern, $url, $matches)) {
				list($type, $handler) = $tuple;
				array_shift($matches);
				
				switch ($type) {
					case 'callable':
						$result = count($matches) ? $handler($matches) : $handler();
						break;
					case 'class':
						$instance = new $handler;
						$result = $instance->handleRequest($matches);
				}
				
				if ($result !== false)
					return $result;
			}
		}
		
		return false;
	}
}

/**
 * Interface for handler classes which can be bound to a router pattern.
 * 
 * @package WebBasics
 */
interface RouteHandler {
	/**
	 * Handle an HTTP request.
	 * 
	 * @param string[] $data A list of matched pattern groups, without the zero-group.
	 * @return mixed FALSE if the handler function was not able to handle the
	 *               request, else the result of the reqeust.
	 */
	function handleRequest(array $data);
}

?>