<?php
class Zend_Db_NestedSet_Node extends Zend_Db_Table_Row_Abstract
                             implements Zend_Db_TreeNodeInterface,
                                        RecursiveIterator,
                                        Countable
{

    const NODE_PATH_SEPARATOR = ':';
    
    /**
     * List of child nodes
     *
     * @var Zend_Db_Tree_NestedSet_NodeIterator
     */
	public $_children = null;

	/**
	 * The parent node
	 *
	 * @var Zend_Db_Tree_NodeInterface
	 */
	protected $_parent = null;
	
	/**
	 * Setup the node
	 *
	 * @todo Enable writing to the db from a node object
	 * @param array $config
	 */
	public function __construct(array $config = array())
	{
		parent::__construct($config);
		if(isset($config['children']) && is_array($config['children'])) {
            $this->addChildren($config['children']);
		} else {
		    $this->_children = array();
		}
		
		/**
		 * TODO Allow writing in a future version
		 */
		//$this->setReadOnly();
	}

	/**
	 * Set the parent node
	 *
	 * @param Zend_Db_TreeNodeInterface $node
	 */
	public function setParent(Zend_Db_TreeNodeInterface $node)
	{
	    $this->_parent = $node;
	}
	
	/**
	 * Get the parent node
	 *
	 * @return Zend_Db_Tree_NodeInterface
	 */
	public function getParent()
	{
	    return $this->_parent;
	}	

	/**
	 * Get's the table to which this node is associated with
	 *
	 * @return Zend_Db_Tree_NestedSet
	 * @see Zend_Db_Table_Row_Abstract#getTable()
	 */
	public function getTable()
	{
		return parent::getTable();
	}

	/**
	 * return whether or not this is a leaf node
	 *
	 * @return bool
	 */
    public function isLeaf()
    {
        $lft = $this->_table->getLeftKey();
        $rgt = $this->_table->getRightKey();
        return (($this->$rgt - $this->$lft) == 1) ? true : false;        
    }

    /**
     * Return whether or not this is a root node
     *
     * @return bool
     */
    public function isRoot()
    {
    	$lft = $this->_table->getLeftKey();
        return ($this->$lft == 1) ? true : false;
    }

    /**
     * Return whether or not this node has children
     *
     * @return bool
     */
    public function hasChildren()
    {
        return $this->count() > 0;
    }
    
    public function addChild($child)
    {
        if($child instanceof Zend_Db_TreeNodeInterface) {
            $child->setParent($this);
            $this->_children[] = $child;
         } else {
             throw new RuntimeException('Not instance of Zend_Db_Tree_NodeInterface');
         }
    }
    
    public function addChildren($children)
    {
        if(is_array($children)) {            
            // its an array, just attach each array member            
            foreach ($children as $child) {
                if($child instanceof Zend_Db_TreeNodeInterface) {
                    $this->addChild($child);
                    continue;
                } 
                if (is_array($child)) {
                    $child = $this->getTable()->createRow($child);
                    $this->addChild($child);
                } else {
                    throw new RuntimeException('Not array or not instance of Zend_Db_NodeInterface');
                }
            }
        } else {
            throw new RuntimeException('Nodes supplied as unsupported format.');
        }
    }    

    /**
     * Get the container of children
     *
     * @return mixed|Zend_Db_Tree_NestedSet_BranchIterator|null
     */
    public function getChildren()
    {
        if(isset($this->_children[$this->key()])) {
            $children = $this->_children[$this->key()];
            return $children;
        }
        return null;
    }
    
    public function count()
    {
        $count =  count($this->_children);
        return $count;
    }

    public function current()
    {
        return current($this->_children);
    }

    public function key()
    {
        return key($this->_children);
    }

    public function next()
    {
        next($this->_children);
    }

    public function rewind()
    {
        reset($this->_children);
    }

    public function valid()
    {
        $key = key($this->_children);
        $current = current($this->_children);
        $valid = current($this->_children) !== false;
        return $valid;
    }

    /**
     * Return the path to this node as an array of column values, the column
     * value to return cannot be primary key, left or right identifiers.
     * 
     * @param string $column
     * @return array $path
     */
    public function getPath($column = null)
    {        
        $select = $this->_table->select();
        $tableName = $this->_table->info(Zend_Db_Table::NAME);
        $lft = $this->_table->getLeftKey();
        $rgt = $this->_table->getRightKey();
        $primary = $this->_table->info(Zend_Db_Table::PRIMARY);
        
        if($column == null ||
           $column == $primary[1] ||
           $column == $lft ||
           $column == $rgt)
        {
            throw new Zend_Db_NestedSet_Exception(
                'Column cannot be null, primary, left or right.'
            );       
        }
        if (!in_array($column, $this->_table->info(Zend_Db_Table::COLS))) {
            throw new Zend_Db_NestedSet_Exception('Unknown column.');
        }
                       
        $sql  = "SELECT p.{$column}";
        $sql .= " FROM {$tableName} AS n, {$tableName} AS p";
        $sql .= " WHERE n.{$lft} BETWEEN p.{$lft} AND p.{$rgt}";
        $sql .= " AND n.{$column} = '{$this->$column}'";
        $sql .= " ORDER BY p.{$lft}";
        
        return $this->_table->getAdapter()
                            ->query($sql)
                            ->fetchAll();                    
    }

    public function getAncestors()
    {

    }

    /**
     * Reutrns whether the node has descendant nodes
     * 
     * @return bool
     */
    public function hasDescendants()
    {
        $lft = $this->_table->getLeftKey();
        $rgt = $this->_table->getRightKey();
        return (($this->$rgt - $this->$lft) > 1) ? true : false;        
    }
    
    public function getDescendants()
    {

    }

    public function getSiblings()
    {

    }
}