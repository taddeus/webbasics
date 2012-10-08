<?php
/**
 * User model implementation linking PHPActiveRecord to the Security class.
 * 
 * @author Taddeus Kroes
 * @date 06-10-2012
 * @since 0.2
 */

namespace webbasics;

require_once 'security.php';
require_once 'php-activerecord/ActiveRecord.php';

abstract class ActiveRecordUser extends \ActiveRecord\Model implements SecureUser, RoleUser {
	/**
	 * Name of the cookie holding user data.
	 * @var string
	 */
	const COOKIE_NAME = 'auth_userdata';
	
	/**
	 * Keep authentication cookies for one month.
	 * @var int
	 */
	const COOKIE_EXPIRE = 2592000;
	
	/**
	 * Length of the salt prepended to the password.
	 * @var int
	 */
	const SALT_LENGTH = 6;
	
	/**
	 * Name of the table users are saved in.
	 * @var string
	 */
	static $table_name = 'users';
	
	/**
	 * 
	 * @var string[5]
	 */
	static $attr_protected = array('username', 'password_hash', 'salt', 'session_hash', 'role');
	
	/**
	 * Plain password, optional and used only during registration process.
	 * @var string
	 */
	public $password;
	
	/**
	 * 
	 */
	function before_create() {
		$this->hashPassword();
		$this->saltPasswordHash();
	}
	
	/**
	 * 
	 */
	function hashPassword() {
		if (!$this->password_hash && $this->password)
			$this->password_hash = Security::hash($this->password);
	}
	
	/**
	 * 
	 */
	function saltPasswordHash() {
		$this->password_hash = Security::hash(self::generateSalt() . $this->password_hash);
	}
	
	/**
	 * @see BaseUser::getUsername()
	 */
	function getUsername() {
		return $this->username;
	}
	
	/**
	 * @see BaseUser::getPasswordHash()
	 */
	function getPasswordHash() {
		return $this->password_hash;
	}
	
	/**
	 * @see SecureUser::getSessionHash()
	 */
	function getSessionHash() {
		return $this->session_hash;
	}
	
	/**
	 * @see SecureUser::setSessionHash()
	 */
	function setSessionHash($hash) {
		$this->update_attribute('session_hash', $hash);
	}
	
	/**
	 * 
	 * 
	 * @return string A 6-byte salt.
	 */
	private static function generateSalt() {
		$class_name = get_called_class();
		return Security::generateRandomString($class_name::SALT_LENGTH);
	}
	
	/**
	 * @see SecureUser::getRole()
	 */
	function getRole() {
		return $this->role;
	}
	
	function saveCookie() {
		$class_name = get_class($this);
		$data = array($this->getUsername(), $this->getSessionHash());
		setcookie($class_name::COOKIE_NAME, implode(',', $data), $class_name::COOKIE_EXPIRE);
	}
	
	/**
	 * @see 
	 */
	static function loadFromCookie() {
		$class_name = get_called_class();
		
		if (!isset($_COOKIE[$class_name::COOKIE_NAME]))
			return false;
		
		list($username, $session_hash) = explode(',', $_COOKIE[$class_name::COOKIE_NAME]);
		$user = $class_name::first(array(
			'conditions' => compact('username', 'session_hash')
		));
		
		Security::getInstance()->loginUser($user);
		$user->saveCookie();
		
		return true;
	}
}

abstract class RegisteredUser extends ActiveRecordUser {
	/**
	 * Length of passwords generated after registration.
	 * @var int
	 */
	const PASSWORD_LENGTH = 8;
	
	/**
	 * Attributes protected against mass assignment.
	 * @var string[6]
	 */
	static $attr_protected = array('username', 'password_hash', 'salt',
		'session_hash', 'role', 'registration_token');
	
	/**
	 * Send a confirmation e-mail after registration.
	 * @var string[1]
	 */
	static $after_create = array('composeConfirmationMail');
	
	function before_create() {
		parent::before_create();
		$this->registration_token = sha1($this->getUsername() . time());
	}
	
	static function confirmRegistrationToken($obfuscated_token) {
		$token = self::deobfuscateToken($obfuscated_token);
		$user = self::first(array(
			'conditions' => array('registration_token' => $token)
		));
		
		if (!$user || $token != $user->registration_token)
			return false;
		
		$user->confirmRegistration();
		
		return true;
	}
	
	function confirmRegistration() {
		$this->clearRegistrationToken();
		$this->composeWelcomeMail();
	}
	
	function clearRegistrationToken() {
		$this->update_attribute('registration_token', null);
	}
	
	function composeWelcomeMail() {
		$this->sendWelcomeMail($this->username, $this->generatePassword());
	}
	
	function generatePassword() {
		$class_name = get_class($this);
		$password = Security::generateRandomString($class_name::PASSWORD_LENGTH);
		$this->update_attribute('password_hash', Security::hash($password));
		return $password;
	}
	
	function composeConfirmationMail() {
		$this->sendConfirmationMail($this->username, $this->obfuscateToken());
	}
	
	function obfuscateToken() {
		$obfuscated = '';
		
		foreach (range(0, strlen($this->registration_token) - 1) as $i)
			$obfuscated .= $this->registration_token[$i] . chr(rand(97, 122));
		
		return $obfuscated;
	}
	
	static function deobfuscateToken($obfuscated) {
		$token = '';
		
		foreach (range(0, strlen($obfuscated) - 1, 2) as $i)
			$token .= $obfuscated[$i];
		
		return $token;
	}
	
	function isConfirmed() {
		return $this->registration_token === null;
	}
	
	abstract function sendConfirmationMail($username, $token);
	abstract function sendWelcomeMail($username, $password);
}

?>