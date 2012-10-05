<?php

require_once 'node.php';
use \webbasics\Node;

class NodeTest extends PHPUnit_Framework_TestCase {
	var $autoloader;
	
	function setUp() {
		$this->root = new Node('test node');
	}
	
	function testGetId() {
		$this->assertEquals($this->root->getId(), 1);
		$this->assertEquals(Node::create('')->getId(), 2);
	}
	
	function testGetName() {
		$this->assertEquals($this->root->getName(), 'test node');
		$this->assertEquals(Node::create('second node')->getName(), 'second node');
	}
	
	function testGetParent() {
		$this->assertNull($this->root->getParent());
		$this->assertSame(Node::create('', $this->root)->getParent(), $this->root);
	}
	
	function testIs() {
		$mirror = $this->root;
		$this->assertTrue($mirror->is($this->root));
		$this->assertFalse(Node::create('')->is($this->root));
	}
	
	function testIsRoot() {
		$this->assertTrue($this->root->isRoot());
		$this->assertFalse(Node::create('', $this->root)->isRoot());
	}
	
	function testAddChild() {
		$node = new Node('');
		$this->root->addChild($node);
		$this->assertAttributeEquals(array($node), 'children', $this->root);
		$this->assertSame($node->getParent(), $this->root);
	}
	
	/**
	 * @depends testAddChild
	 */
	function testGetChildren() {
		$this->assertEquals($this->root->getChildren(), array());
		$node = new Node('');
		$this->root->addChild($node);
		$this->assertSame($this->root->getChildren(), array($node));
	}
	
	function testAddChildNoSetParent() {
		$node = new Node('');
		$this->root->addChild($node, false);
		$this->assertAttributeEquals(array($node), 'children', $this->root);
		$this->assertNull($node->getParent());
	}
	
	/**
	 * @depends testAddChild
	 */
	function testIsLeaf() {
		$node = new Node('');
		$this->root->addChild($node);
		$this->assertTrue($node->isLeaf());
		$this->assertFalse($this->root->isLeaf());
	}
	
	/**
	 * @depends testAddChild
	 */
	function testAdd() {
		$node = $this->root->add('name', array('foo' => 'bar'));
		$this->assertEquals($node->getName(), 'name');
		$this->assertEquals($node->get('foo'), 'bar');
		$this->assertSame($node->getParent(), $this->root);
	}
	
	/**
	 * @depends testAdd
	 */
	function testRemoveChild() {
		$node1 = $this->root->add('name', array('foo' => 'bar'));
		$node2 = $this->root->add('name', array('foo' => 'bar'));
		$this->root->removeChild($node2);
		$this->assertAttributeSame(array($node1), 'children', $this->root);
	}
	
	/**
	 * @depends testRemoveChild
	 */
	function testRemoveLeaf() {
		$node1 = $this->root->add('name', array('foo' => 'bar'));
		$node2 = $this->root->add('name', array('foo' => 'bar'));
		$node1->remove();
		$this->assertAttributeSame(array($node2), 'children', $this->root);
	}
	
	/**
	 * @depends testRemoveLeaf
	 */
	function testRemoveNode() {
		$node = $this->root->add('node');
		$leaf = $node->add('leaf');
		$node->remove();
		$this->assertAttributeEquals(array(), 'children', $this->root);
		$this->assertNull($leaf->getParent());
	}
	
	/**
	 * @depends testRemoveChild
	 * @expectedException \RuntimeException
	 */
	function testRemoveRoot() {
		$node1 = $this->root->add('name', array('foo' => 'bar'));
		$node2 = $this->root->add('name', array('foo' => 'bar'));
		$this->root->remove();
		$this->assertAttributeSame(array($node2), 'children', $this->root);
	}
	
	function testSetSingle() {
		$this->root->set('foo', 'bar');
		$this->assertAttributeEquals(array('foo' => 'bar'), 'variables', $this->root);
		$this->root->set('bar', 'baz');
		$this->assertAttributeEquals(array('foo' => 'bar', 'bar' => 'baz'), 'variables', $this->root);
	}
	
	function testSetReturn() {
		$this->assertSame($this->root->set('foo', 'bar'), $this->root);
	}
	
	function testSetMultiple() {
		$this->root->set(array('foo' => 'bar'));
		$this->assertAttributeEquals(array('foo' => 'bar'), 'variables', $this->root);
		$this->root->set(array('bar' => 'baz'));
		$this->assertAttributeEquals(array('foo' => 'bar', 'bar' => 'baz'), 'variables', $this->root);
	}
	
	/**
	 * @depends testSetSingle
	 */
	function test___set() {
		$this->root->foo = 'bar';
		$this->assertAttributeEquals(array('foo' => 'bar'), 'variables', $this->root);
		$this->root->bar = 'baz';
		$this->assertAttributeEquals(array('foo' => 'bar', 'bar' => 'baz'), 'variables', $this->root);
	}
	
	/**
	 * @depends testSetMultiple
	 */
	function testGetDirect() {
		$this->root->set(array('foo' => 'bar', 'bar' => 'baz'));
		$this->assertEquals($this->root->get('foo'), 'bar');
		$this->assertEquals($this->root->get('bar'), 'baz');
	}
	
	/**
	 * @depends testGetDirect
	 */
	function test___get() {
		$this->root->set(array('foo' => 'bar', 'bar' => 'baz'));
		$this->assertEquals($this->root->foo, 'bar');
		$this->assertEquals($this->root->bar, 'baz');
	}
	
	/**
	 * @depends testSetSingle
	 */
	function testGetAncestor() {
		$this->root->set('foo', 'bar');
		$node = $this->root->add('');
		$this->assertEquals($node->get('foo'), 'bar');
	}
	
	function testGetFailure() {
		$this->assertNull($this->root->get('foo'));
	}
	
	/**
	 * @depends testGetName
	 */
	function testFind() {
		$node1 = $this->root->add('foo');
		$node2 = $this->root->add('bar');
		$node3 = $this->root->add('foo');
		$this->assertSame($this->root->find('foo'), array($node1, $node3));
	}
	
	/**
	 * @depends testSetMultiple
	 */
	function testCopySimple() {
		$copy = $this->root->copy();
		$this->assertEquals($this->root, $copy);
		$this->assertNotSame($this->root, $copy);
	}
	
	/**
	 * @depends testCopySimple
	 */
	function testCopyShallow() {
		$child = $this->root->add('');
		$copy = $this->root->copy();
		$this->assertAttributeSame(array($child), 'children', $copy);
	}
	
	/**
	 * @depends testGetChildren
	 * @depends testCopySimple
	 */
	function testCopyDeep() {
		$child = $this->root->add('foo');
		$copy = $this->root->copy(true);
		$copy_children = $copy->getChildren();
		$child_copy = reset($copy_children);
		$this->assertNotSame($copy_children, $this->root->getChildren());
		$this->assertSame($child_copy->getParent(), $copy);
	}
}

?>