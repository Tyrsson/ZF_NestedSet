<?php

require_once dirname(__FILE__) . '/_files/fixtures.php';

/**
 * Zend_Db_Abstract test case.
 */
class Zend_Db_NestedSetTest extends Zend_Test_PHPUnit_DatabaseTestCase
{

    /**
     * @var Zend_Db_NestedSet
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
            dirname(__FILE__) . '/_files/categoriesSeed.xml'
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
        $this->tree = new Test_Tree_Model(null, $this->adapter);
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

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        require_once "PHPUnit/TextUI/TestRunner.php";
        $suite  = new PHPUnit_Framework_TestSuite("Zend_Db_NestedSetTest");
        return PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Tests Zend_Db_Abstract->__construct()
     */
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

    public function testSetLeftRightKey()
    {
        $this->tree->setLeftKey('fooleft');
        $this->tree->setRightKey('fooright');
        $this->assertEquals('fooleft', $this->tree->getLeftKey());
        $this->assertEquals('fooright', $this->tree->getRightKey());
    }

    /**
     * Tests Zend_Db_Abstract->getLeftKey()
     */
    public function testGetLeftKey()
    {
        $this->assertEquals($this->tree->getLeftKey(), 'lft');
    }

    /**
     * Tests Zend_Db_Abstract->getRightKey()
     */
    public function testGetRightKey()
    {
        $this->assertEquals($this->tree->getRightKey(), 'rgt');

    }

    /**
     * Tests Zend_Db_Abstract->hasRoot()
     */
    public function testHasRoot()
    {
        $this->assertTrue($this->tree->hasRoot());
        $this->tree->delete($this->tree->getAdapter()->quoteInto($this->tree->getLeftKey() . '>?', 0));
        $this->assertFalse($this->tree->hasRoot());
    }

    /**
     * @expectedException Zend_Db_NestedSet_Exception
     */
    public function testAddRootNodeThrowsExceptionNonMultiTreeTables()
    {
        $this->tree->addRootNode(array());
    }

    public function testSetRootOnSingleTreeTable()
    {    
        $this->markTestIncomplete('Implement in other test suite.'); 
    }
    
    public function testGetRoot()
    {
        $root = $this->tree->getRoot();
        $this->assertEquals('Electronics', $root->categoryName);
    }

