<?php
/**
 * Collection functions, mainly used to manipulate lists of PHPActiveRecord models.
 * 
 * @author Taddeus Kroes
 * @version 1.0
 * @date 13-07-2012
 */

namespace WebBasics;

require_once 'base.php';

/**
 * The Collection class contains a number of functions sort, index and manipulate
 * an array of items.
 * 
 * Example 1: Index a list based on its values and extract a single attribute.
 * <code>
 * class Book extends PHPActiveRecord\Model {
 *     static $attr_accessible = array('id', 'name');
 * }
 * 
 * // Index all book names by their id.
 * $books = Book::all();                      // Find a list of books
 * $collection = new Collection($books);      // Put the list in a collection
 * $indexed = $collection->index_by('id');    // Create indexes for all books
 * $names = $indexed->get_attribute('name');  // Get the values of a single attribute
 * // $names now contains something like array(1 => 'Some book', 2 => 'Another book')
 * 
 * // Same as above:
 * $names = Collection::create(Book::all())->index_by('id')->get_attribute('name');
 * </code>
 * 
 * Example 2: Execute a method for each item in a list.
 * <code>
 * // Delete all books
 * Collection::create(Book::all())->map_method('delete');
 * </code>
 * 
 * @package WebBasics
 * @todo Finish unit tests
 */
class Collection extends Base {
	/**
	 * The set of items item that is being manipulated, as an associoative array.
	 * 
	 * @var array
	 */
	private $items;
	
	/**
	 * Create a new Collection object.
	 * 
	 * @param array $items Initial item set (optional).
	 */
	function __construct(array $items=array()) {
		$this->items = $items;
	}
	
	/**
	 * Add an item to the collection.
	 * 
	 * @param mixed $item The item to add.
	 */
	function add($item) {
		$this->items[] = $item;
	}
	
	/**
	 * Insert an item at a specific index in the collection.
	 * 
	 * If the index is numeric, all existing numeric indices from that
	 * index will be shifted one position to the right.
	 * 
	 * @param mixed $item The item to insert.
	 * @param int|string $index The index at which to insert the item.
	 * @throws \InvalidArgumentException If the added index already exists, and is non-numeric.
	 */
	function insert($item, $index) {
		if( isset($this->items[$index]) ) {
			if( !is_int($index) )
				throw new \InvalidArgumentException(sprintf('Index "%s" already exists in this collection.', $index));
			
			for( $i = count($this->items) - 1; $i >= $index; $i--)
				$this->items[$i + 1] = $this->items[$i];
		}
		
		$this->items[$index] = $item;
	}
	
	/**
	 * Get all items in the collection as an array.
	 * 
	 * @return array The items in the collection.
	 */
	function all() {
		return $this->items;
	}
	
	/**
	 * Get the first item in the collection.
	 * 
	 * @return mixed
	 * @throws \OutOfBoundsException if the collection is empty.
	 */
	function first() {
		if( !$this->count() )
			throw new \OutOfBoundsException(sprintf('Cannot get first item: collection is empty.'));
		
		return $this->items[0];
	}
	
	/**
	 * Get the last item in the collection.
	 * 
	 * @return mixed
	 * @throws \OutOfBoundsException if the collection is empty.
	 */
	function last() {
		if( !$this->count() )
			throw new \OutOfBoundsException(sprintf('Cannot get last item: collection is empty.'));
		
		return end($this->items);
	}
	
	/**
	 * Get the number of items in the collection.
	 * 
	 * @return int The number of items in the collection.
	 */
	function count() {
		return count($this->items);
	}
	
	/**
	 * Check if an item with the given index exists.
	 * 
	 * @param int|string $index The index to check existance of.
	 * @return bool Whether the index exists.
	 */
	function index_exists($index) {
		return isset($this->items[$index]);
	}
	
	/**
	 * Get an item from the collection by its index.
	 * 
	 * @param int|string $index The index to the item to get.
	 * @return mixed The value corresponding to the specified index.
	 */
	function get($index) {
		return $this->items[$index];
	}
	
	/**
	 * Delete an item from the collection by its index.
	 * 
	 * @param int|string $index The index to the item to delete.
	 */
	function delete_index($index) {
		unset($this->items[$index]);
	}
	
	/**
	 * Delete an item from the collection.
	 * 
	 * @param mixed $item The item to delete.
	 */
	function delete($item) {
		$this->delete_index(array_search($item, $this->items));
	}
	
