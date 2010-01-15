CREATE TABLE [categories] (
    [categoryId] INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    [parentId] INT NOT NULL,
    [rootId] INT DEFAULT NULL,
    [categoryName] VARCHAR, 
    [lft] INT NOT NULL, 
    [rgt] INT NOT NULL
)