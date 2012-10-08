<?php
/**
 * Include file for WebBasics library.
 * 
 * @author Taddeus Kroes
 * @date 05-10-2012
 * @since 0.2
 */

// @codeCoverageIgnoreStart
require_once 'logger.php';
require_once 'base.php';
require_once 'autoloader.php';
require_once 'collection.php';
require_once 'router.php';
require_once 'template.php';
require_once 'session.php';
require_once 'security.php';

if (defined('WB_INCLUDE_PHPACTIVERECORD') && WB_INCLUDE_PHPACTIVERECORD) {
	require_once 'php-activerecord/ActiveRecord.php';
	require_once 'users.php';
}
// @codeCoverageIgnoreEnd

?>