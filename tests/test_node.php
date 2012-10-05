<?php

require_once 'node.php';
use \webbasics\Node;

class NodeTest extends PHPUnit_Framework_TestCase {
	var $autoloader;
	
	function setUp() {
		$this->root = new Node('test node');
	}
	
	function test_get_id() {
		$this->assertEquals($this->root->get_id(), 1);
		$this->assertEquals(Node::create('')->get_id(), 2);
	}
	
	function test_get_name() {
		$this->assertEquals($this->root->get_name(), 'test node');
		$this->assertEquals(Node::create('second node')->get_name(), 'second node');
	}
	
	function test_get_parent() {
		$this->assertNull($this->root->get_parent());
		$this->assertSame(Node::create('', $this->root)->get_parent(), $this->root);
	}
	
	function test_is() {
		$mirror = $this->root;
		$this->assertTrue($mirror->is($this->root));
		$this->assertFalse(Node::create('')->is($this->root));
	}
	
	function test_is_root() {
		$this->assertTrue($this->root->is_root());
		$this->assertFalse(Node::create('', $this->root)->is_root());
	}
	
	function test_add_child() {
		$node = new Node('');
		$this->root->add_child($node);
		$this->assertAttributeEquals(array($node), 'children', $this->root);
		$this->assertSame($node->get_parent(), $this->root);
	}
	
	/**
	 * @depends test_add_child
	 */
	function test_get_children() {
		$this->assertEquals($this->root->get_children(), array());
		$node = new Node('');
		$this->root->add_child($node);
		$this->assertSame($this->root->get_children(), array($node));
	}
	
	function test_add_child_no_set_parent() {
		$node = new Node('');
		$this->root->add_child($node, false);
		$this->assertAttributeEquals(array($node), 'children', $this->root);
		$this->assertNull($node->get_parent());
	}
	
	/**
	 * @depends test_add_child
	 */
	function test_is_leaf() {
		$node = new Node('');
		$this->root->add_child($node);
		$this->assertTrue($node->is_leaf());
		$this->assertFalse($this->root->is_leaf());
	}
	
	/**
	 * @depends test_add_child
	 */
	function test_add() {
		$node = $this->root->add('name', array('foo' => 'bar'));
		$this->assertEquals($node->get_name(), 'name');
		$this->assertEquals($node->get('foo'), 'bar');
		$this->assertSame($node->get_parent(), $this->root);
	}
	
	/**
	 * @depends test_add
	 */
	function test_remove_child() {
		$node1 = $this->root->add('name', array('foo' => 'bar'));
		$node2 = $this->root->add('name', array('foo' => 'bar'));
		$this->root->remove_child($node2);
		$this->assertAttributeSame(array($node1), 'children', $this->root);
	}
	
	/**
	 * @depends test_remove_child
	 */
	function test_remove_leaf() {
		$node1 = $this->root->add('name', array('foo' => 'bar'));
		$node2 = $this->root->add('name', array('foo' => 'bar'));
		$node1->remove();
		$this->assertAttributeSame(array($node2), 'children', $this->root);
	}
	
	/**
	 * @depends test_remove_leaf
	 */
	function test_remove_node() {
		$node = $this->root->add('node');
		$leaf = $node->add('leaf');
		$node->remove();
		$this->assertAttributeEquals(array(), 'children', $this->root);
		$this->assertNull($leaf->get_parent());
	}
	
	/**
	 * @depends test_remove_child
	 * @expectedException \RuntimeException
	 */
	function test_remove_root() {
		$node1 = $this->root->add('name', array('foo' => 'bar'));
		$node2 = $this->root->add('name', array('foo' => 'bar'));
		$this->root->remove();
		$this->assertAttributeSame(array($node2), 'children', $this->root);
	}
	
	function test_set_single() {
		$this->root->set('foo', 'bar');
		$this->assertAttributeEquals(array('foo' => 'bar'), 'variables', $this->root);
		$this->root->set('bar', 'baz');
		$this->assertAttributeEquals(array('foo' => 'bar', 'bar' => 'baz'), 'variables', $this->root);
	}
	
	function test_set_return() {
		$this->assertSame($this->root->set('foo', 'bar'), $this->root);
	}
	
	function test_set_multiple() {
		$this->root->set(array('foo' => 'bar'));
		$this->assertAttributeEquals(array('foo' => 'bar'), 'variables', $this->root);
		$this->root->set(array('bar' => 'baz'));
		$this->assertAttributeEquals(array('foo' => 'bar', 'bar' => 'baz'), 'variables', $this->root);
	}
	
	/**
	 * @depends test_set_single
	 */
	function test___set() {
		$this->root->foo = 'bar';
		$this->assertAttributeEquals(array('foo' => 'bar'), 'variables', $this->root);
		$this->root->bar = 'baz';
		$this->assertAttributeEquals(array('foo' => 'bar', 'bar' => 'baz'), 'variables', $this->root);
	}
	
	/**
	 * @depends test_set_multiple
	 */
	function test_get_direct() {
		$this->root->set(array('foo' => 'bar', 'bar' => 'baz'));
		$this->assertEquals($this->root->get('foo'), 'bar');
		$this->assertEquals($this->root->get('bar'), 'baz');
	}
	
	/**
	 * @depends test_get_direct
	 */
	function test___get() {
		$this->root->set(array('foo' => 'bar', 'bar' => 'baz'));
		$this->assertEquals($this->root->foo, 'bar');
		$this->assertEquals($this->root->bar, 'baz');
	}
	
	/**
	 * @depends test_set_single
	 */
	function test_get_ancestor() {
		$this->root->set('foo', 'bar');
		$node = $this->root->add('');
		$this->assertEquals($node->get('foo'), 'bar');
	}
	
	function test_get_failure() {
		$this->assertNull($this->root->get('foo'));
	}
	
	/**
	 * @depends test_get_name
	 */
	function test_find() {
		$node1 = $this->root->add('foo');
		$node2 = $this->root->add('bar');
		$node3 = $this->root->add('foo');
		$this->assertSame($this->root->find('foo'), array($node1, $node3));
	}
	
	/**
	 * @depends test_set_multiple
	 */
	function test_copy_simple() {
		$copy = $this->root->copy();
		$this->assertEquals($this->root, $copy);
		$this->assertNotSame($this->root, $copy);
	}
	
	/**
	 * @depends test_copy_simple
	 */
	function test_copy_shallow() {
		$child = $this->root->add('');
		$copy = $this->root->copy();
		$this->assertAttributeSame(array($child), 'children', $copy);
	}
	
	/**
	 * @depends test_get_children
	 * @depends test_copy_simple
	 */
	function test_copy_deep() {
		$child = $this->root->add('foo');
		$copy = $this->root->copy(true);
		$copy_children = $copy->get_children();
		$child_copy = reset($copy_children);
		$this->assertNotSame($copy_children, $this->root->get_children());
		$this->assertSame($child_copy->get_parent(), $copy);
	}
}

?>