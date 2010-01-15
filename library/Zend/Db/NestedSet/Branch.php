<?php
class Zend_Db_NestedSet_Branch extends Zend_Db_Table_Rowset_Abstract
                               implements Zend_Db_TreeBranchInterface
{

    /**
     * Zend_Db_Tree_NestedSet_NodeIterator
     *
     * @var RecursiveIteratorIterator
     */
    protected $_iterator = null;

    /**
     * Constructor, passes config to parent constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     * Return the rowset data as a multi-dimensional array
     *
     * @todo fallback to recursion to build the array if no parent column
     * @return array $tree
     */
    public function toMultiArray()
    {
        $tree = array();
        $ref = array();
        $tableInfo = $this->getTable()->info();
        $primary = $tableInfo['primary'][1];
        $parent = $this->getTable()->getParentKey();

        // TODO fallback to recursion to build the array if no parent column
        if(!in_array($parent, $tableInfo['cols'])) {
            throw new RuntimeException(
                'Cannot build mutli-dimensional array. Table has no parent column.'
            );
        }

        foreach ($this->_data as $node) {
                $node['children'] = array();
            if (isset($ref[$node[$parent]])) { 
                $ref[$node[$parent]]['children'][$node[$primary]] = $node;
                $ref[$node[$primary]] = & $ref[$node[$parent]]['children'][$node[$primary]];
            } else {
                $tree[$node[$primary]] = $node;
                $ref[$node[$primary]] = & $tree[$node[$primary]];
            }
        }
        return $tree;
    }

    /**
     * Return the rowset as recursive iterator tree
     *
     * @todo Fallback to recursion to build iterator if no parent column
     * @return RecursiveIteratorIterator
     */
    public function toIterator()
    {
        $tree = null;
        $ref = array();

        $tableInfo = $this->getTable()->info();
        $primary = $tableInfo['primary'][1];
        $parent = $this->getTable()->getParentKey();

        // TODO fallback to recursion to build the array at this point
        if(!in_array($parent, $tableInfo['cols'])) {
            throw new RuntimeException(
                'Cannot build mutli-dimensional array. Table has no parent column.'
            );
        }

        foreach ($this->_data as $value) {
        	if ( isset($ref[$value[$parent]]) ) { 
                $node = new $this->_rowClass(
                            array(
                                'table'    => $this->_table,
                                'data'     => $value,
                                'stored'   => $this->_stored,
                                'readOnly' => $this->_readOnly
                            )
                        );
                $ref[$value[$parent]]->addChild($node);
                $ref[$value[$primary]] = $node;
        	} else {
                $node = new $this->_rowClass(
                            array(
                                'table'    => $this->_table,
                                'data'     => $value,
                                'stored'   => $this->_stored,
                                'readOnly' => $this->_readOnly
                            )
                        );
               $tree = $node;
               $ref[$value[$primary]] = $node;
        	}
        }
        $this->_iterator = new RecursiveIteratorIterator(
                               $tree, RecursiveIteratorIterator::SELF_FIRST
                           );
        return $this->_iterator;
    }
}