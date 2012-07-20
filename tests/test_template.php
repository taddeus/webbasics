<?php

require_once 'template.php';
use WebBasics\Template;
use WebBasics\Node;

define('TEMPLATES_DIR', 'tests/_files/templates/');
define('FOOBAR', 'foobar_const');

class DataObject {
	var $foo = 'bar';
	var $bar = 'baz';
	
	function baz() {
		return 'foobar';
	}
	
	static function foobar($param) {
		return ucfirst($param);
	}
}

class TemplateTest extends PHPUnit_Framework_TestCase {
	/**
	 * @depends test_add_root_success
	 */
	function setUp() {
		Template::set_root(TEMPLATES_DIR);
		$this->tpl = new Template('foo');
		$this->data = new Node();
		
		$object = new stdClass();
		$object->foo = 'bar';
		$object->bar = 'baz';
		
		$this->data->set(array(
			'foo' => 'bar',
			'bar' => 'baz',
			'FOO' => 'BAR',
			'true' => true,
			'false' => false,
			'array' => array('foo' => 'bar', 'bar' => 'baz'),
			'object' => new DataObject,
			'foobar' => 'my_foobar_variable',
			'foobaz' => 'MY_FOOBAZ_VARIABLE',
		));
	}
	
	/**
	 * @expectedException WebBasics\FileNotFoundError
	 * @expectedExceptionMessage Directory "non_existing_folder/" does not exist.
	 */
	function test_add_root_failure() {
		Template::add_root('non_existing_folder');
	}
	
	function assert_include_path_equals($expected) {
		$include_path = new ReflectionProperty('WebBasics\Template', 'include_path');
		$include_path->setAccessible(true);
		$this->assertEquals($expected, $include_path->getValue());
	}
	
	function test_clear_include_path() {
		Template::clear_include_path();
		$this->assert_include_path_equals(array());
	}
	
	/**
	 * @depends test_clear_include_path
	 */
	function test_add_root_success() {
		Template::clear_include_path();
		Template::add_root(TEMPLATES_DIR);
		$this->assert_include_path_equals(array(TEMPLATES_DIR));
		Template::add_root('tests/_files');
		$this->assert_include_path_equals(array(TEMPLATES_DIR, 'tests/_files/'));
	}
	
	/**
	 * @depends test_add_root_success
	 */
	function test_set_root() {
		Template::clear_include_path();
		Template::add_root(TEMPLATES_DIR);
		Template::add_root('tests/_files');
		Template::set_root(TEMPLATES_DIR);
		$this->assert_include_path_equals(array(TEMPLATES_DIR));
	}
	
	/**
	 * @expectedException RuntimeException
	 */
	function test_non_existing_template() {
		$bar = new Template('bar');
	}
	
	function test_other_root() {
		Template::add_root('tests/_files/other_templates');
		new Template('bar');
	}
	
	function test_get_path() {
		$this->assertEquals(TEMPLATES_DIR.'foo.tpl', $this->tpl->get_path());
	}
	
	function get_property($object, $property_name) {
		$rp = new ReflectionProperty($object, $property_name);
		$rp->setAccessible(true);
		return $rp->getValue($object);
	}
	
	function assert_is_html_node($node, $content) {
		$this->assertEquals('html', $node->get_name());
		$this->assertEquals($content, str_replace("\r\n", "\n", $node->get('content')));
		$this->assertEquals(array(), $node->get_children());
	}
	
	function assert_is_block_node($node, $block_name, $child_count) {
		$this->assertEquals('block', $node->get_name());
		$this->assertSame($block_name, $node->get('name'));
		$this->assertNull($node->get('content'));
		$this->assertEquals($child_count, count($node->get_children()));
	}
	
	function assert_is_exp_node($node, $brackets_content) {
		$this->assertEquals('expression', $node->get_name());
		$this->assertEquals($brackets_content, $node->get('content'));
		$this->assertEquals(array(), $node->get_children());
	}
	
	function test_parse_blocks_simple() {
		$root_block = $this->get_property($this->tpl, 'root_block');
		$this->assert_is_block_node($root_block, null, 1);
		
		list($child) = $root_block->get_children();
		$this->assert_is_html_node($child, 'test');
	}
	
