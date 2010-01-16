<?php
require_once dirname(__FILE__) . '/_files/fixtures.php';

class Zend_Db_NestedSetNodeTest extends Zend_Test_PHPUnit_DatabaseTestCase {

	protected $row   = null;

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
    protected  function setUp()
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
    
    public function testConstructor()
    {
        $row = $this->tree->createRow();
        $row->__construct(
            array(
                'data' => array(
                    'categoryName' => 'foo',
                    'lft' => 35,
                    'rgt' => 36
                )
            )
        );
        
        $this->assertEquals('foo', $row->categoryName);
        $this->assertEquals(35, $row->lft);
        $this->assertEquals(36, $row->rgt);
    }
    
    public function testSetGetParent()
    {
        $tvRow = $this->tree->fetchRow(
            $this->tree->select()->where('categoryName =?', 'Televisions')
        );
        $LCDRow = $this->tree->fetchRow(
            $this->tree->select()->where('categoryName =?', 'LCD')
        );
        
        $LCDRow->setParent($tvRow);
        $this->assertEquals($tvRow, $LCDRow->getParent());
    }
    

    public function testFetchNodeReturnsNodeClass()
    {
    	$row = $this->tree->fetchNode(
    	   $this->tree->select()->where('categoryName=?', 'Plasma')
        );

        $this->assertThat(
            $row, $this->isInstanceOf('Zend_Db_NestedSet_Node')
        );
    }

    public function testFetchRowReturnsNodeClass()
    {
        $row = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Plasma')
        );

        $this->assertThat(
            $row, $this->isInstanceOf('Zend_Db_NestedSet_Node')
        );
    }

    public function testIsLeaf()
    {
        $row = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Plasma')
        );
        $this->assertTrue($row->isLeaf());

        $row = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Televisions')
        );
        $this->assertFalse($row->isLeaf());
    }

    public function testIsRoot()
    {
    	$row = $this->tree->fetchNode(
    	   $this->tree->select()->where($this->tree->getLeftKey() . '=?', 1)
    	);
    	$this->assertTrue($row->isRoot());

        $row = $this->tree->fetchNode(
           $this->tree->select()->where($this->tree->getLeftKey() . '=?', 2)
        );
        $this->assertFalse($row->isRoot());
    }
    
    public function testAddChildrenToSingleNodeAsNodeIterator()
    {
       
        $tvNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Televisions')
        );
        $plasmaNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Plasma')
        );
        $lcdNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'LCD')
        );

        $children = array($plasmaNode, $lcdNode);        
        $tvNode->addChildren($children);
        
        $this->assertTrue($tvNode->hasChildren());
    }
    
    public function testAddChildrenToSingleNodeAsArrayOfNodes()
    {
        $tvNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Televisions')
        );
        $plasmaNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Plasma')
        );
        $lcdNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'LCD')
        );

        $children = array($plasmaNode, $lcdNode);
        
        $tvNode->addChildren($children);
        
        $this->assertTrue($tvNode->hasChildren());        
    }
    
    public function testHasChildren()
    {
        
        $plasmaRow = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Plasma')
        );
        $this->assertFalse($plasmaRow->hasChildren());

        $row = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Televisions')
        );
        $row->addChildren(array($plasmaRow));
        $this->assertTrue($row->hasChildren());
    }    
    
    public function testGetChildren()
    {        
        $tvNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Televisions')
        );
        $plasmaNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Plasma')
        );
        $lcdNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'LCD')
        );

        $children = array($plasmaNode, $lcdNode);
        
        $tvNode->addChildren($children);

        $testChildren = $tvNode->getChildren();
        $this->assertThat($testChildren, $this->isInstanceOf('Zend_Db_NestedSet_Node'));
    }
    
    public function testGetChildrenReturnsNullWhenEmptyIterator()
    {
        $lcdNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'LCD')
        );
        $this->assertNull($lcdNode->getChildren());
    }
    
    public function testCount()
    {
        $tvNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Televisions')
        );
        $plasmaNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Plasma')
        );
        $lcdNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'LCD')
        );

        $children = array($plasmaNode, $lcdNode);
        
        $tvNode->addChildren($children);
        $this->assertEquals(2, $tvNode->count());        
    }    
    
    public function testIteration()
    {
        $tvNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Televisions')
        );
        $plasmaNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'Plasma')
        );
        $lcdNode = $this->tree->fetchRow(
           $this->tree->select()->where('categoryName=?', 'LCD')
        );

        $children = array($plasmaNode, $lcdNode);
       
        $tvNode->addChildren($children);
        
        $iterator = new RecursiveIteratorIterator($tvNode, RecursiveIteratorIterator::CHILD_FIRST);

        $iterator->rewind();
        $values = array();
        foreach($iterator as $node) {
            $values[] = $node->categoryName;
        }
        $this->assertEquals('Plasma', $values[0]);
        $this->assertEquals('LCD', $values[1]);             
    }
    
    public function testGetPath()
    {
        $node = $this->tree->fetchNode(
            $this->tree->select()->where('categoryName=?', 'LCD')
        );
        $path = $node->getPath('categoryName');
        $items = array('Electronics', 'Televisions', 'LCD');
        foreach ($path as $item) {
            $this->assertContains($item->categoryName, $items);            
        }
        $this->assertEquals(count($items), count($path));        
    }
    
    public function testGetDescendants()
    {
        $node = $this->tree->fetchNode(
            $this->tree->select()->where('categoryName=?', 'Audio Equipment')
        );
        $descendants = $node->getDescendants('categoryName');
        $items = array('Audio Equipment', 'MP3 Players', 'Radios', 'CD Players');
        
        foreach ($descendants as $item) {
            $this->assertContains($item->categoryName, $items);            
        }
        $this->assertEquals(count($items), count($descendants));        
    }
    
    public function testGetSiblings()
    {
        $node = $this->tree->fetchNode(
            $this->tree->select()->where('categoryName=?', 'MP3 Players')
        );
        
        $siblings = $node->getSiblings();
        $items = array('Radios', 'CD Players');
        
        foreach ($siblings as $item) {
            $this->assertContains($item->categoryName, $items);            
        }
        $this->assertEquals(count($items), count($siblings));                
    }
}
