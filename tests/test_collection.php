<?php

require_once 'collection.php';
use webbasics\Collection;

class IdObject {
	static $count = 0;
	
	function __construct($foo=null) {
		$this->id = ++self::$count;
		$this->foo = $foo;
	}
	
	function getId() {
		return $this->id;
	}
	
	static function clearCounter() {
		self::$count = 0;
	}
}

function set($items=array()) {
	return new Collection($items);
}


function std_object(array $properties) {
	$object = new stdClass();
	
	foreach( $properties as $property => $value )
		$object->{$property} = $value;
	
	return $object;
}

class CollectionTest extends PHPUnit_Framework_TestCase {
	function setUp() {
		$this->set = set(array(1, 2));
	}
	
	function testAdd() {
		$this->set->add(3);
		$this->assertEquals($this->set, set(array(1, 2, 3)));
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	function testInsertError() {
		set(array('foo' => 1))->insert(2, 'foo');
	}
	
	function testInsertSuccess() {
		$this->set->insert(4, 1);
		$this->assertEquals($this->set, set(array(1, 4, 2)));
		$this->set->insert(5, 0);
		$this->assertEquals($this->set, set(array(5, 1, 4, 2)));
	}
	
	function testAll() {
		$this->assertEquals(set()->all(), array());
		$this->assertEquals(set(array())->all(), array());
		$this->assertEquals(set(array(1))->all(), array(1));
		$this->assertEquals(set(array(1, 2))->all(), array(1, 2));
	}
	
	/**
	 * @expectedException OutOfBoundsException
	 */
	function testLastEmpty() {
		set()->last();
	}
	
	function testLast() {
		$this->assertEquals($this->set->last(), 2);
	}
	
	/**
	 * @expectedException OutOfBoundsException
	 */
	function testFirstEmpty() {
		set()->first();
	}
	
	function testFirst() {
		$this->assertEquals($this->set->first(), 1);
	}
	
	function testCount() {
		$this->assertEquals(set()->count(), 0);
		$this->assertEquals(set(array())->count(), 0);
		$this->assertEquals(set(array(1))->count(), 1);
		$this->assertEquals(set(array(1, 2))->count(), 2);
	}
	
	function testIndexExists() {
		$this->assertTrue($this->set->indexExists(1));
		$this->assertTrue(set(array('foo' => 'bar'))->indexExists('foo'));
	}
	
	function testGet() {
		$this->assertEquals($this->set->get(0), 1);
		$this->assertEquals($this->set->get(1), 2);
		$this->assertEquals(set(array('foo' => 'bar'))->get('foo'), 'bar');
	}
	
	function testDeleteIndex() {
		$this->set->deleteIndex(0);
		$this->assertEquals($this->set, set(array(1 => 2)));
	}
	
	function testDelete() {
		$this->set->delete(1);
		$this->assertEquals($this->set, set(array(1 => 2)));
	}
	
	function assertSetEquals(array $expected_items, $set) {
		$this->assertAttributeEquals($expected_items, 'items', $set);
	}
	
	function testUniques() {
		$this->assertSetEquals(array(1, 2), set(array(1, 2, 2))->uniques());
		$this->assertSetEquals(array(2, 1), set(array(2, 1, 2))->uniques());
		$this->assertSetEquals(array(2, 1), set(array(2, 2, 1))->uniques());
	}
	
	function setItems($collection, $items, $clone) {
		$rm = new ReflectionMethod($collection, 'setItems');
		$rm->setAccessible(true);
		return $rm->invoke($collection, $items, $clone);
	}
	
	function testSetItemsClone() {
		$result = $this->setItems($this->set, array(3, 4), true);
		$this->assertSetEquals(array(1, 2), $this->set);
		$this->assertSetEquals(array(3, 4), $result);
		$this->assertNotSame($this->set, $result);
	}
	
	function testSetItemsNoClone() {
		$result = $this->setItems($this->set, array(3, 4), false);
		$this->assertSame($this->set, $result);
	}
	
	/**
	 * @depends testSetItemsClone
	 */
	function testFilter() {
		$smaller_than_five = function($number) { return $number < 5; };
		$this->assertSetEquals(array(2, 4, 1, 4), set(array(2, 7, 4, 7, 1, 8, 4, 5))->filter($smaller_than_five));
	}
	
	/**
	 * @depends testFilter
	 */
	function testFindSuccess() {
		$items = array(
			array('foo' => 'bar', 'bar' => 'baz'),
			array('foo' => 'baz', 'bar' => 'foo'),
			std_object(array('foo' => 'bar', 'baz' => 'bar')),
		);
		$this->assertSetEquals(array($items[1]), set($items)->find(array('foo' => 'baz')));
		$this->assertSetEquals(array($items[0], $items[2]), set($items)->find(array('foo' => 'bar')));
	}
	
	/**
	 * @depends testFindSuccess
	 * @expectedException \UnexpectedValueException
	 * @expectedExceptionMessage Collection::find encountered a non-object and non-array item "foobar".
	 */
	function testFindFailure() {
		$items = array(
			array('foo' => 'bar', 'bar' => 'baz'),
			'foobar',
		);
		set($items)->find(array('foo' => 'bar'));
	}
	
	function testGetAttributeSimple() {
		IdObject::clearCounter();
		$set = set(array(new IdObject(), new IdObject(), new IdObject()));
		$this->assertEquals(array(1, 2, 3), $set->getAttribute('id'));
	}
	
	/**
	 * @depends testGetAttributeSimple
	 */
	function testGetAttributeIndices() {
		IdObject::clearCounter();
		$set = set(array('foo' => new IdObject(), 'bar' => new IdObject(), 'baz' => new IdObject()));
		$this->assertEquals(array('foo' => 1, 'bar' => 2, 'baz' => 3), $set->getAttribute('id'));
	}
	
	/**
	 * @depends testAll
	 * @depends testSetItemsClone
	 */
	function testIndexBy() {
		IdObject::clearCounter();
		$set = set(array(new IdObject('foo'), new IdObject('bar'), new IdObject('baz')));
		list($foo, $bar, $baz) = $set->all();
		$this->assertSetEquals(array('foo' => $foo, 'bar' => $bar, 'baz' => $baz), $set->indexBy('foo'));
	}
	
	/**
	 * @depends testSetItemsClone
	 */
	function testMap() {
		$plus_five = function($number) { return $number + 5; };
		$this->assertSetEquals(array(6, 7, 8), set(array(1, 2, 3))->map($plus_five));
	}
	
	/**
	 * @depends testSetItemsClone
	 */
	function testMapMethod() {
		IdObject::clearCounter();
		$set = set(array(new IdObject(), new IdObject(), new IdObject()));
		$this->assertSetEquals(array(1, 2, 3), $set->mapMethod('getId'));
	}
}

?>