<?php
require_once dirname(__FILE__) . '/_files/fixtures.php';

class Zend_Db_NestedSetBranchTest extends Zend_Test_PHPUnit_DatabaseTestCase
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
    
    public function testGetBranchAsArray()
    {
        $node = $this->tree->fetchNode($this->tree->select()->where('categoryName =?', 'Plasma'));
        $newNode = $this->tree->addNode($node, array('categoryName' => 'foo'));
        $this->tree->addNode($newNode, array('categoryName' => 'bar'));
        
        $treeArray = $this->tree->fetchBranch()->toMultiArray();
        
        $tvNode = $this->tree->fetchNode(
            $this->tree->select()->where('categoryName =?', 'Televisions')
        );
        $this->assertArrayHasKey(1, $treeArray);
        $this->assertArrayHasKey($tvNode->categoryId, $treeArray[1]['children']);
        
        $plasmaNode = $this->tree->fetchNode(
            $this->tree->select()->where('categoryName =?', 'Plasma')
        );
        $this->assertArrayHasKey($plasmaNode->categoryId, $treeArray[1]['children'][$tvNode->categoryId]['children']);
    }
    
    public function testGetBranchAsObject()
    {
        $rows = $this->tree->fetchBranch();
        $iterator = $rows->toIterator();
                
        $iterator->rewind();
        foreach($rows as $row) {
            if($row->categoryId == 1) {
                continue;
            }
            $node = $iterator->current();
            $this->assertEquals($row->categoryId, $node->categoryId);
            $iterator->next();
        }
    }
}