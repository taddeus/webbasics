<?php
/**
 * Security functions for user authentication and authorization.
 * 
 * Usage example:
 * <code>
 * try {
 *     $security = webbasics\Security::getInstance();
 *     
 *     // SecureUser: can the origin of the request be trusted?
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
 *     $template->set('token', $user->generateToken());
 *     
 *     ...
 *     
 * } catch(webbasics\SecureUserFailed $e) {
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
 * $security = webbasics\Security::getInstance();
 * 
 * // Simple: use a plain password
 * if (!$security->attemptPasswordLogin($user, $_POST['password']))
 *     die('Invalid password');
 * 
 * // More secure: hash the password in a javascript function before
 * // submitting the login form
 * if (!$security->attemptPasswordHashLogin($user, $_POST['password_hash']))
 *     die('Invalid password');
 * </code>
 * 
 * And the User model implementation used in the example above:
 * <code>
 * 
 * use webbasics\ActiveRecordUser;
 * class User extends webbasics\ActiveRecordUser {}
 * </code>
 * 
 * WebBasics provides the {@link AuthenticatedUser} and {@link AuthorizedUser}
 * classes, which extend ActiveRecord\Model and implement both security
 * interfaces.
 * 
 * @author Taddeus Kroes
 * @date 06-10-2012
 * @since 0.2
 * @todo Documentation, unit tests
 */

namespace webbasics;

require_once 'session.php';

class Security implements Singleton {
	/**
	 * All alphanumeric characters.
	 * @var string
	 */
	const ALNUM_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUWXYZ01234567890123456789';
	
	const TOKEN_NAME = 'auth_token';
	const USERDATA_NAME = 'auth_userdata';
	
	private static $instance;
	
	/**
	 * User that is currently logged in.
	 * @var BaseUser
	 */
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
		$session->set(self::TOKEN_NAME, $token);
		return $token;
	}
	
	function requireToken($request_token) {
		if ($request_token != $this->getSavedToken())
			throw new SecureUserFailed('invalid token "%s"', $request_token);
	}
	
	private function getSavedToken() {
		$session = Session::getInstance();
		
		if (!$session->isRegistered(self::TOKEN_NAME))
			throw new SecureUserError('no token saved in session');
		
		return $session->get(self::TOKEN_NAME);
	}
	
	function requireLogin() {
		if ($this->user === null && !$this->loadUserFromSession())
			throw new SecureUserFailed('no user is logged in');
	}
	
	private function loadUserFromSession() {
		$session = Session::getInstance();
		
		if (!$session->isRegistered(self::USERDATA_NAME))
			return false;
		
		// Load session data
		$user = $session->get(self::USERDATA_NAME);
		
		// Verify session data
		if ($user->getSessionHash() != $this->getSessionHash())
			throw new SecureUserFailure('session data could not be verified');
		
		$this->user = $user;
		return true;
	}
	
	function getSessionHash() {
		return self::hash(Session::getInstance()->getId());
	}
	
	function requireUserRole($required_role) {
		if (!($this->user instanceof RoleUser))
			throw new AuthorizationError('user must implement interface RoleUser');
		
		if ($this->user->getRole() != $required_role)
			throw new AuthorizationFailed('page requires user role "%s"', $required_role);
	}
	
	function attemptPasswordLogin(BaseUser $user, $password) {
		return $this->attemptPasswordHashLogin($user, self::hash($password));
	}
	
	function attemptPasswordHashLogin(BaseUser $user, $password_hash) {
		if ($password_hash != $user->getPasswordHash())
			return false;
		
		$this->loginUser($user);
		return true;
	}
	
	private function loginUser(BaseUser $user) {
		// Create a new session id so that a hijacked session will not become
		// logged in as well
		Session::getInstance()->regenerateId();
		
		// Save a hash of the new session id in the database for verification
		$user->setSessionHash($this->getSessionHash());
		$this->user = clone $user;
		Session::getInstance()->set(self::USERDATA_NAME, $this->user);
	}
	
	/**
	 * Get the hash value of a string.
	 * 
	 * THe hash function used is SHA-256.
	 * 
	 * @param string $string The string to hash.
	 * @return string A 64-byte hash.
	 */
	static function hash($string) {
		return hash('sha256', $string);
	}
	
	/**
	 * Generate a random string of the specified length.
	 * 
	 * @param int $lengh The length of the string to generate.
	 * @param string $chars The caracters to choose from, defaults to {@link ALNUM_CHARS}.
	 * @return string The generated string.
	 */
	static function generateRandomString($length, $chars=self::ALNUM_CHARS) {
		srand(time());
		$string = '';
		
		for ($i = 0; $i < $length; $i++)
			$string .= $chars[rand(0, strlen($chars) - 1)];
		
		return $string;
	}
}

interface BaseUser {
	function getUsername();
	function getPasswordHash();
}

interface SecureUser extends BaseUser {
	function getSessionHash();
	function setSessionHash($hash);
}

interface RoleUser {
	function getRole();
}

/**
 * Exception, thrown when an error occurs during authentication.
 */
class SecureUserError extends FormattedException {}

/**
 * Exception, thrown when the current user cannot be authenticated.
 */
class SecureUserFailed extends FormattedException {}

/**
 * Exception, thrown when an error occurs during authorization.
 */
class AuthorizationError extends FormattedException {}

/**
 * Exception, thrown when the current user is unauthorized to perform the
 * current request.
 */
class AuthorizationFailed extends FormattedException {}

/**
 * The StaticUser class is meant for websites without databases, it allows for
 * hard-coded usernames and passwords to be specified in user objects.
 * 
 * Example usage:
 * <code>
 * class JohnDoe extends webbasics\StaticUser {
 *     function getUsername() {
 *         return 'john';
 *     }
 *     
 *     function getPassword() {
 *         return 'foobar';
 *     }
 * }
 * 
 * // Login controller:
 * $security = webbasics\Security::getInstance();
 * 
 * switch ($_POST['username']) {
 *     case 'john':
 *         $success = $security->attemptPasswordLogin(new JohnDoe, $_POST['password']);
 *         break;
 *     ...
 * }
 * 
 * // And this might be handy during debugging:
 * $security->loginUser(new JohnDoe);
 * </code>
 * 
 * Note that this simple method of authentication even allows role-based authorization:
 * <code>
 * abstract class User extends webbasics\StaticUser implements webbasics\RoleUser {}
 * 
 * class JohnDoe extends User {
 *     function getUsername { return 'john'; }
 *     function getPassword { return 'foobar'; }
 *     function getRole     { return 'admin'; }
 * }
 * 
 * class JaneDoe extends User {
 *     function getUsername { return 'jane'; }
 *     function getPassword { return 'barfoo'; }
 *     function getRole     { return 'member'; }
 * }
 * </code>
 */
abstract class StaticUser implements BaseUser {
	/**
	 * @see BaseUser::getPasswordHash()
	 */
	function getPasswordHash() {
		return Security::hash($this->getPassword());
	}
	
	/**
	 * 
	 * 
	 * @return string 
	 */
	abstract function getPassword();
}

?>