<?php
/**
 * Utility functions for WebBasics library.
 * 
 * @author Taddeus Kroes
 * @date 05-10-2012
 * @since 0.2
 */

namespace webbasics;

/**
 * Camelize a string.
 * 
 * @param string $string The string to camelize.
 * @param bool $upper Whether to make the first character uppercase (defaults to FALSE).
 * @return string The camelized string.
 */
function camelize($string, $upper=false) {
	$camel = preg_replace_callback('/[_ -]([a-z])/', function($matches) {
		return strtoupper($matches[1]);
	}, $string);
	
	if ($upper)
		return ucfirst($camel);
	
	return $camel;
}

?>