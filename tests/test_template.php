<?php

require_once 'template.php';
use WebBasics\Template;
use WebBasics\Node;

define('TEMPLATES_DIR', 'tests/_files/templates/');

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
			'object' => $object,
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
	
	function assert_is_variable_node($node, $brackets_content) {
		$this->assertEquals('variable', $node->get_name());
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
		$this->assert_is_variable_node($foobar, 'foobar');
		$this->assert_is_html_node($bar, "\nbar\n");
		$this->assert_is_variable_node($foobaz, 'foobaz:strtolower');
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
		$this->assert_is_variable_node($foobaz, 'foobaz:strtolower');
		$this->assert_is_html_node($second_space, "\n");
		
		list($space_before, $foobar, $space_after) = $bar->get_children();
		$this->assert_is_html_node($space_before, "\n\t");
		$this->assert_is_variable_node($foobar, 'foobar');
		$this->assert_is_html_node($space_after, "\n\t");
	}
	
	function assert_replaces($expected, $variable) {
		$rm = new ReflectionMethod('WebBasics\Template', 'replace_variable');
		$rm->setAccessible(true);
		$this->assertEquals($expected, $rm->invoke(null, $variable, $this->data));
	}
	
	function test_replace_variable_simple() {
		$this->assert_replaces('bar', 'foo');
	}
	
	/**
	 * @depends test_replace_variable_simple
	 */
	function test_replace_variable_helper_functions() {
		$this->assert_replaces('Bar', 'foo:ucfirst');
		$this->assert_replaces('bar', 'FOO:strtolower');
		$this->assert_replaces('Bar', 'FOO:strtolower:ucfirst');
	}
	
	/**
	 * @depends test_replace_variable_helper_functions
	 */
	function test_replace_variable_constant() {
		define('FOOCONST', 'foobar');
		$this->assert_replaces('foobar', 'FOOCONST');
		$this->assert_replaces('FOOBAR', 'FOOCONST:strtoupper');
	}
	
	/**
	 * @expectedException UnexpectedValueException
	 * @expectedExceptionMessage Helper function "idonotexist" is not callable.
	 */
	function test_replace_variable_non_callable_helper_function() {
		$this->assert_replaces(null, 'foo:idonotexist');
	}
	
	function test_replace_variable_not_found() {
		$this->assert_replaces('{idonotexist}', 'idonotexist');
	}
	
	function test_replace_variable_associative_array() {
		$this->assert_replaces('bar', 'array.foo');
		$this->assert_replaces('baz', 'array.bar');
	}
	
	function test_replace_variable_object_property() {
		$this->assert_replaces('bar', 'object.foo');
		$this->assert_replaces('baz', 'object.bar');
	}
	
	function test_replace_variable_if_statement() {
		$this->assert_replaces('bar', 'if:true:foo');
		$this->assert_replaces('', 'if:false:foo');
		$this->assert_replaces('bar', 'if:true:foo:else:bar');
		$this->assert_replaces('baz', 'if:false:foo:else:bar');
		$this->assert_replaces('Bar', 'if:false:foo:else:FOO:strtolower:ucfirst');
	}
	
	/*function assert_block_renders($expected_file, $block, $data) {
		$rm = new ReflectionMethod('WebBasics\Template', 'render_block');
		$rm->setAccessible(true);
		$expected_file = "tests/_files/rendered/$expected_file.html";
		$this->assertStringEqualsFile($expected_file, $rm->invoke(null, $block, $data));
	}*/
	
	function assert_renders($expected_file, $tpl) {
		$expected_file = "tests/_files/rendered/$expected_file.html";
		$this->assertStringEqualsFile($expected_file, $tpl->render());
	}
	
	function test_render_simple() {
		$this->assertEquals('test', $this->tpl->render());
	}
	
	/**
	 * @depends test_replace_variable_helper_functions
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