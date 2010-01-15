<?php
interface Zend_Db_TreeNodeInterface
{
    /**
     * Return whether or not this is a leaf node
     *
     * @return bool
     */
    public function isLeaf();

    /**
     * Returns whether or not this is a root node
     *
     * @return bool
     */
    public function isRoot();
    
    /**
     * Return any siblings of this node or null if none
     *
     * @return mixed|Zend_Db_Tree_BranchIterator
     */
    public function getSiblings();

    /**
     * Returns array of values representing the ancestry of this node
     *
     * @return array
     */
    public function getPath();

    /**
     * Returns the parent node
     *
     * @return Zend_Db_Tree_NodeInterface
     */
    public function getParent();

    /**
     * Return a branch filled with the nodes ancestors only
     *
     * @return Zend_Db_Tree_BranchInterface
     */
    public function getAncestors();

    /**
     * Returns whether this node has descendants
     * 
     * @return bool
     */
    public function hasDescendants();
    
    /**
     * Return a branch filled with an decendants of this node
     *
     * @return Zend_Db_Tree_BranchInterface
     */
    public function getDescendants(); 

}