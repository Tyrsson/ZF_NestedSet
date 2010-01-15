<?php
include_once 'Zend/Loader/Autoloader.php';
Zend_Loader_Autoloader::getInstance();
include_once(dirname(__FILE__) . '/../../../../../library/Zend/Db/TreeInterface.php');
include_once(dirname(__FILE__) . '/../../../../../library/Zend/Db/TreeBranchInterface.php');
include_once(dirname(__FILE__) . '/../../../../../library/Zend/Db/TreeNodeInterface.php');
include_once(dirname(__FILE__) . '/../../../../../library/Zend/Db/NestedSet/Branch.php');
include_once(dirname(__FILE__) . '/../../../../../library/Zend/Db/NestedSet/Node.php');
include_once(dirname(__FILE__) . '/../../../../../library/Zend/Db/NestedSet.php');
include_once(dirname(__FILE__) . '/../../../../../library/Zend/Db/NestedSet/Exception.php');

class Test_Tree_Model extends Zend_Db_NestedSet
{
    protected $_name    = 'categories';

    protected $_primary = 'categoryId';
    
    protected $lft = 'lft';
    
    protected $rgt = 'rgt';

    public function __construct($config = null) {
        parent::__construct($config);
    }

}

class Test_Tree_MultiModel extends Zend_Db_NestedSet
{
    protected $_name    = 'categories';

    protected $_primary = 'categoryId';
    
    protected $lft = 'lft';
    
    protected $rgt = 'rgt';

    public function __construct($config = null) {
        parent::__construct($config);
        $this->_multiRoot = true;        
    }

}
