<?php
namespace Database\Migrations;

class CreatePostsTable
{
    public function up(): array
    {
        return [
            <<<SQL
CREATE TABLE IF NOT EXISTS posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    reply_to_id INT NULL,
    subject VARCHAR(255) NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    thumbnail_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reply_to_id) REFERENCES posts(post_id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL
        ];
    }

    public function down(): array
    {
        return [
            "DROP TABLE IF EXISTS posts"
        ];
    }
}

