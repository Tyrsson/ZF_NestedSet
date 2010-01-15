<?php

require_once dirname(__FILE__) . '/_files/fixtures.php';

class MultiNestedSetTest extends Zend_Test_PHPUnit_DatabaseTestCase
{
    /**
     * @var Atech_Db_Tree_NestedSet
     */
    private $tree;

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    private $adapter;

    private $_connectionMock;
    
    public function getDataSet()
    {
        return $this->createXMLDataSet(
            dirname(__FILE__) . '/_files/multiCategoriesSeed.xml'
        );
    }
    
    public function getConnection()
    {   
        if ($this->_connectionMock == null) {
            $this->adapter = new Zend_Db_Adapter_Pdo_Sqlite(
                array(
                    'dbname' => ':memory:'
                )
            );
            $sql = file_get_contents(
                dirname(__FILE__) . '/_files/categories_table.sql'
            );
            $this->adapter->query($sql);
            $this->_connectionMock = $this->createZendDbConnection(
                $this->adapter,
                'categories'
            );
            Zend_Db_Table_Abstract::setDefaultAdapter($this->adapter);
        }
        return $this->_connectionMock;                
    }
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->tree = new Test_Tree_MultiModel(null, $this->adapter);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->tree = null;
        $this->adapter = null;

        parent::tearDown();
    }
    
    public function test__construct()
    {
        $config = array(
            'leftKey' => 'fooleft',
            'rightKey'=> 'fooright',
            'parentKey' => 'fooparent'
        );
        $this->tree->__construct($config);
        $this->assertEquals('fooleft', $this->tree->getLeftKey());
        $this->assertEquals('fooright', $this->tree->getRightKey());
        $this->assertEquals('fooparent', $this->tree->getParentKey());
    }
    
    public function testGetLeftKey()
    {
        $this->assertEquals($this->tree->getLeftKey(), 'lft');
    }

    public function testGetRightKey()
    {
        $this->assertEquals($this->tree->getRightKey(), 'rgt');

    }
    
    /**
     * @expectedException Zend_Db_NestedSet_Exception
     */
    public function testHasRootThrowsExceptionIfAmbiguous()
    {       
        $this->tree->hasRoot();
    }
    
    public function testHasRootReturnsTrueForValidRootId()
    {
        $this->assertTrue($this->tree->hasRoot(9));
    }
    
    /**
     * @expectedException Zend_Db_NestedSet_Exception
     */
    public function testSetRootNodeThrowsException()
    {
        $this->tree->setRootNode(array());
    }
    
    public function testAddRootNodeAddsNewRootNode()
    {
        $this->tree->addRootNode(array(
                'categoryName' => 'DVD',                
            )
        );
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/multiAddRootNode.xml'                
            ),
            $ds
        );                
    }
    
    /**
     * @expectedException Zend_Db_NestedSet_Exception
     */
    public function testApplyDeltaShiftThrowsException()
    {
        $this->tree->applyDeltaShift(2, 11);
    }
    
    public function testApplyDeltaShift()
    {
        $this->tree->applyDeltaShift(2, 11, 9);
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/multiModeDeltaShift.xml'                
            ),
            $ds
        );        
    }
    
    /**
     * @expectedException Zend_Db_NestedSet_Exception
     */
    public function testApplyDeltaRangeThrowsException()
    {
        $this->tree->applyDeltaRange(null, null);
    }
    
    public function testApplyRangeDeltaAsChild()
    {
        $node = new stdClass();
        $node->lft = 8;
        $node->rgt = 15;
        
        $this->tree->applyDeltaRange($node, -5, 9);
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/multiModeDeltaRange.xml'                
            ),
            $ds
        );        
    }
    
    /**
     * @expectedException Zend_Db_NestedSet_Exception
     */
    public function testFetchNodeThrowsException()
    {
        $this->tree->fetchNode($this->tree->select());
    }
    
    public function testFetchNodeReturnsCorrectTreeNode()
    {
        $select = $this->tree->select();
        $select->where('categoryName =?', 'Fiction');
        $node = $this->tree->fetchNode($select, 9);
        $this->assertEquals('Fiction', $node->categoryName);
    }
    
    /**
     * @expectedException Zend_Db_NestedSet_Exception
     */
    public function testFetchBranchThrowsException()
    {
        $node = $this->tree->fetchNew();
        $this->tree->fetchBranch($node);
    }
    
    public function testfetchBranch()
    {
        $select = $this->tree->select()->where('categoryName = ?', 'Non Fiction');
        $node = $this->tree->fetchNode($select, 9);
        $branch = $this->tree->fetchBranch($node, 9);
        $this->assertEquals(4, count($branch));
    }
    
    /**
     * @expectedException Zend_Db_NestedSet_Exception
     */
    public function testAddNodeThrowsException()
    {
        $data = array('categoryName' => 'Foo');
        $relation = $this->tree->fetchNew();
        $this->tree->addNode($relation, $data);
    }
    
    public function testAddNode()
    {
        $relation = $this->tree->getRoot(9);
        $data = array('categoryName' => 'Foo');
        $this->tree->addNode(
            $relation, 
            $data,
            Zend_Db_NestedSet::FIRST_CHILD
        );
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/multiModeAddRootChild.xml'                
            ),
            $ds
        );         
    }
    
    /**
     * @expectedException Zend_Db_NestedSet_Exception
     */
    public function testMoveNodesFromDifferentTreesThrowsException()
    {
        $oSelect = $this->tree->select()->where('categoryName = ?', 'Radios');
        $dSelect = $this->tree->select()->where('categoryName = ?', 'Fiction');
        $origin = $this->tree->fetchNode($oSelect, 1);
        $destination = $this->tree->fetchNode($dSelect, 9);
        
        $this->tree->moveNode(
            $origin, 
            $destination,
            Zend_Db_NestedSet::FIRST_CHILD
        );
    }
    
    public function testMoveNode()
    {
        $oSelect = $this->tree->select()->where('categoryName = ?', 'History');
        $dSelect = $this->tree->select()->where('categoryName = ?', 'Fiction');
        $origin = $this->tree->fetchNode($oSelect, 9);
        $destination = $this->tree->fetchNode($dSelect, 9);
        
        $this->tree->moveNode(
            $origin, 
            $destination,
            Zend_Db_NestedSet::FIRST_CHILD
        );

        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/multiModeMoveNode.xml'                
            ),
            $ds
        );        
    }
}