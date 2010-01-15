<?php
interface Zend_Db_TreeInterface
{
    /**
     * Check whether tree has a root node
     * 
     * @return bool
     */
    public function hasRoot();
    
    /**
     * Insert a new root node un a multi-tree table
     *
     * @param array $values array of column/values 
     * @return Zend_Db_Tree_NodeInterface $root
     */
    public function setRootNode(array $values = array());
    
    /**
     * Add a new node
     *
     * @param Zend_Db_TreeNodeInterface $relation
     * @param mixed $data
     * @param string $position
     */
    public function addNode(Zend_Db_TreeNodeInterface $relation, $data, $position = null);
    
   /**
     * Move a node (and any descendants) of the tree to the specified location
     *
     * @param Zend_Db_Tree_NodeInterface $origin
     * @param Zend_Db_Tree_NodeInterface $destination
     * @param string $position
     */
    public function moveNode(Zend_Db_TreeNodeInterface $origin,
                             Zend_Db_TreeNodeInterface $destination,
                             $position = null);
                             
    /**
     * Delete a node (and all its children!)
     *
     * @param Zend_Db_TreeNodeInterface $node
     */
    public function deleteNode(Zend_Db_TreeNodeInterface $node);
    
    /**
     * Fetch a node via a select object
     *
     * @param Zend_Db_Table_Select $select
     */
    public function fetchNode(Zend_Db_Table_Select $select);
    
    /**
     * Return a branch, starting at $node or the whole tree
     *
     * @param mixed Zend_Db_Table_Select|Zend_Db_TreeNodeInterface $node
     */
    public function fetchBranch($node = null);    

    /**
     * Add a child node, 'last child' position by default
     *
     * @param Zend_Db_TreeNodeInterface $parent
     * @param array $data
     */
    public function addChild(Zend_Db_TreeNodeInterface $parent,
                             array $data,
                             $position = Zend_Db_NestedSet::LAST_CHILD);
                             
    /**
     * Add a sibling node, 'next' position by default
     *
     * @param Zend_Db_TreeNodeInterface $relation
     * @param array $data
     */
    public function addSibling(Zend_Db_TreeNodeInterface $relation,
                               array $data,
                               $position = Zend_Db_NestedSet::NEXT_SIBLING);                             
}
