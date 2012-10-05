<?php

require_once 'collection.php';
use webbasics\Collection;

class IdObject {
	static $count = 0;
	
	function __construct($foo=null) {
		$this->id = ++self::$count;
		$this->foo = $foo;
	}
	
	function get_id() {
		return $this->id;
	}
	
	static function clear_counter() {
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
	
	function test_add() {
		$this->set->add(3);
		$this->assertEquals($this->set, set(array(1, 2, 3)));
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	function test_insert_error() {
		set(array('foo' => 1))->insert(2, 'foo');
	}
	
	function test_insert_success() {
		$this->set->insert(4, 1);
		$this->assertEquals($this->set, set(array(1, 4, 2)));
		$this->set->insert(5, 0);
		$this->assertEquals($this->set, set(array(5, 1, 4, 2)));
	}
	
	function test_all() {
		$this->assertEquals(set()->all(), array());
		$this->assertEquals(set(array())->all(), array());
		$this->assertEquals(set(array(1))->all(), array(1));
		$this->assertEquals(set(array(1, 2))->all(), array(1, 2));
	}
	
	/**
	 * @expectedException OutOfBoundsException
	 */
	function test_last_empty() {
		set()->last();
	}
	
	function test_last() {
		$this->assertEquals($this->set->last(), 2);
	}
	
	/**
	 * @expectedException OutOfBoundsException
	 */
	function test_first_empty() {
		set()->first();
	}
	
	function test_first() {
		$this->assertEquals($this->set->first(), 1);
	}
	
	function test_count() {
		$this->assertEquals(set()->count(), 0);
		$this->assertEquals(set(array())->count(), 0);
		$this->assertEquals(set(array(1))->count(), 1);
		$this->assertEquals(set(array(1, 2))->count(), 2);
	}
	
	function test_index_exists() {
		$this->assertTrue($this->set->index_exists(1));
		$this->assertTrue(set(array('foo' => 'bar'))->index_exists('foo'));
	}
	
	function test_get() {
		$this->assertEquals($this->set->get(0), 1);
		$this->assertEquals($this->set->get(1), 2);
		$this->assertEquals(set(array('foo' => 'bar'))->get('foo'), 'bar');
	}
	
	function test_delete_index() {
		$this->set->delete_index(0);
		$this->assertEquals($this->set, set(array(1 => 2)));
	}
	
	function test_delete() {
		$this->set->delete(1);
		$this->assertEquals($this->set, set(array(1 => 2)));
	}
	
	function assert_set_equals(array $expected_items, $set) {
		$this->assertAttributeEquals($expected_items, 'items', $set);
	}
	
	function test_uniques() {
		$this->assert_set_equals(array(1, 2), set(array(1, 2, 2))->uniques());
		$this->assert_set_equals(array(2, 1), set(array(2, 1, 2))->uniques());
		$this->assert_set_equals(array(2, 1), set(array(2, 2, 1))->uniques());
	}
	
	function set_items($collection, $items, $clone) {
		$rm = new ReflectionMethod($collection, 'set_items');
		$rm->setAccessible(true);
		return $rm->invoke($collection, $items, $clone);
	}
	
	function test_set_items_clone() {
		$result = $this->set_items($this->set, array(3, 4), true);
		$this->assert_set_equals(array(1, 2), $this->set);
		$this->assert_set_equals(array(3, 4), $result);
		$this->assertNotSame($this->set, $result);
	}
	
	function test_set_items_no_clone() {
		$result = $this->set_items($this->set, array(3, 4), false);
		$this->assertSame($this->set, $result);
	}
	
	/**
	 * @depends test_set_items_clone
	 */
	function test_filter() {
		$smaller_than_five = function($number) { return $number < 5; };
		$this->assert_set_equals(array(2, 4, 1, 4), set(array(2, 7, 4, 7, 1, 8, 4, 5))->filter($smaller_than_five));
	}
	
	/**
	 * @depends test_filter
	 */
	function test_find_success() {
		$items = array(
			array('foo' => 'bar', 'bar' => 'baz'),
			array('foo' => 'baz', 'bar' => 'foo'),
			std_object(array('foo' => 'bar', 'baz' => 'bar')),
		);
		$this->assert_set_equals(array($items[1]), set($items)->find(array('foo' => 'baz')));
		$this->assert_set_equals(array($items[0], $items[2]), set($items)->find(array('foo' => 'bar')));
	}
	
	/**
	 * @depends test_find_success
	 * @expectedException \UnexpectedValueException
	 * @expectedExceptionMessage Collection::find encountered a non-object and non-array item "foobar".
	 */
	function test_find_failure() {
		$items = array(
			array('foo' => 'bar', 'bar' => 'baz'),
			'foobar',
		);
		set($items)->find(array('foo' => 'bar'));
	}
	
	function test_get_attribute_simple() {
		IdObject::clear_counter();
		$set = set(array(new IdObject(), new IdObject(), new IdObject()));
		$this->assertEquals(array(1, 2, 3), $set->get_attribute('id'));
	}
	
	/**
	 * @depends test_get_attribute_simple
	 */
	function test_get_attribute_indices() {
		IdObject::clear_counter();
		$set = set(array('foo' => new IdObject(), 'bar' => new IdObject(), 'baz' => new IdObject()));
		$this->assertEquals(array('foo' => 1, 'bar' => 2, 'baz' => 3), $set->get_attribute('id'));
	}
	
	/**
	 * @depends test_all
	 * @depends test_set_items_clone
	 */
	function test_index_by() {
		IdObject::clear_counter();
		$set = set(array(new IdObject('foo'), new IdObject('bar'), new IdObject('baz')));
		list($foo, $bar, $baz) = $set->all();
		$this->assert_set_equals(array('foo' => $foo, 'bar' => $bar, 'baz' => $baz), $set->index_by('foo'));
	}
	
	/**
	 * @depends test_set_items_clone
	 */
	function test_map() {
		$plus_five = function($number) { return $number + 5; };
		$this->assert_set_equals(array(6, 7, 8), set(array(1, 2, 3))->map($plus_five));
	}
	
	/**
	 * @depends test_set_items_clone
	 */
	function test_map_method() {
		IdObject::clear_counter();
		$set = set(array(new IdObject(), new IdObject(), new IdObject()));
		$this->assert_set_equals(array(1, 2, 3), $set->map_method('get_id'));
	}
}

?>