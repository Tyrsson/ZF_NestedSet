Usage
=====

Sample database:

    CREATE TABLE IF NOT EXISTS `categories` (
      `categoryId` int NOT NULL AUTO_INCREMENT,
      `rootId` int NOT NULL,
      `categoryName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `parentId` int NOT NULL,
      `lft` int NOT NULL,
      `rgt` int NOT NULL,
      PRIMARY KEY (`categoryId`)
    ) ;

    INSERT INTO `categories` (`categoryId`, `rootId`, `categoryName`, `parentId`, `lft`, `rgt`) VALUES
    (1, 0, 'Electronics', 0, 1, 16),
    (2, 0, 'Televisions', 1, 2, 7),
    (3, 0, 'LCD', 2, 3, 4),
    (4, 0, 'Plasma', 2, 5, 6),
    (5, 0, 'Audio Equipment', 1, 8, 15),
    (6, 0, 'MP3 Players', 5, 9, 10),
    (7, 0, 'Radios', 5, 11, 12),
    (8, 0, 'CD Players', 5, 13, 14);
    
Get the full tree
-----------------
    
    <?php
    
    $table = new Zend_Db_NestedSet('categories');
    
    $tree = $table->getTree() 
    
    // or getTree($rootId) for mutli tree table
    
Get the full tree as a multidimensional associative array
---------------------------------------------------------
    
    <?php
    
    $table = new Zend_Db_NestedSet('categories');
    
    $tree = $table->getTree()->toMultiArray() 
    
    // or getTree($rootId) for mutli tree table
    
    $navigation = new Zend_Navigation($tree);
    
Get the full tree as a recursive iterator
---------------------------------------------------------
    
    <?php
    
    $table = new Zend_Db_NestedSet('categories');
    
    $tree = $table->getTree()->toIterator() 
    
    // or getTree($rootId) for mutli tree table    
        
    foreach($tree as $node) {
        echo str_repeat('--',$tree->getDepth()) . $node->categoryName;
    }
    
     
    */ Outputs the following
    
      Electronics
      --Televisions
      ----LCD
      ----Plasma
      --Audio Equipment
      ----MP3 Players
      ----Radios
      ----CD Players
      
    */
    
Get a branch starting at a specific node:
-----------------------------------------

    <?php
    $table = new Zend_Db_NestedSet('categories');
    
    $sql = $table->select()
                 ->where('categoryName = ?', 'Audio Equipment');
    
    $branch = $table->fetchBranch($sql);
    
Add a node
----------

    <?php
    
    $table = new Zend_Db_NestedSet('categories');
    
    $audio = $table->fetchNode(
        $table->select()->where('categoryName=?','Audio Equipment')
    );
    
    $newNode = $table->addNode($audio, array('categoryName' => 'iPods'));
    
Move a single node or a branch
------------------------------

    $table = new Zend_Db_NestedSet('categories');
    
    // Add new sub cateogry 'Apple' who's parent is the root 'Electronics'
    $apple = $table->addNode(
        $table->getRoot(),
        array('categoryName' => 'Apple')
    );
    
    // Add ipods under audio equipment
    $audio = $table->fetchNode(
        $table->select()->where('categoryName=?','Audio Equipment')
    );        
    $ipods = $table->addNode($audio, array('categoryName' => 'iPods'));
    
    // Move ipods to 'Apple' category as the first child
    $newLocation = $table->moveNode(
        $ipods, 
        $apple, 
        Zend_Db_NestedSet::FIRST_CHILD
    );