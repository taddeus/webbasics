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
 * Format a string using parameters in an associative array.
 * 
 * <code>
 * echo asprintf('foo %(bar)', array('bar' => 'baz'));  // prints 'foo baz'
 * </code>
 * 
 * @param string $format The string to format.
 * @param array $params An associative array with parameters that are used in $format.
 * @package WebBasics
 */
function asprintf($format, array $params) {
	return preg_replace_callback(
		'/%\(([a-z0-9-_ ]*)\)/i',
		function($matches) use ($params) {
			return (string)$params[$matches[1]];
		},
		$format
	);
}

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