<?php
/**
 * Security functions for user authentication and authorization.
 * 
 * Usage example:
 * <code>
 * try {
 *     $security = webbasics\Security::getInstance();
 *     
 *     // Authentication: can the origin of the request be trusted?
 *     
 *     // Verify that a user is logged in
 *     $security->requireLogin();
 *     
 *     // Use a security token to verify that the request originated from a
 *     // trusted page. This is recommended if, for example, the script makes
 *     // changes to the database
 *     $security->requireToken($_REQUEST['token']);
 *     
 *     // Authorization: is the user allowed to request this page?
 *     $security->requireUserRole('admin');
 *     
 *     ...
 *     
 *     // Pass token to template so that it can be used in a submitted form or
 *     // AJAX request
 *     $template->set('token', $auth->generateToken());
 *     
 *     ...
 *     
 * } catch(webbasics\AuthenticationFailed $e) {
 *     die('Get lost hacker!');
 * } catch(webbasics\AuthorizationFailed $e) {
 *     http_response_code(403);
 *     die('You are not authorized to view this page.');
 * }
 * </code>
 * 
 * Corresponding login controller example:
 * <code>
 * // Find the user using ActiveRecord (not part of the WebBasics library)
 * $user = User::first(array('username' => $_POST['username']));
 * 
 * if (!$user)
 *     die('Invalid username');
 * 
 * // Current user is part of the 
 * $security = webbasics\Security::getInstance();
 * $security->setUser($user);
 * 
 * // Simple: use a plain password
 * if (!$security->attemptPassword($user, $_POST['password']))
 *     die('Invalid password');
 * 
 * // More secure: hash the password in a javascript function before
 * // submitting the login form
 * if (!$security->attemptPasswordHash($user, $_POST['password_hash']))
 *     die('Invalid password');
 * </code>
 * 
 * And the User model implementation used in the example above:
 * <code>
 * use ActiveRecord\Model;
 * use webbasics\AuthenticatedUser;
 * use webbasics\AuthorizedUser;
 * 
 * class User extends Model implements AuthenticatedUser, AuthorizedUser {
 *     function getUsername() {
 *         return $this->username;
 *     }
 *     
 *     function getPasswordHash() {
 *         return $this->password;
 *     }
 *     
 *     function getCookieToken() {
 *         return $this->cookie_token;
 *     }
 *     
 *     function setCookieToken($token) {
 *         $this->update_attribute('cookie_token', $token);
 *     }
 *     
 *     function getRegistrationToken() {
 *         return $this->registration_token;
 *     }
 *     
 *     function setRegistrationToken($token) {
 *         $this->update_attribute('registration_token', $token);
 *     }
 *     
 *     function getRole() {
 *         return $this->role;
 *     }
 * }
 * </code>
 * 
 * @author Taddeus Kroes
 * @date 05-10-2012
 */

namespace webbasics;

require_once 'base.php';

interface AuthenticatedUser {
	function getUsername();
	function getPasswordHash();
	
	function getCookieToken();
	function setCookieToken($token);
	
	function getRegistrationToken();
	function setRegistrationToken($token);
}

interface AuthorizedUser {
	function getRole();
}

class Security implements Singleton {
	const SESSION_TOKEN_NAME = 'auth_token';
	const SESSION_NAME_USERDATA = 'auth_userdata';
	
	private static $instance;
	
	private $user;
	
	static function getInstance() {
		if (self::$instance === null)
			self::$instance = new self;
		
		return self::$instance;
	}
	
	private function __construct() {}
	
	function generateToken() {
		$session = Session::getInstance();
		$token = sha1(self::generateRandomString(10));
		$session->set(self::SESSION_TOKEN_NAME, $token);
		return $token;
	}
	
	function requireToken($request_token) {
		if ($request_token != $this->getSavedToken())
			throw new AuthenticationFailed('invalid token "%s"', $request_token);
	}
	
	private function getSavedToken() {
		$session = Session::getInstance();
		
		if (!$session->isRegistered(self::SESSION_TOKEN_NAME))
			throw new AuthenticationError('no token saved in session');
		
		return $session->get(self::SESSION_TOKEN_NAME);
	}
	
	function sessionDataExists() {
		return Session::getInstance()->areRegistered(array(
			self::SESSION_TOKEN_NAME, self::SESSION_NAME_USERDATA));
	}
	
	function requireLogin() {
		
	}
	
	function requireUserRole() {
		
	}
	
	//function setUser(AuthenticatedUser $user) {
	//	$this->user = $user;
	//}
	
	static function generateRandomString($length) {
		$CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUWXYZ01234567890123456789';
		$string = '';
		
		srand(time());
		
		for ($i = 0; $i < $length; $i++)
			$string .= $CHARS[rand(0, strlen($CHARS) - 1)];
		
		return $string;
	}
}

class AuthenticationError extends FormattedException {}
class AuthenticationFailed extends FormattedException {}

class AuthorizationError extends FormattedException {}
class AuthorizationFailed extends FormattedException {}

?>