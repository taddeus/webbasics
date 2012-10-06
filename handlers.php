<?php
/**
 * 
 * 
 * @author Taddeus Kroes
 * @date 05-10-2012
 */

namespace webbasics;

require_once 'router.php';

class BaseHandler implements RouteHandler {
	function handleRequest(array $data) {
		$request_type = strtolower($_SERVER['REQUEST_METHOD']);
		
		// Try to use first match value as method name, e.g. getAction() if
		// first match value is "action"
		if (count($data)) {
			$method_name = $request_type . Inflector::capitalize(array_splice($data, 0, 1));
			
			if (method_exists($this, $method_name)) {
				if (count($data))
					$this->$method_name($data);
				else
					$this->$method_name();
			}
			
			// get($data) or post($data)
			$this->$request_type($data);
		}
		
		// get() or post()
		$this->$request_type();
	}
}

?>