	/**
	 * @depends test_parse_blocks_simple
	 */
	function test_parse_blocks_blocks() {
		$tpl = new Template('blocks');
		$root_block = $this->get_property($tpl, 'root_block');
		$this->assert_is_block_node($root_block, null, 3);
		
		list($before, $foo, $after) = $root_block->get_children();
		$this->assert_is_html_node($before, '');
		$this->assert_is_block_node($foo, 'foo', 3);
		$this->assert_is_html_node($after, '');
		
		list($foofoo, $bar, $foobaz) = $foo->get_children();
		$this->assert_is_html_node($foofoo, "\nfoofoo\n\t");
		$this->assert_is_block_node($bar, 'bar', 1);
		$this->assert_is_html_node($foobaz, "\nfoobaz\n");
		
		list($foobar) = $bar->get_children();
		$this->assert_is_html_node($foobar, "\n\tfoobar\n\t");
	}
	
	/**
	 * @depends test_parse_blocks_blocks
	 * @expectedException WebBasics\ParseError
	 * @expectedExceptionMessage Parse error in file tests/_files/templates/unexpected_end.tpl, line 5: unexpected {end}
	 */
	function test_parse_blocks_unexpected_end() {
		new Template('unexpected_end');
	}
	
	/**
	 * @depends test_parse_blocks_blocks
	 * @expectedException WebBasics\ParseError
	 * @expectedExceptionMessage Parse error in file tests/_files/templates/missing_end.tpl, line 6: missing {end}
	 */
	function test_parse_blocks_missing_end() {
		new Template('missing_end');
	}
	
	/**
	 * @depends test_parse_blocks_simple
	 */
	function test_parse_blocks_variables() {
		$tpl = new Template('variables');
		$root_block = $this->get_property($tpl, 'root_block');
		$this->assert_is_block_node($root_block, null, 5);
		
		list($foo, $foobar, $bar, $foobaz, $baz) = $root_block->get_children();
		$this->assert_is_html_node($foo, "foo\n");
		$this->assert_is_exp_node($foobar, '$foobar');
		$this->assert_is_html_node($bar, "\nbar\n");
		$this->assert_is_exp_node($foobaz, 'strtolower($foobaz)');
		$this->assert_is_html_node($baz, "\nbaz");
	}
	
	/**
	 * @depends test_parse_blocks_blocks
	 * @depends test_parse_blocks_variables
	 */
	function test_parse_blocks_full() {
		$tpl = new Template('full');
		$root_block = $this->get_property($tpl, 'root_block');
		$this->assert_is_block_node($root_block, null, 3);
		
		list($bar, $foo, $baz) = $root_block->get_children();
		$this->assert_is_html_node($bar, "bar\n");
		$this->assert_is_block_node($foo, 'foo', 5);
		$this->assert_is_html_node($baz, "\nbaz");
		
		list($foofoo, $bar, $first_space, $foobaz, $second_space) = $foo->get_children();
		$this->assert_is_html_node($foofoo, "\nfoofoo\n\t");
		$this->assert_is_block_node($bar, 'bar', 3);
		$this->assert_is_html_node($first_space, "\n");
		$this->assert_is_exp_node($foobaz, 'strtolower($foobaz)');
		$this->assert_is_html_node($second_space, "\n");
		
		list($space_before, $foobar, $space_after) = $bar->get_children();
		$this->assert_is_html_node($space_before, "\n\t");
		$this->assert_is_exp_node($foobar, '$foobar');
		$this->assert_is_html_node($space_after, "\n\t");
	}
	
	function evaluate_expression() {
		$args = func_get_args();
		$eval = new ReflectionMethod('WebBasics\Template', 'evaluate_expression');
		$eval->setAccessible(true);
		return $eval->invokeArgs(null, $args);
	}
	
	function assert_evaluates($expected, $expression) {
		$this->assertEquals($expected, $this->evaluate_expression($expression, $this->data));
	}
	
	/** 
	 * @expectedException \UnexpectedValueException
	 */
	function test_evaluate_variable_attribute_null() {
		$this->evaluate_expression('$foobarbaz.foo', $this->data);
	}
	
	/** 
	 * @expectedException \UnexpectedValueException
	 */
	function test_evaluate_variable_attribute_no_such_attribute() {
		$this->evaluate_expression('$object.foobar', $this->data);
	}
	
	/** 
	 * @expectedException \UnexpectedValueException
	 */
	function test_evaluate_variable_attribute_no_array_or_object() {
		$this->evaluate_expression('$foo.bar', $this->data);
	}
	
	/** 
	 * @expectedException \UnexpectedValueException
	 */
	function test_evaluate_variable_method_null() {
		$this->evaluate_expression('$foobarbaz.foo()', $this->data);
	}
	
	/** 
	 * @expectedException \BadMethodCallException
	 */
	function test_evaluate_variable_method_no_such_method() {
		$this->evaluate_expression('$object.foo()', $this->data);
	}
	
	/** 
	 * @expectedException \BadMethodCallException
	 */
	function test_evaluate_variable_method_no_object() {
		$this->evaluate_expression('$foo.bar()', $this->data);
	}
	
