<?php
// ~/php_dynamic_server_v2/Database/state.php
return [
    'User' => [
        'userID' => ['dataType'=>'INT','constraints'=>'AUTO_INCREMENT','primaryKey'=>true,'nullable'=>false],
        'username' => ['dataType'=>'VARCHAR(255)','nullable'=>false],
        'email' => ['dataType'=>'VARCHAR(255)','nullable'=>false],
        'password' => ['dataType'=>'VARCHAR(255)','nullable'=>false],
        'email_confirmed_at' => ['dataType'=>'VARCHAR(255)','nullable'=>true],
        'created_at' => ['dataType'=>'DATETIME','nullable'=>false],
        'updated_at' => ['dataType'=>'DATETIME','nullable'=>false],
    ],
    'Post' => [
        'postID' => ['dataType'=>'INT','constraints'=>'AUTO_INCREMENT','primaryKey'=>true,'nullable'=>false],
        'title' => ['dataType'=>'VARCHAR(255)','nullable'=>false],
        'content' => ['dataType'=>'TEXT','nullable'=>false],
        'created_at' => ['dataType'=>'DATETIME','nullable'=>false],
        'updated_at' => ['dataType'=>'DATETIME','nullable'=>false],
        'userID' => [
            'dataType'=>'INT','nullable'=>false,
            'foreignKey'=>['referenceTable'=>'User','referenceColumn'=>'userID','onDelete'=>'CASCADE'],
        ],
    ],
    'Comment' => [
        'commentID' => ['dataType'=>'INT','constraints'=>'AUTO_INCREMENT','primaryKey'=>true,'nullable'=>false],
        'commentText' => ['dataType'=>'VARCHAR(255)','nullable'=>false],
        'created_at' => ['dataType'=>'DATETIME','nullable'=>false],
        'updated_at' => ['dataType'=>'DATETIME','nullable'=>false],
        'userID' => [
            'dataType'=>'INT','nullable'=>false,
            'foreignKey'=>['referenceTable'=>'User','referenceColumn'=>'userID','onDelete'=>'CASCADE'],
        ],
        'postID' => [
            'dataType'=>'INT','nullable'=>false,
            'foreignKey'=>['referenceTable'=>'Post','referenceColumn'=>'postID','onDelete'=>'CASCADE'],
        ],
    ],
    'PostLike' => [
        'userID' => [
            'dataType'=>'INT','primaryKey'=>true,'nullable'=>false,
            'foreignKey'=>['referenceTable'=>'User','referenceColumn'=>'userID','onDelete'=>'CASCADE'],
        ],
        'postID' => [
            'dataType'=>'INT','primaryKey'=>true,'nullable'=>false,
            'foreignKey'=>['referenceTable'=>'Post','referenceColumn'=>'postID','onDelete'=>'CASCADE'],
        ],
    ],
    'CommentLike' => [
        'userID' => [
            'dataType'=>'INT','primaryKey'=>true,'nullable'=>false,
            'foreignKey'=>['referenceTable'=>'User','referenceColumn'=>'userID','onDelete'=>'CASCADE'],
        ],
        'commentID' => [
            'dataType'=>'INT','primaryKey'=>true,'nullable'=>false,
            'foreignKey'=>['referenceTable'=>'Comment','referenceColumn'=>'commentID','onDelete'=>'CASCADE'],
        ],
    ],
];
