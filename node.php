<?php
/**
 * Tree data structure, used for rendering purposes.
 * 
 * @author Taddeus Kroes
 * @version 1.0
 * @date 13-07-2012
 */

namespace BasicWeb;

require_once 'base.php';

/**
 * Tree node.
 * 
 * Each tree node has a (non-unique) name, a list of variables, and zero or
 * more children.
 * 
 * @package BasicWeb
 */
class Node extends Base {
	/**
	 * The number of Node instances, used to create unique node id's.
	 * 
	 * @var int
	 */
	private static $count = 0;
	
	/**
	 * The unique id of this Bloc.
	 * 
	 * @var int
	 */
	private $id;
	
	/**
	 * The node's name.
	 * 
	 * @var string
	 */
	private $name;
	
	/**
	 * An optional parent node.
	 * 
	 * If NULL, this node is the root of the data tree.
	 * 
	 * @var Node
	 */
	private $parent_node;
	
	/**
	 * Child nodes.
	 * 
	 * @var array
	 */
	private $children = array();
	
	/**
	 * Variables in this node.
	 * 
	 * All variables in a node are also available in its descendants through
	 * {@link get()}.
	 * 
	 * @var array
	 */
	private $variables = array();
	
	/**
	 * Constructor.
	 * 
	 * The id of the node is determined by the node counter.
	 * 
	 * @param string $name The node's name.
	 * @param Node|null &$parent_node A parent node (optional).
	 * @param int|null $id An id to assign. If none is specified, a new unique
	 *                     id is generated.
	 * @uses $count
	 */
	function __construct($name='', Node &$parent_node=null, $id=null) {
		$this->id = $id ? $id : ++self::$count;
		$this->name = $name;
		$this->parent_node = $parent_node;
	}
	
	/**
	 * Get the node's unique id.
	 * 
	 * @return int The node's id.
	 */
	function get_id() {
		return $this->id;
	}
	
	/**
	 * Get the node's name.
	 * 
	 * @return string The node's name.
	 */
	function get_name() {
		return $this->name;
	}
	
	/**
	 * Get the node's parent.
	 * 
	 * @return Node|null The parent node if any, NULL otherwise.
	 */
	function get_parent() {
		return $this->parent_node;
	}
	
	/**
	 * Get the node's children.
	 * 
	 * @return array A list of child nodes.
	 */
	function get_children() {
		return $this->children;
	}
	
	/**
	 * Check if a node is the same instance or a copy of this node.
	 * 
	 * @param Node $node The node to compare this node to.
	 * @return bool Whether the nodes have the same unique id.
	 */
	function is(Node $node) {
		return $node->get_id() == $this->id;
	}
	
	/**
	 * Check if this node is the root node of the tree.
	 * 
	 * A node is the root node if it has no parent.
	 * 
	 * @return bool Whether this node is the root node.
	 */
	function is_root() {
		return $this->parent_node === null;
	}
	
	/**
	 * Check if this node is a leaf node of the tree.
	 * 
	 * A node is a leaf if it has no children.
	 * 
	 * @return bool Whether this node is a leaf node.
	 */
	function is_leaf() {
		return !count($this->children);
	}
	
	/**
	 * Add a child node.
	 * 
	 * @param Node &$node The child node to add.
	 * @param bool $set_parent Whether to set this node as the child's parent
	 *                         (defaults to TRUE).
	 */
	function add_child(Node &$node, $set_parent=true) {
		$this->children[] = $node;
		$set_parent && $node->set_parent($this);
	}
	
	/**
	 * Add a child node.
	 * 
	 * @param string $name The name of the node to add.
	 * @param array $data Data to set in the created node (optional).
	 * @return Node The created node.
	 */
	function add($name, array $data=array()) {
		$node = new self($name, $this);
		$this->add_child($node, false);
		
		return $node->set($data);
	}
	
	/**
	 * Remove a child node.
	 * 
	 * @param Node &$child The node to remove.
	 */
	function remove_child(Node &$child) {
		foreach( $this->children as $i => $node )
			$node->is($child) && array_splice($this->children, $i, 1);
	}
	
	/**
	 * Remove this node from its parent.
	 * 
	 * @throws \RuntimeException If the node has no parent.
	 * @return Node This node.
	 */
	function remove() {
		if( $this->is_root() )
			throw new \RuntimeException('Cannot remove the root node of a tree.');
		
		$this->parent_node->remove_child($this);
		
		foreach( $this->children as $child )
			$child->set_parent(null);
		
		return $this;
	}
	
	/**
	 * Set the node's parent.
	 * 
	 * Removes this node as child of the original parent, if a parent was
	 * already set.
	 * 
	 * @param Node|null $parent The parent node to set.
	 * @return Node This node.
	 */
	function set_parent($parent) {
		if( $this->parent_node !== null )
			$this->parent_node->remove_child($this);
		
		$this->parent_node = &$parent;
		
		return $this;
	}
	
	/**
	 * Set the value of one or more variables in the node.
	 * 
	 * @param string|array $name Either a single variable name, or a set of name/value pairs.
	 * @param mixed $value The value of a single variable to set.
	 * @return Node This node.
	 */
	function set($name, $value=null) {
		if( is_array($name) ) {
			foreach( $name as $var => $val )
				$this->variables[$var] = $val;
		} else {
			$this->variables[$name] = $value;
		}
		
		return $this;
	}
	
	/**
	 * Get the value of a variable.
	 * 
	 * @param string $name The name of the variable to get the value of.
	 * @return mixed The value of the variable if it exists, NULL otherwise.
	 */
	function get($name) {
		// Variable inside this node?
		if( isset($this->variables[$name]) )
			return $this->variables[$name];
		
		// Variable in one of ancestors?
		if( $this->parent_node !== null )
			return $this->parent_node->get($name);
		
		// All nodes up to the tree's root node do not contain the variable
		return null;
	}
	
	/**
	 * Set the value of a variable.
	 * 
	 * This method provides a shortcut for {@link set()}.
	 * 
	 * @param string $name The name of the variable to set the value of.
	 * @param mixed $value The value to set.
	 */
	function __set($name, $value) {
		$this->set($name, $value);
	}
	
	/**
	 * Get the value of a variable.
	 * 
	 * This method provides a shortcut for {@link get()}.
	 * 
	 * @param string $name The name of the variable to get the value of.
	 * @return mixed The value of the variable if it exists, NULL otherwise.
	 */
	function __get($name) {
		return $this->get($name);
	}
	
	/**
	 * Find all child nodes that have the specified name.
	 * 
	 * @param string $name The name of the nodes to find.
	 * @return array The positively matched nodes.
	 */
	function find($name) {
		$has_name = function($child) use ($name) {
			return $child->get_name() == $name;
		};
		
		return array_values(array_filter($this->children, $has_name));
	}
	
	/**
	 * Create a copy of this node.
	 * 
	 * The copy will have the same list of children and variables. In case of
	 * a 'deep copy', the list of children is also cloned recursively.
	 * 
	 * @param bool $deep Whether to create a deep copy.
	 * @return Node A copy of this node.
	 */
	function copy($deep=false) {
		$copy = new self($this->name, $this->parent_node, $this->id);
		$copy->set($this->variables);
		
		foreach( $this->children as $child ) {
			if( $deep ) {
				$child_copy = $child->copy(true);
				$copy->add_child($child_copy);
			} else {
				$copy->add_child($child, false);
			}
		}
		
		return $copy;
	}
}

?>