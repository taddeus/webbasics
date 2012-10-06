<?php

require_once 'SingletonTestCase.php';
require_once 'security.php';
use webbasics\Security;

class SecurityTest extends SingletonTestCase {
	function getClassName() {
		return 'webbasics\Security';
	}
}

?>