	/**
	 * Create a new item set with the same class name as this object.
	 * 
	 * @param array $items The new items to create a set with.
	 * @param bool $clone If TRUE, the item set will overwrite the current
	 *                    object's item set and not create a new object.
	 * @return Collection A collection with the new item set.
	 */
	private function set_items(array $items, $clone=true) {
		if( $clone )
			return new self($items);
		
		$this->items = $items;
		
		return $this;
	}
	
	/**
	 * Remove duplicates from the current item set.
	 * 
	 * @param bool $clone Whether to create a new object, or overwrite the current item set.
	 * @return Collection A collection without duplicates.
	 */
	function uniques($clone=false) {
		return $this->set_items(array_values(array_unique($this->items)), $clone);
	}
	
	/**
	 * Filter items from the collection.
	 * 
	 * @param callable $callback Function that receives an item from the collection and
	 *                           returns TRUE if the item should be present in the
	 *                           resulting collection.
	 * @param bool $clone Whether to create a new object, or overwrite the current item set.
	 * @return Collection A collection with the filtered set of items.
	 */
	function filter($callback, $clone=true) {
		return $this->set_items(array_values(array_filter($this->items, $callback)), $clone);
	}
	
	/**
	 * Find a subset of items in the collection using property value conditions.
	 * 
	 * The conditions are specified as an associative array of property names
	 * pointing to values. Only items whose properties have these values will
	 * appear in the resulting collection. The items in the collection have to
	 * be objects or associative arrays, or an error will occur.
	 * 
	 * @param array $conditions The conditions that items in the subset should meet.
	 * @param bool $clone Whether to create a new object, or overwrite the current item set.
	 * @throws \UnexpectedValueException If a non-object and non-array value is encountered.
	 * @return Collection
	 */
	function find(array $conditions, $clone=true) {
		return $this->filter(function($item) use ($conditions) {
			if( is_object($item) ) {
				// Object, match property values
				foreach( $conditions as $property => $value )
					if( $item->{$property} != $value )
						return false;
			} elseif( is_array($item) ) {
				// Array item, match array values
				foreach( $conditions as $property => $value )
					if( $item[$property] != $value )
						return false;
			} else {
				// Other, incompatible type -> throw exception
				throw new \UnexpectedValueException(
					sprintf('Collection::find encountered a non-object and non-array item "%s".', $item)
				);
			}
			
			return true;
		}, $clone);
	}
	
	/**
	 * Get an attribute value for each of the items in the collection.
	 * 
	 * The items are assumed to be objects with the specified attribute.
	 * 
	 * @param string $attribute The name of the attribute to get the value of.
	 * @return array The original item keys, pointing to single attribute values.
	 */
	function get_attribute($attribute) {
		return array_map(function($item) use ($attribute) {
			return $item->{$attribute};
		}, $this->items);
	}
	
	/**
	 * Use an attribute of each item in the collection as an index to that item.
	 * 
	 * The items are assumed to be objects with the specified index attribute.
	 * 
	 * @param string $attribute The name of the attribute to use as index value.
	 * @param bool $clone Whether to create a new object, or overwrite the current item set.
	 * @return Collection A collection object with the values of the attribute used as indices.
	 */
	function index_by($attribute, $clone=true) {
		$indexed = array();
		
		foreach( $this->items as $item )
			$indexed[$item->$attribute] = $item;
		
		return $this->set_items($indexed, $clone);
	}
	
	/**
	 * Execute a callback for each of the items in the collection.
	 * 
	 * @param callable $callback Function that receives an item from the collection.
	 * @param bool $clone Whether to create a new object, or overwrite the current item set.
	 * @return Collection A collection with return values of the callback calls.
	 */
	function map($callback, $clone=true) {
		return $this->set_items(array_map($callback, $this->items), $clone);
	}
	
	/**
	 * Execute an object method for each item in the collection.
	 * 
	 * The items are assumed to be objects with the specified method.
	 * 
	 * @param string $method_name The name of the method to execute.
	 * @param array $args Any arguments to pass to the method.
	 * @param bool $clone Whether to create a new object, or overwrite the current item set.
	 * @return Collection A collection with return values of the method calls.
	 */
	function map_method($method_name, array $args=array(), $clone=true) {
		$items = array();
		
		foreach( $this->items as $item )
			$items[] = call_user_func_array(array($item, $method_name), $args);
		
		return $this->set_items($items);
	}
}

?>