    public function testApplyShiftDelta()
    {
        $this->tree->applyDeltaShift(2, 11);
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeDeltaShift.xml'                
            ),
            $ds
        );
    }
    
    public function testApplyRangeDeltaAsChild()
    {
        $node = new stdClass();
        $node->lft = 8;
        $node->rgt = 15;
        
        $this->tree->applyDeltaRange($node, -5);
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeDeltaRange.xml'                
            ),
            $ds
        );        
    }
    
    public function testApplyRangeDeltaAsSibling()
    {
        $node = new stdClass();
        $node->lft = 8;
        $node->rgt = 15;
        
        $this->tree->applyDeltaRange($node, -5);
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeDeltaRangeChild.xml'                
            ),
            $ds
        );        
    }

    public function testAddNewFirstChildOfRootNode()
    {
        $root = $this->tree->getRoot();
        $this->tree->addNode(
            $root, 
            array('categoryName' => 'DVD'),
            Zend_Db_NestedSet::FIRST_CHILD
        );
        
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeAddRootChild.xml'                
            ),
            $ds
        );        
    }
    
    public function testAddNewLastChildOfRootNode()
    {
        $root = $this->tree->getRoot();
        $this->tree->addNode(
            $root, 
            array('categoryName' => 'DVD'),
            Zend_Db_NestedSet::LAST_CHILD
        );
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeAddRootChildLast.xml'                
            ),
            $ds
        );        
    }

    public function testHasDescendantsReturnsTrueOnBranchParent()
    {
        $select = $this->tree->select()->where('categoryName =?', 'Televisions');
        $node = $this->tree->fetchNode($select);
        $this->assertTrue($node->hasDescendants());
    }
    
    public function testHasDescendantsReturnsFalseOnLeafNode()
    {
        $select = $this->tree->select()->where('categoryName =?', 'LCD');
        $node = $this->tree->fetchNode($select);
        $this->assertFalse($node->hasDescendants());
    }

    public function testRootNodeIsValidAfterAddingChildNodes()
    {
        $root = $this->tree->getRoot();
        $lft = $root->lft;
        $rgt = $root->rgt;
        $firstChild = $this->tree->addNode(
            $root,
            array('categoryName' => 'Consoles'),
            Zend_Db_NestedSet::FIRST_CHILD
        );
        $node = $this->tree->addNode(
            $firstChild,
            array('categoryName' => 'PS3'),
            Test_Tree_Model::FIRST_CHILD
        );
        $root = $this->tree->getRoot();

        $this->assertEquals($root->lft, $lft);
        $this->assertEquals($root->rgt, $rgt+4);
    }

    public function testAddNewNodeAs_PREVIOUS_SIBLING_Node()
    {
        $where = $this->tree->select()->where('categoryName = ?', 'MP3 Players');
        $node = $this->tree->fetchNode($where);
        $this->tree->addNode(
            $node, 
            array('categoryName' => 'Separates'),
            Zend_Db_NestedSet::PREVIOUS_SIBLING
        );
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeAddSiblingFirst.xml'                
            ),
            $ds
        ); 
    }

    public function testAddNewNodeAs_NEXT_SIBLING_Node()
    {
        $where = $this->tree->select()->where('categoryName = ?', 'MP3 Players');
        $node = $this->tree->fetchNode($where);
        $this->tree->addNode(
            $node, 
            array('categoryName' => 'Separates'),
            Zend_Db_NestedSet::NEXT_SIBLING
        );
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeAddSiblingNext.xml'                
            ),
            $ds
        ); 
    }
    
    public function testMoveTreeBranchFromLeftToRightAsChildFirst()
    {
        
        $originSelect = $this->tree->select()->where('categoryName =?', 'Televisions');
        $destinationselect = $this->tree->select()->where('categoryName =?', 'Radios' );

        $origin = $this->tree->fetchRow($originSelect);
        $destination = $this->tree->fetchRow($destinationselect);

        $newLocation = $this->tree->moveNode(
            $origin, 
            $destination, 
            Zend_Db_NestedSet::FIRST_CHILD
        );
        $this->assertType('array', $newLocation);
        $this->assertArrayHasKey('left', $newLocation);
        $this->assertArrayHasKey('right', $newLocation);

        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeMoveNodeLeftRightChildFirst.xml'                
            ),
            $ds
        ); 
    }
    
    public function testMoveTreeBranchFromLeftToRightAsChildLast()
    {
        
        $originSelect = $this->tree->select()->where('categoryName =?', 'Televisions');
        $destinationselect = $this->tree->select()->where('categoryName =?', 'Audio Equipment' );

        $origin = $this->tree->fetchRow($originSelect);
        $destination = $this->tree->fetchRow($destinationselect);

        $newLocation = $this->tree->moveNode(
            $origin, 
            $destination, 
            Zend_Db_NestedSet::LAST_CHILD
        );
        $this->assertType('array', $newLocation);
        $this->assertArrayHasKey('left', $newLocation);
        $this->assertArrayHasKey('right', $newLocation);

        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeMoveNodeLeftRightChildLast.xml'                
            ),
            $ds
        ); 
    }
    
    public function testMoveTreeBranchFromLeftToRightAsSiblingPrevious()
    {
        
        $originSelect = $this->tree->select()->where('categoryName =?', 'Televisions');
        $destinationselect = $this->tree->select()->where('categoryName =?', 'MP3 Players' );

        $origin = $this->tree->fetchRow($originSelect);
        $destination = $this->tree->fetchRow($destinationselect);

        $newLocation = $this->tree->moveNode(
            $origin, 
            $destination, 
            Zend_Db_NestedSet::PREVIOUS_SIBLING
        );
        $this->assertType('array', $newLocation);
        $this->assertArrayHasKey('left', $newLocation);
        $this->assertArrayHasKey('right', $newLocation);

        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeMoveNodeLeftRightSiblingPrevious.xml'                
            ),
            $ds
        ); 
    }
    
    public function testMoveTreeBranchFromLeftToRightAsSiblingNext()
    {
        
        $originSelect = $this->tree->select()->where('categoryName =?', 'Televisions');
        $destinationselect = $this->tree->select()->where('categoryName =?', 'Radios' );

        $origin = $this->tree->fetchRow($originSelect);
        $destination = $this->tree->fetchRow($destinationselect);

        $newLocation = $this->tree->moveNode(
            $origin, 
            $destination, 
            Zend_Db_NestedSet::NEXT_SIBLING
        );
        $this->assertType('array', $newLocation);
        $this->assertArrayHasKey('left', $newLocation);
        $this->assertArrayHasKey('right', $newLocation);

        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeMoveNodeLeftRightSiblingNext.xml'                
            ),
            $ds
        ); 
    }    

    public function testMoveTreeBranchRightToLeftAsFirstChild()
    {
        $originSelect = $this->tree->select()->where('categoryName =?', 'Audio Equipment');
        $destinationselect = $this->tree->select()->where('categoryName =?', 'Televisions' );

        $origin = $this->tree->fetchRow($originSelect);
        $destination = $this->tree->fetchRow($destinationselect);

        $newLocation = $this->tree->moveNode(
            $origin,
            $destination,
            Zend_Db_NestedSet::FIRST_CHILD
        );
        
        $this->assertType('array', $newLocation);
        $this->assertArrayHasKey('left', $newLocation);
        $this->assertArrayHasKey('right', $newLocation);
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeMoveNodeRightLeftFirstChild.xml'                
            ),
            $ds
        );        
    }
    
    public function testMoveTreeBranchRightToLeftAsLastChild()
    {
        $originSelect = $this->tree->select()->where('categoryName =?', 'Audio Equipment');
        $destinationselect = $this->tree->select()->where('categoryName =?', 'Televisions' );

        $origin = $this->tree->fetchRow($originSelect);
        $destination = $this->tree->fetchRow($destinationselect);

        $newLocation = $this->tree->moveNode(
            $origin,
            $destination,
            Zend_Db_NestedSet::LAST_CHILD
        );
        
        $this->assertType('array', $newLocation);
        $this->assertArrayHasKey('left', $newLocation);
        $this->assertArrayHasKey('right', $newLocation);
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeMoveNodeRightLeftLastChild.xml'                
            ),
            $ds
        );        
    }

    public function testMoveTreeBranchRightToLeftAsPreviousSibling()
    {
        $originSelect = $this->tree->select()->where('categoryName =?', 'Audio Equipment');
        $destinationselect = $this->tree->select()->where('categoryName =?', 'Televisions' );

        $origin = $this->tree->fetchRow($originSelect);
        $destination = $this->tree->fetchRow($destinationselect);

        $newLocation = $this->tree->moveNode(
            $origin,
            $destination,
            Zend_Db_NestedSet::PREVIOUS_SIBLING
        );
        
        $this->assertType('array', $newLocation);
        $this->assertArrayHasKey('left', $newLocation);
        $this->assertArrayHasKey('right', $newLocation);
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeMoveNodeRightLeftPreviousSibling.xml'                
            ),
            $ds
        );        
    }

    public function testMoveTreeBranchRightToLeftAsNextSibling()
    {
        $originSelect = $this->tree->select()->where('categoryName =?', 'Audio Equipment');
        $destinationselect = $this->tree->select()->where('categoryName =?', 'LCD' );

        $origin = $this->tree->fetchRow($originSelect);
        $destination = $this->tree->fetchRow($destinationselect);

        $newLocation = $this->tree->moveNode(
            $origin,
            $destination,
            Zend_Db_NestedSet::NEXT_SIBLING
        );
        
        $this->assertType('array', $newLocation);
        $this->assertArrayHasKey('left', $newLocation);
        $this->assertArrayHasKey('right', $newLocation);
        
        $ds = new Zend_Test_PHPUnit_Db_DataSet_QueryDataSet(
            $this->getConnection()
        );
        $ds->addTable('categories', 'SELECT * FROM categories');
        
        $this->assertDataSetsEqual(
            $this->createXMLDataSet(
                dirname(__FILE__) . '/_files/singleModeMoveNodeRightLeftNextSibling.xml'                
            ),
            $ds
        );        
    }    

    public function testFetchNode()
    {
        
        $select = $this->tree->select()->where('categoryName =?', 'MP3 Players');
        $node = $this->tree->fetchNode($select);
        $this->assertThat($node, $this->isInstanceOf('Zend_Db_TreeNodeInterface'));
        $this->assertEquals('MP3 Players', $node->categoryName);
    }

    public function testDeleteNode()
    {
       
        $select = $this->tree->select()->where('categoryName =?', 'MP3 Players');
        $node = $this->tree->fetchNode($select);
        $result = $this->tree->DeleteNode($node);
        $this->assertGreaterThanOrEqual(1, $result);
        $delNode = $this->tree->fetchNode($select);
        $this->assertNull($delNode);
    }

    public function testGetBranch()
    {
        //$this->markTestIncomplete('not implimented');
    }

    public function testGetDepth()
    {
        //$this->markTestIncomplete('not implimented');
    }

    public function testGetPath()
    {
        //$this->markTestIncomplete('not implimented');
    }

}