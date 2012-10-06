<?php
/**
 * 
 * 
 * @author Taddeus Kroes
 * @date 05-10-2012
 */

namespace webbasics;

require_once 'router.php';

abstract class BaseHandler implements RouteHandler {
	function handleRequest(array $data) {
		$request_type = strtolower($_SERVER['REQUEST_METHOD']);
		
		// Try to use first match value as method name, e.g. getAction() if
		// first match value is "action"
		if (count($data)) {
			$method_name = $request_type . camelize($data[0], true);
			
			if (method_exists($this, $method_name)) {
				array_shift($data);
				
				if (count($data)) 
					return $this->$method_name($data);
				else
					return $this->$method_name();
			}
			
			// get($data) or post($data)
			return $this->$request_type($data);
		}
		
		// get() or post()
		return $this->$request_type();
	}
}

?>