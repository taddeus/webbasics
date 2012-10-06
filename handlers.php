<?php
/**
 * 
 * 
 * @author Taddeus Kroes
 * @date 05-10-2012
 */

namespace webbasics;

require_once 'router.php';
require_once 'security.php';

abstract class BaseHandler implements RouteHandler {
	function handleRequest(array $data) {
		list($method_name, $args) = $this->extractMethodAndArgs($data);
		
		return call_user_func_array(array($this, $method_name), $args);
	}
	
	protected function extractMethodAndArgs(array $data) {
		$request_type = strtolower($_SERVER['REQUEST_METHOD']);
		
		// Try to use first match value as method name, e.g. getAction() if
		// first match value is "action" and request method is "GET"
		if (count($data)) {
			$method_name = $request_type . camelize($data[0], true);
			
			if (method_exists($this, $method_name)) {
				// getAction() or getAction($data)
				array_shift($data);
			} else {
				// get($data) or post($data)
				$method_name = $request_type;
			}
		} else {
			// get() or post()
			$method_name = $request_type;
		}
		
		$args = count($data) ? array($data) : array();
		return array($method_name, $args);
	}
}

abstract class AuthenticatedHandler extends BaseHandler {
	function handleRequest(array $data) {
		// A user must be logged in
		self::checkLogin();
		
		// Base class will call the corresponding method
		return parent::handleRequest();
	}
	
	static function checkLogin() {
		Authentication::getInstance()->requireLogin();
	}
}

abstract class TokenizedHandler extends BaseHandler {
	const TOKEN_NAME = 'auth_token';
	
	function handleRequest(array $data) {
		// Token must exist and have the right value
		self::checkToken();
		
		// Base class will call the corresponding method
		return parent::handleRequest();
	}
	
	static function checkToken() {
		if (!isset($_REQUEST[self::TOKEN_NAME]))
			throw new AuthenticationError('token missing in request data');
		
		Authentication::getInstance()->requireToken($_REQUEST[self::TOKEN_NAME]);
	}
}

abstract class AuthorizedHandler extends BaseHandler {
	function handleRequest(array $data) {
		// A user must be logged in
		AuthenticatedHandler::checkLogin();
		
		// The user must have access to the called method
		list($method_name, $args) = $this->extractMethodAndArgs($data);
		$role = $this->determineRequiredRole($method_name, $args);
		Authentication::getInstance()->requireUserRole($role);
		
		// Base class will call the corresponding method
		return parent::handleRequest();
	}
	
	abstract function getRequiredRole($method_name, array $args);
}

abstract class TokenizedAuthenticatedHandler extends TokenizedHandler {
	function handleRequest(array $data) {
		// A user must be logged in
		AuthenticatedHandler::checkLogin();
		
		// Parent class will check the token
		return parent::handleRequest();
	}
}

abstract class TokenizedAuthorizedHandler extends AuthorizedHandler {
	function handleRequest(array $data) {
		// Token must exist and have the right value
		TokenizedHandler::checkToken();
		
		// Parent class will verify that a user is logged in and has access to
		// the called method
		return parent::handleRequest($data);
	}
}

?>