	function test_evaluate_variable_success() {
		$this->assert_evaluates('bar', '$array.foo');
		$this->assert_evaluates('bar', '$foo');
		$this->assert_evaluates('baz', '$bar');
		$this->assert_evaluates('bar', '$object.foo');
		$this->assert_evaluates('baz', '$object.bar');
		$this->assert_evaluates('foobar', '$object.baz()');
	}
	
	function test_evaluate_constant() {
		$this->assert_evaluates('foobar_const', 'FOOBAR');
		$this->assert_evaluates('{NON_DEFINED_CONST}', 'NON_DEFINED_CONST');
	}
	
	function test_evaluate_no_expression() {
		$this->assert_evaluates('{foo}', 'foo');
	}
	
	function test_evaluate_condition_if() {
		$this->assert_evaluates('bar', '$true?bar');
		$this->assert_evaluates('', '$false?bar');
	}
	
	function test_evaluate_condition_if_else() {
		$this->assert_evaluates('bar', '$true?bar:baz');
		$this->assert_evaluates('baz', '$false?bar:baz');
	}
	
	/**
	 * @depends test_evaluate_condition_if
	 * @depends test_evaluate_condition_if_else
	 */
	function test_evaluate_condition_spaces() {
		$this->assert_evaluates(' bar ', '$true? bar : baz');
		$this->assert_evaluates(' baz', '$false? bar : baz');
		
		$this->assert_evaluates(' bar ', '$true ? bar : baz');
		$this->assert_evaluates(' baz', '$false ? bar : baz');
		
		$this->assert_evaluates(' Foo bar ', '$true ? Foo bar : Baz foo');
		$this->assert_evaluates(' Baz foo', '$false ? Foo bar : Baz foo');
	}
	
	/** 
	 * @expectedException \BadFunctionCallException
	 */
	function test_evaluate_function_error() {
		$this->evaluate_expression('undefined_function($foo)', $this->data);
	}
	
	function test_evaluate_function_success() {
		$this->assert_evaluates('Bar', 'ucfirst($foo)');
		$this->assert_evaluates('Bar', 'DataObject::foobar($foo)');
	}
	
	/**
	 * @depends test_evaluate_function_success
	 */
	function test_evaluate_function_nested() {
		$this->assert_evaluates('Bar', 'ucfirst(strtolower($FOO))');
	}
	
	/**
	 * @depends test_evaluate_variable_success
	 * @depends test_evaluate_no_expression
	 * @depends test_evaluate_condition_spaces
	 * @depends test_evaluate_function_success
	 */
	function test_evaluate_expression_combined() {
		$this->assert_evaluates('Bar', '$true?ucfirst($foo)');
		$this->assert_evaluates('', '$false?ucfirst($foo)');
		$this->assert_evaluates('Bar', '$true?ucfirst($foo):baz');
		$this->assert_evaluates('baz', '$false?ucfirst($foo):baz');
		$this->assert_evaluates('Baz', 'ucfirst($array.bar)');
	}
	
	function assert_renders($expected_file, $tpl) {
		$expected_file = "tests/_files/rendered/$expected_file.html";
		$this->assertStringEqualsFile($expected_file, $tpl->render());
	}
	
	function test_render_simple() {
		$this->assertEquals('test', $this->tpl->render());
	}
	
	/**
	 * @depends test_evaluate_expression_combined
	 */
	function test_render_variable() {
		$tpl = new Template('variables');
		$tpl->set(array(
			'foobar' => 'my_foobar_variable',
			'foobaz' => 'MY_FOOBAZ_VARIABLE'
		));
		$this->assert_renders('variables', $tpl);
	}
	
	/**
	 * @depends test_render_simple
	 */
	function test_render_blocks() {
		$tpl = new Template('blocks');
		
		$foo = $tpl->add('foo');
		$foo->add('bar');
		$foo->add('bar');
		$tpl->add('foo');
		
		$this->assert_renders('blocks', $tpl);
	}
	
	/**
	 * @depends test_render_variable
	 * @depends test_render_blocks
	 */
	function test_render_full() {
		$tpl = new Template('full');
		$first_foo = $tpl->add('foo')->set('foobaz', 'FIRST_FOOBAZ_VAR');
		$first_foo->add('bar')->set('foobar', 'first_foobar_var');
		$second_foo = $tpl->add('foo')->set('foobaz', 'SECOND_FOOBAZ_VAR');
		$second_foo->add('bar')->set('foobar', 'second_foobar_var');
		$second_foo->add('bar')->set('foobar', 'third_foobar_var');
		$this->assert_renders('full', $tpl);
	}
}

?>