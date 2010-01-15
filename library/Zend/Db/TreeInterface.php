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
     * Check if node is root of the tree
     * 
     * @param Zend_Db_TreeNodeInterface $node
     * @return bool
     */
    public function isRoot(Zend_Db_TreeNodeInterface $node);
    
    /**
     * Check if node is a leaf
     * 
     * @param Zend_Db_TreeNodeInterface $node
     * @return bool
     */    
    public function isLeaf(Zend_Db_TreeNodeInterface $node);
    
    /**
     * Check is the node has immediate descendants
     * 
     * @param Zend_Db_TreeNodeInterface $node
     * @return bool
     */
    public function hasChildren(Zend_Db_TreeNodeInterface $node);
    
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
     * @param Zend_Db_TreeNodeInterface $node
     */
    public function fetchBranch(Zend_Db_TreeNodeInterface $node = null);
    
    /**
     * Return the depth of a tree node
     *
     * @return int $depth
     */
    public function getDepth();
    
    /**
     * Proxies to getDepth()
     * 
     * @see Zend_Db_TreeInterface::getDepth()
     */
    public function getLevel();
    
    /**
     * Move a node (and all descendants) to the first child location of 
     * $destination node
     *
     * @param Zend_Db_TreeNodeInterface $destination
     * @param Zend_Db_TreeNodeInterface $origin
     */
    public function moveNodeToFirstChild(Zend_Db_TreeNodeInterface $destination,
                                         Zend_Db_TreeNodeInterface $origin);

    /**
     * Move a node (and all descendants) to the last child location of 
     * $destination node
     *
     * @param Zend_Db_TreeNodeInterface $destination
     * @param Zend_Db_Tree_NodeInterface $origin
     */
    public function moveNodeToLastChild(Zend_Db_TreeNodeInterface $destination,
                                        Zend_Db_TreeNodeInterface $origin);

    /**
     * Move a node (and all descendants) to the previous sibling location of
     * $destination node
     *
     * @param Zend_Db_TreeNodeInterface $destination
     * @param Zend_Db_TreeNodeInterface $origin
     */
    public function moveNodeToPreviousSibling(Zend_Db_TreeNodeInterface $destination,
                                              Zend_Db_TreeNodeInterface $origin);
                                              
    /**
     * Move a node (and all descendants) to the next sibling location of
     * $destination node
     *
     * @param Zend_Db_TreeNodeInterface $destination
     * @param Zend_Db_TreeNodeInterface $origin
     */
    public function moveNodeToNextSibling(Zend_Db_TreeNodeInterface $destination,
                                          Zend_Db_TreeNodeInterface $origin);

    /**
     * Add a child node to the 'last child' position
     *
     * @param Zend_Db_TreeNodeInterface $destination
     * @param Zend_Db_TreeNodeInterface $origin
     */
    public function addChild(Zend_Db_TreeNodeInterface $destination,
                             Zend_Db_TreeNodeInterface $origin);
}
