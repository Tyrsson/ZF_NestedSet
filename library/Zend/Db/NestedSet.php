<?php

abstract class Zend_Db_NestedSet extends Zend_Db_Table
                                 implements Zend_Db_TreeInterface
{
	protected $_rowClass = "Zend_Db_NestedSet_Node";

	protected $_rowsetClass = "Zend_Db_NestedSet_Branch";

    const FIRST_CHILD       = 'firstChild';
    const LAST_CHILD        = 'lastChild';
    const PREVIOUS_SIBLING  = 'previousSibling';
    const NEXT_SIBLING      = 'nextSibling';

    const LEFT_KEY          = 'lft';
    const RIGHT_KEY         = 'rgt';
    const PARENT_KEY        = 'parentId';    
    const ROOT_KEY          = 'rootId';
    
    /**
     * enable/disable table with mutliple trees
     * 
     * @var unknown_type
     */
    protected $_multiRoot = false;

    /**
     * Column identifier for the "left" node value
     *
     * @var string
     */
    protected $_lftKey;

    /**
     * Column identifier for the "right" node value
     *
     * @var string
     */
    protected $_rgtKey;

    /**
     * The parent id column for fast tree re-construction
     *
     * @var string
     */
    protected $_parentKey;
    
    /**
     * The root id column for multi tree tables
     * 
     * @var string
     */
    protected $_rootKey;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct($config = null)
    {
        parent::__construct($config);

        if (isset($config['leftKey'])) {
            $this->_lftKey = (string) $config['leftKey'];
        } else {
            $this->_lftKey = self::LEFT_KEY;
        }

        if (isset($config['rightKey'])) {
            $this->_rgtKey = (string) $config['rightKey'];
        } else {
            $this->_rgtKey = self::RIGHT_KEY;
        }

        if (isset($config['parentKey'])) {
            $this->_parentKey = (string) $config['parentKey'];
        } else {
            $this->_parentKey = self::PARENT_KEY;
        }
        
        if (isset($config['rootKey'])) {
            $this->_rootKey = (string) $config['rootKey'];
        } else {
            $this->_rootKey = self::ROOT_KEY;
        }
        
        if(isset($config['multiRoot'])) {
            $this->_multiRoot = (bool)$config['multiRoot'];
        }
    }

    /**
     * Set the left node key
     *
     * @param string $left
     * @return void
     */
    public function setLeftKey($left)
    {
        $this->_lftKey = (string)$left;
    }

    /**
     * Set the right key
     *
     * @param string $right
     */
    public function setRightKey($right)
    {
        $this->_rgtKey = (string)$right;
    }

    /**
     * Return name of left key column
     * @return string
     */
    public function getLeftKey()
    {
        return $this->_lftKey;
    }

    /**
     * return name of right key colum
     * @return string
     */
    public function getRightKey()
    {
        return $this->_rgtKey;
    }

    /**
     * Returns the parentID key
     *
     * @return string
     */
    public function getParentKey()
    {
        return $this->_parentKey;
    }
    
    /**
     * Return the rootId key name
     * @return string
     */
    public function getRootkey()
    {
        return $this->_rootKey;
    }
    
    /**
     * Set whether this table is multi root(tree) capables
     * @param bool $mutli
     */
    public function setMultiRoot($multi = true)
    {
        $row = $this->fetchNode($this->select()->where($this->_lftKey . '=1'));
        if($row && !$this->_multiRoot) {
            throw new Zend_Db_NestedSet_Exception('Cannot set table to multi-root.');
        }
        $this->_multiRoot = $multi;
        return $this;
    }

    /**
     * Check whether tree has a root node
     *
     * @return bool
     */
    public function hasRoot($rootId = null)
    {
        if ($this->_multiRoot && ($rootId == null)) {
            throw new Zend_Db_NestedSet_Exception('Ambiguous rootId');
        }
        
        $select = $this->select();
        $select->where($this->_lftKey . '=1');
        if($this->_multiRoot && !($rootId == null)) {
            $select->where($this->getRootkey() . '=?', $rootId);
        }
        $root = $this->fetchRow($select);
        if ($root === null) {
            return false;
        }
        return true;
    }
    
    /**
     * Set an initial or add a new root node
     * 
     */
    protected function _addRoot(array $data = array())
    {
        $data[$this->_lftKey] = 1;
        $data[$this->_rgtKey] = 2;
        $data[$this->_parentKey] = 0;
        
        if(!$this->_multiRoot && $this->hasRoot()) {
            throw new Zend_Db_NestedSet_Exception('Not a multi-tree table.');
        }
            
        $root = $this->fetchNew();
        $root->setFromArray($data);
        $id = $root->save();
            
        $savedRoot = $this->find($id);
        $savedRoot = $savedRoot[0];
        $savedRoot->{$this->getRootkey()} = $savedRoot->{$this->_primary[1]};
        $savedRoot->save();

        return $savedRoot;            
    }
    
    /**
     * Set a root node, typically done when setting up a single tree table
     *
     * @param array $values array of coulmn values
     * @return Zend_Db_Node_NestedSet $root node
     */
    public function setRootNode(array $values = array()) {

        if ($this->_multiRoot) {
            throw new Zend_Db_NestedSet_Exception(
                'Use addRootNode() on multi-tree tables.'
            );
        }
        return $this->_addRoot($values);
    }

    /**
     * Add a new root node to a multi-tree table
     * @param array $values
     * @return Zend_Db_Node_NestedSet $root node
     */
    public function addRootNode(array $values = array())
    {
        if(!$this->_multiRoot) {
            throw new Zend_Db_NestedSet_Exception(
                'Use setRootNode() on single tree tables.'
            );            
        }
        return $this->_addRoot($values);
    } 
    
    /**
     * Return whether the table is in multi mode or not
     * 
     * @return bool
     */
    public function isMultiRoot()
    {
        return $this->_multiRoot;
    }
    
    /**
     * Return a root node
     * @param $roodId
     * @return Zend_Db_Node_NestedSet
     */
    public function getRoot($rootId = null)
    {
        if($this->_multiRoot && $rootId == null) {
            throw new Zend_Db_NestedSet_Exception('Invalid rootId arguement.');
        }
        if(!$this->_multiRoot) {
            $rootId = 0;
        }
        $sql = $this->select()
                    ->where($this->getLeftKey() . '= ?', 1);
        if($this->_multiRoot) {
            $sql->where($this->getRootkey() . '= ?', $rootId);
        }
        return $this->fetchRow($sql);        
    }

    /**
     * Shift the left and right values of nodes to make room for the addition of
     * a new node. Calls to this method should be wrapped in a transaction.
     *
     * @param int $delta
     * @param int $value the node value to compare against
     * @param string|int $rootId the rootId in mutli tree tables
     * @return void
     */
    public function applyDeltaShift($delta, $value, $rootId = null)
    {
        if($this->_multiRoot && !$rootId) {
            throw new Zend_Db_NestedSet_Exception('You must specify a rootId.');
        }
        
        $lftKey = $this->getLeftKey();
        $rgtKey = $this->getRightKey();

        $leftWhere = $this->_db->quoteInto($lftKey . '>=?', $value);
        $rightWhere = $this->_db->quoteInto($rgtKey . '>=?', $value);
        
        if ($this->_multiRoot) {
            $rootClause = ' AND ' . $this->_db->quoteInto(
                                       $this->getRootkey() . '=?',$rootId
                                    );
            $leftWhere .= $rootClause;
            $rightWhere .= $rootClause; 
        } 
        
        $this->update(
            array(
                $lftKey => new Zend_Db_Expr($lftKey . '+' . $delta)
            ),
            $leftWhere  
        );

        $this->update(
            array(
                $rgtKey => new Zend_Db_Expr($rgtKey . '+' . $delta)
            ),
            $rightWhere  
        );
    }

    /**
     * Change the lef/right values of nodes between a range to allow for moving
     * branches. Any calls to this should be wrapped in a transaction. Auto-commit
     * is bad mkay.
     * 
     * @param Zend_Db_Table_Row $node
     * @param int $delta
     * @param string|int $rootId the rootId in mutli tree tables
     * @return void
     */
    public function applyDeltaRange($node, $delta, $rootId = null)
    {
        if($this->_multiRoot && !$rootId) {
            throw new Zend_Db_NestedSet_Exception('You must specify a rootId.');
        }
        
        $lftKey = $this->getLeftKey();
        $rgtKey = $this->getRightKey();
        
        $leftWhere = array(
            $this->_db->quoteInto($lftKey . '>=?', $node->$lftKey),
            $this->_db->quoteInto($lftKey . '<=?', $node->$rgtKey)
        );
        $rightWhere = array(
            $this->_db->quoteInto($rgtKey . '>=?', $node->$lftKey),
            $this->_db->quoteInto($rgtKey . '<=?', $node->$rgtKey )
        );

        if ($this->_multiRoot) {
            $rootClause = $this->_db->quoteInto(
                $this->getRootkey() . '=?',$rootId
            );
            $leftWhere[] = $rootClause;
            $rightWhere[] = $rootClause; 
        } 

        $this->update(array(
                $lftKey => new Zend_Db_Expr( $lftKey . '+' . $delta )
                ),
                $leftWhere
            );

        $this->update(array(
                $rgtKey => new Zend_Db_Expr( $rgtKey . '+' . $delta )
                ),
                $rightWhere
            );
    }

    /**
     * Add a new node, optionally with a position, defaults to adding new node
     * as a last(right most) child.
     *
     * @param Zend_Db_Table_Row $relation
     * @param mixed $data
     * @param string $position
     * @return Zend_Db_Table_Row $row
     * @todo check if save() performs a db commit
     */
    public function addNode(Zend_Db_TreeNodeInterface $relation, $data,
                            $position = null)
    {
        if (is_null($position)) {
            $position = self::LAST_CHILD;
        }

        $row = $this->fetchNew()->setFromArray($data);
        
        if ($position == self::PREVIOUS_SIBLING || 
            $position == self::NEXT_SIBLING)
        {
            $parent = $relation->{$this->getParentKey()};                
        } else {
            $parent = $relation->{$this->_primary[1]};
        }
        $row->{$this->getParentKey()} = $parent;
        
        if ($this->_multiRoot) {
            $row->{$this->getRootkey()} = $relation->{$this->getRootkey()};
        }

        $lftKey = $this->getLeftKey();
        $rgtKey = $this->getRightKey();

        switch ((string)$position) {
            case self::FIRST_CHILD:
                $row->$lftKey = $relation->$lftKey + 1;
                $row->$rgtKey = $relation->$lftKey + 2;
                break;
            case self::LAST_CHILD:
                $row->$lftKey = $relation->$rgtKey;
                $row->$rgtKey = $relation->$rgtKey + 1;
                break;
            case self::PREVIOUS_SIBLING:
                $row->$lftKey = $relation->$lftKey;
                $row->$rgtKey = $relation->$lftKey + 1;
                break;
            case self::NEXT_SIBLING:
                $row->$lftKey = $relation->$rgtKey + 1;
                $row->$rgtKey = $relation->$rgtKey + 2;
                break;
        	default:
        	   break;
        }
        
        try {
            $this->_db->beginTransaction();
            
            $rootId = null;
            if ($this->_multiRoot) {
                $rootId = $relation->{$this->getRootkey()};
            }
            $this->applyDeltaShift(2, $row->$lftKey, $rootId);
            
            $row->save();            
            $this->_db->commit();
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw new Zend_Db_NestedSet_Exception($e->getMessage());
        }

        return $row;
    }

    /**
     * Move a branch of the tree to the specified location
     *
     * @param Zend_Db_TreeNodeInterface $branchOrigin
     * @param Zend_Db_TreeNodeInterface $branchDestination
     * @return array $newBranchValues
     */
    public function moveNode(Zend_Db_TreeNodeInterface $origin,
                             Zend_Db_TreeNodeInterface $destination,
                             $position = null )
    {
        if(is_null($position)) {
            $position = self::LAST_CHILD;
        }
        
        $lftKey = $this->getLeftKey();
        $rgtKey = $this->getRightKey();
        $rootKey = $this->getRootkey();
        
        if($this->_multiRoot && 
           !($origin->$rootKey == $destination->$rootKey))
        {
            throw new Zend_Db_NestedSet_Exception(
                'Both origin and destination must be in the same tree.'
            );            
        }
                
        $updateParent = false;
        switch ((string)$position) {
            case self::FIRST_CHILD:
                $nodeDestination = $destination->$lftKey + 1;
                $updateParent = true;
                break;
            case self::LAST_CHILD:
                $nodeDestination = $destination->$rgtKey;
                $updateParent = true;
                break;
            case self::PREVIOUS_SIBLING:
                $nodeDestination = $destination->$lftKey;
                break;
            case self::NEXT_SIBLING:
            default:
                $nodeDestination = $destination->$rgtKey + 1;
                break;
        }

        $branchWidth = ($origin->$rgtKey - $origin->$lftKey) + 1;

        $this->_db->beginTransaction();

        try {
            $rootId = null;
            if ($this->_multiRoot) {
                $rootId = $origin->$rootKey;
            }
            $this->applyDeltaShift($branchWidth, $nodeDestination, $rootId);

            if ( $origin->$lftKey >= $nodeDestination ) {
                $origin->$lftKey += $branchWidth;
                $origin->$rgtKey += $branchWidth;
            }

            $range =  $nodeDestination - $origin->$lftKey;
            $this->applyDeltaRange($origin, $range, $rootId);

            $this->applyDeltaShift(-$branchWidth, ($origin->$rgtKey + 1), $rootId);
            
            if($updateParent) {
                $parent = $destination->{$this->_primary[1]}; 
            } else {
                $parent = $destination->{$this->getParentKey()};
            }   

            $this->update(array(
                    $this->getParentKey() => $parent
                ),
                $this->_primary[1] . '=' . $origin->{$this->_primary[1]}
            );

            $this->_db->commit();

        } catch (Exception $e) {
            $this->_db->rollBack();
            throw new Zend_Db_NestedSet_Exception($e->getMessage());
        }
        
        
        $newBranchValues = array('left'  => $origin->$lftKey + $range,
                                 'right' => $origin->$rgtKey + $range);

        if ($origin->$lftKey <= $nodeDestination) {
            $newBranchValues['left'] -= $branchWidth;
            $newBranchValues['right']-= $branchWidth;
        }

        return $newBranchValues;
    }

    /**
     * Delete a node (and all its children!)
     *
     * @param Zend_Db_Table_Row $node
     * @return int $rowsDeleted
     * @todo add option to move children to nodes position
     */
    public function deleteNode(Zend_Db_TreeNodeInterface $node) {

        $lftKey = $this->getLeftKey();
        $rgtKey = $this->getRightKey();

        try {
            $this->_db->beginTransaction();
            $rowsDeleted = $this->delete(array(
                $this->_db->quoteInto($lftKey . '>=?', $node->$lftKey),
                $this->_db->quoteInto($rgtKey . '<=?', $node->$rgtKey))
            );
            $this->applyDeltaShift(
                ($node->$lftKey - $node->$rgtKey - 1),
                $node->$rgtKey + 1
            );
            $this->_db->commit();
        } catch (Exception $e) {
            $this->_db->rollBack();
            throw new Zend_Db_NestedSet_Exception($e->getMessage());
        }

        return $rowsDeleted;
    }

    /**
     * Fetch a node via a select object
     *
     * @param Zend_Db_Table_Select $select
     * @return Zend_Db_table_Row $row
     */
    public function fetchNode(Zend_Db_Table_Select $select, $rootId = null) {
        if($this->_multiRoot && !$rootId) {
            throw new Zend_Db_NestedSet_Exception('You must specify a rootId.');
        }
        return $this->fetchRow($select);
    }

    /**
     * Return a branch, starting at $node, ordered on left value, depth first
     *
     * @param mixed Zend_Db_Table_Select|Zend_Db_TreeNodeInterface $node
     * @return Zend_Db_TreeNodeInterface $rowset
     */
    public function fetchBranch($node = null, $rootId = null)
    {
        if($this->_multiRoot && !$rootId) {
            throw new Zend_Db_NestedSet_Exception('You must specify a rootId.');
        }
        if($node instanceof Zend_Db_Table_Select) {
            $node = $this->fetchNode($node);
        }
        
        $lftKey = $this->getLeftKey();
        $rgtKey = $this->getRightKey();

        if (null == $node) {
            $nodeSelect = $this->select()->where($lftKey . '=?', 1);
            if ($this->_multiRoot) {
                $nodeSelect->where($this->getRootkey() . '=?', $rootId);
            }
            $node = $this->fetchRow($nodeSelect);
        }

        $select = $this->select()
                       ->where($lftKey . '>=?', $node->$lftKey)
                       ->where($rgtKey . '<=?', $node->$rgtKey)
                       ->order($lftKey . ' ASC');
                       
        if($this->_multiRoot) {
            $select->where($this->getRootkey() . '=?', $rootId);
        }

        return $this->fetchAll($select);
    }

    /**
     * Add a child node to the 'last child' position
     *
     * @param Zend_Db_NodeInterface $destination
     * @param Zend_Db_NodeInterface $origin
     * @return int $id of new node
     */
    public function addChild(Zend_Db_TreeNodeInterface $parent,
                             array $data,
                             $position = self::LAST_CHILD)
    {
        return $this->addNode($parent, $data, $position);
    }
    
    /**
     * Add a sibling node, 'next' position by default
     *
     * @param Zend_Db_NodeInterface $relation
     * @param array $data
     * @return int $id of new node
     */
    public function addSibling(Zend_Db_TreeNodeInterface $relation,
                               array $data,
                               $position = self::NEXT_SIBLING)
    {
        return $this->addNode($relation, $data, $position);
    }
}