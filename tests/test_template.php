<?php

require_once 'template.php';
use webbasics\Template;
use webbasics\Node;

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
	const INTERNATIONALIZATION_STRING = 'Iñtërnâtiônàlizætiøn';
	
	/**
	 * @depends testAddRootSuccess
	 */
	function setUp() {
		Template::setRoot(TEMPLATES_DIR);
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
			'html' => '<script></script>',
			'internationalization' => self::INTERNATIONALIZATION_STRING,
		));
	}
	
	/**
	 * @expectedException webbasics\FileNotFoundError
	 * @expectedExceptionMessage Directory "non_existing_folder/" does not exist.
	 */
	function testAddRootFailure() {
		Template::addRoot('non_existing_folder');
	}
	
	function assertIncludePathEquals($expected) {
		$include_path = new ReflectionProperty('webbasics\Template', 'include_path');
		$include_path->setAccessible(true);
		$this->assertEquals($expected, $include_path->getValue());
	}
	
	function testClearIncludePath() {
		Template::clearIncludePath();
		$this->assertIncludePathEquals(array());
	}
	
	/**
	 * @depends testClearIncludePath
	 */
	function testAddRootSuccess() {
		Template::clearIncludePath();
		Template::addRoot(TEMPLATES_DIR);
		$this->assertIncludePathEquals(array(TEMPLATES_DIR));
		Template::addRoot('tests/_files');
		$this->assertIncludePathEquals(array(TEMPLATES_DIR, 'tests/_files/'));
	}
	
	/**
	 * @depends testAddRootSuccess
	 */
	function testSetRoot() {
		Template::clearIncludePath();
		Template::addRoot(TEMPLATES_DIR);
		Template::addRoot('tests/_files');
		Template::setRoot(TEMPLATES_DIR);
		$this->assertIncludePathEquals(array(TEMPLATES_DIR));
	}
	
	/**
	 * @expectedException webbasics\FormattedException
	 */
	function testNonExistingTemplate() {
		$bar = new Template('bar');
	}
	
	function testOtherRoot() {
		Template::addRoot('tests/_files/other_templates');
		new Template('bar');
	}
	
	function testGetPath() {
		$this->assertEquals(TEMPLATES_DIR.'foo.tpl', $this->tpl->getPath());
	}
	
	function getProperty($object, $property_name) {
		$rp = new ReflectionProperty($object, $property_name);
		$rp->setAccessible(true);
		return $rp->getValue($object);
	}
	
	static function stripNewlines($html) {
		return str_replace("\r\n", "\n", $html);
	}
	
	function assertIsHtmlNode($node, $content) {
		$this->assertEquals('html', $node->getName());
		$this->assertEquals($content, self::stripNewlines($node->get('content')));
		$this->assertEquals(array(), $node->getChildren());
	}
	
	function assertIsBlockNode($node, $block_name, $child_count) {
		$this->assertEquals('block', $node->getName());
		$this->assertSame($block_name, $node->get('name'));
		$this->assertNull($node->get('content'));
		$this->assertEquals($child_count, count($node->getChildren()));
	}
	
	function assertIsExpNode($node, $brackets_content) {
		$this->assertEquals('expression', $node->getName());
		$this->assertEquals($brackets_content, $node->get('content'));
		$this->assertEquals(array(), $node->getChildren());
	}
	
	function testParseBlocksSimple() {
		$root_block = $this->getProperty($this->tpl, 'root_block');
		$this->assertIsBlockNode($root_block, null, 1);
		
		list($child) = $root_block->getChildren();
		$this->assertIsHtmlNode($child, 'test');
	}
	
	/**
	 * @depends testParseBlocksSimple
	 */
	function testParseBlocksBlocks() {
		$tpl = new Template('blocks');
		$root_block = $this->getProperty($tpl, 'root_block');
		$this->assertIsBlockNode($root_block, null, 2);
		
		list($before, $foo) = $root_block->getChildren();
		$this->assertIsHtmlNode($before, '');
		$this->assertIsBlockNode($foo, 'foo', 3);
		
		list($foofoo, $bar, $foobaz) = $foo->getChildren();
		$this->assertIsHtmlNode($foofoo, "\nfoofoo\n\t");
		$this->assertIsBlockNode($bar, 'bar', 1);
		$this->assertIsHtmlNode($foobaz, "\nfoobaz\n");
		
		list($foobar) = $bar->getChildren();
		$this->assertIsHtmlNode($foobar, "\n\tfoobar\n\t");
	}
	
	/**
	 * @depends testParseBlocksBlocks
	 * @expectedException webbasics\ParseError
	 * @expectedExceptionMessage Parse error in file tests/_files/templates/unexpected_end.tpl, line 5: unexpected {end}
	 */
	function testParseBlocksUnexpectedEnd() {
		new Template('unexpected_end');
	}
	
	/**
	 * @depends testParseBlocksBlocks
	 * @expectedException webbasics\ParseError
	 * @expectedExceptionMessage Parse error in file tests/_files/templates/missing_end.tpl, line 6: missing {end}
	 */
	function testParseBlocksMissingEnd() {
		new Template('missing_end');
	}
	
	/**
	 * @depends testParseBlocksSimple
	 */
	function testParseBlocksVariables() {
		$tpl = new Template('variables');
		$root_block = $this->getProperty($tpl, 'root_block');
		$this->assertIsBlockNode($root_block, null, 5);
		
		list($foo, $foobar, $bar, $foobaz, $baz) = $root_block->getChildren();
		$this->assertIsHtmlNode($foo, "foo\n");
		$this->assertIsExpNode($foobar, '$foobar');
		$this->assertIsHtmlNode($bar, "\nbar\n");
		$this->assertIsExpNode($foobaz, 'strtolower($foobaz)');
		$this->assertIsHtmlNode($baz, "\nbaz\n{\nno_variable\n}");
	}
	
	/**
	 * @depends testParseBlocksBlocks
	 * @depends testParseBlocksVariables
	 */
	function testParseBlocksFull() {
		$tpl = new Template('full');
		$root_block = $this->getProperty($tpl, 'root_block');
		$this->assertIsBlockNode($root_block, null, 3);
		
		list($bar, $foo, $baz) = $root_block->getChildren();
		$this->assertIsHtmlNode($bar, "bar\n");
		$this->assertIsBlockNode($foo, 'foo', 5);
		$this->assertIsHtmlNode($baz, "\nbaz");
		
		list($foofoo, $bar, $first_space, $foobaz, $second_space) = $foo->getChildren();
		$this->assertIsHtmlNode($foofoo, "\nfoofoo\n\t");
		$this->assertIsBlockNode($bar, 'bar', 3);
		$this->assertIsHtmlNode($first_space, "\n");
		$this->assertIsExpNode($foobaz, 'strtolower($foobaz)');
		$this->assertIsHtmlNode($second_space, "\n");
		
		list($space_before, $foobar, $space_after) = $bar->getChildren();
		$this->assertIsHtmlNode($space_before, "\n\t");
		$this->assertIsExpNode($foobar, '$foobar');
		$this->assertIsHtmlNode($space_after, "\n\t");
	}
	
	function evaluateExpression() {
		$args = func_get_args();
		$eval = new ReflectionMethod('webbasics\Template', 'evaluateExpression');
		$eval->setAccessible(true);
		return $eval->invokeArgs(null, $args);
	}
	
	function assertEvaluates($expected, $expression) {
		$this->assertEquals($expected, $this->evaluateExpression($expression, $this->data));
	}
	
	/** 
	 * @expectedException \UnexpectedValueException
	 */
	function testEvaluateVariableAttributeNull() {
		$this->evaluateExpression('$foobarbaz.foo', $this->data);
	}
	
	/** 
	 * @expectedException \UnexpectedValueException
	 */
	function testEvaluateVariableAttributeNoSuchAttribute() {
		$this->evaluateExpression('$object.foobar', $this->data);
	}
	
	/** 
	 * @expectedException \UnexpectedValueException
	 */
	function testEvaluateVariableAttributeNoArrayOrObject() {
		$this->evaluateExpression('$foo.bar', $this->data);
	}
	
	/** 
	 * @expectedException \UnexpectedValueException
	 */
	function testEvaluateVariableMethodNull() {
		$this->evaluateExpression('$foobarbaz.foo()', $this->data);
	}
	
	/** 
	 * @expectedException \BadMethodCallException
	 */
	function testEvaluateVariableMethodNoSuchMethod() {
		$this->evaluateExpression('$object.foo()', $this->data);
	}
	
	/** 
	 * @expectedException \BadMethodCallException
	 */
	function testEvaluateVariableMethodNoObject() {
		$this->evaluateExpression('$foo.bar()', $this->data);
	}
	
	function testEvaluateVariableSuccess() {
		$this->assertEvaluates('bar', '$array.foo');
		$this->assertEvaluates('bar', '$foo');
		$this->assertEvaluates('baz', '$bar');
		$this->assertEvaluates('bar', '$object.foo');
		$this->assertEvaluates('baz', '$object.bar');
		$this->assertEvaluates('foobar', '$object.baz()');
	}
	
	/** 
	 * @depends testEvaluateVariableSuccess
	 */
	function testEvaluateVariableEscape() {
		$this->assertEvaluates('&lt;script&gt;&lt;/script&gt;', '$html');
		$this->assertEvaluates('Iñtërnâtiônàlizætiøn', '$internationalization');
		//$this->assertEvaluates('I&ntilde;t&euml;rn&acirc;ti&ocirc;n&agrave;liz&aelig;ti&oslash;n', '$internationalization');
	}
	
	/** 
	 * @depends testEvaluateVariableSuccess
	 */
	function testEvaluateVariableNoescape() {
		$this->assertEvaluates('<script></script>', '$$html');
		$this->assertEvaluates('Iñtërnâtiônàlizætiøn', '$$internationalization');
	}
	
	function testEvaluateConstant() {
		$this->assertEvaluates('foobar_const', 'FOOBAR');
		$this->assertEvaluates('{NON_DEFINED_CONST}', 'NON_DEFINED_CONST');
	}
	
	function testEvaluateNoExpression() {
		$this->assertEvaluates('{foo}', 'foo');
	}
	
	function testEvaluateConditionIf() {
		$this->assertEvaluates('bar', '$true?bar');
		$this->assertEvaluates('', '$false?bar');
	}
	
	function testEvaluateConditionIfElse() {
		$this->assertEvaluates('bar', '$true?bar:baz');
		$this->assertEvaluates('baz', '$false?bar:baz');
	}
	
	/**
	 * @depends testEvaluateConditionIf
	 * @depends testEvaluateConditionIfElse
	 */
	function testEvaluateConditionExtended() {
		$this->assertEvaluates(' bar ', '$true? bar : baz');
		$this->assertEvaluates(' baz', '$false? bar : baz');
		
		$this->assertEvaluates(' bar ', '$true ? bar : baz');
		$this->assertEvaluates(' baz', '$false ? bar : baz');
		
		$this->assertEvaluates(' Foo bar ', '$true ? Foo bar : Baz foo');
		$this->assertEvaluates(' Baz foo', '$false ? Foo bar : Baz foo');
		
		$this->assertEvaluates('| bar', '$true ?| $foo');
	}
	
	/** 
	 * @expectedException \BadFunctionCallException
	 */
	function testEvaluateFunctionError() {
		$this->evaluateExpression('undefined_function($foo)', $this->data);
	}
	
	function testEvaluateFunctionSuccess() {
		$this->assertEvaluates('Bar', 'ucfirst($foo)');
		$this->assertEvaluates('Bar', 'DataObject::foobar($foo)');
	}
	
	/**
	 * @depends testEvaluateFunctionSuccess
	 */
	function testEvaluateFunctionNested() {
		$this->assertEvaluates('Bar', 'ucfirst(strtolower($FOO))');
	}
	
	function testEvaluateDefaultValue() {
		$this->assertEvaluates('bar', '$foo||fallback');
		$this->assertEvaluates('fallback', '$foo.bar||fallback');
		$this->assertEvaluates('', '$foo.bar||');
	}
	
	/**
	 * @depends testEvaluateVariableSuccess
	 * @depends testEvaluateNoExpression
	 * @depends testEvaluateConditionExtended
	 * @depends testEvaluateFunctionSuccess
	 * @depends testEvaluateDefaultValue
	 */
	function testEvaluateExpressionCombined() {
		$this->assertEvaluates('Bar', '$true?ucfirst($foo)');
		$this->assertEvaluates('', '$false?ucfirst($foo)');
		$this->assertEvaluates('Bar', '$true?ucfirst($foo):baz');
		$this->assertEvaluates('baz', '$false?ucfirst($foo):baz');
		$this->assertEvaluates('Baz', 'ucfirst($array.bar)');
	}
	
	function assertRenders($expected_file, $tpl) {
		$expected_file = "tests/_files/rendered/$expected_file.html";
		$this->assertEquals(self::stripNewlines(file_get_contents($expected_file)),
		                    self::stripNewlines($tpl->render()));
	}
	
	function testRenderSimple() {
		$this->assertEquals('test', $this->tpl->render());
	}
	
	/**
	 * @depends testEvaluateExpressionCombined
	 */
	function testRenderVariable() {
		$tpl = new Template('variables');
		$tpl->set(array(
			'foobar' => 'my_foobar_variable',
			'foobaz' => 'MY_FOOBAZ_VARIABLE'
		));
		$this->assertRenders('variables', $tpl);
	}
	
	/**
	 * @depends testRenderSimple
	 */
	function testRenderBlocks() {
		$tpl = new Template('blocks');
		
		$foo = $tpl->add('foo');
		$foo->add('bar');
		$foo->add('bar');
		$tpl->add('foo');
		
		$this->assertRenders('blocks', $tpl);
	}
	
	/**
	 * @depends testRenderVariable
	 * @depends testRenderBlocks
	 */
	function testRenderFull() {
		$tpl = new Template('full');
		$first_foo = $tpl->add('foo')->set('foobaz', 'FIRST_FOOBAZ_VAR');
		$first_foo->add('bar')->set('foobar', 'first_foobar_var');
		$second_foo = $tpl->add('foo')->set('foobaz', 'SECOND_FOOBAZ_VAR');
		$second_foo->add('bar')->set('foobar', 'second_foobar_var');
		$second_foo->add('bar')->set('foobar', 'third_foobar_var');
		$this->assertRenders('full', $tpl);
	}
}

?>