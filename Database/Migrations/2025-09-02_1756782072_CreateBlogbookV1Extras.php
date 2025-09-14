<?php
namespace Database\Migrations;

use Database\SchemaMigration;

class CreateBlogbookV1Extras implements SchemaMigration
{
    public function up(): array
    {
        return [
            // 1) users: add subscription* columns (nullable)
            "ALTER TABLE users
                ADD COLUMN subscription            VARCHAR(50)  NULL AFTER password,
                ADD COLUMN subscription_status     VARCHAR(50)  NULL AFTER subscription,
                ADD COLUMN subscriptionCreatedAt   DATETIME     NULL AFTER subscription_status,
                ADD COLUMN subscriptionEndsAt      DATETIME     NULL AFTER subscriptionCreatedAt",

            // 2) categories
            "CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                categoryName VARCHAR(100) NOT NULL,
                UNIQUE KEY uq_categories_name (categoryName)
            )",

            // 3) posts: add category_id (NOT NULL) and FK
            // NOTE: pick a default category to satisfy NOT NULL (create one on the fly)
            "INSERT IGNORE INTO categories (id, categoryName) VALUES (1, 'General')",
            "ALTER TABLE posts
                ADD COLUMN category_id INT NOT NULL DEFAULT 1 AFTER user_id,
                ADD CONSTRAINT fk_posts_category
                    FOREIGN KEY (category_id) REFERENCES categories(id)
                    ON DELETE RESTRICT ON UPDATE CASCADE",

            // 4) tags + post_tags (many-to-many)
            "CREATE TABLE IF NOT EXISTS tags (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tagName VARCHAR(100) NOT NULL,
                UNIQUE KEY uq_tags_name (tagName)
            )",
            "CREATE TABLE IF NOT EXISTS post_tags (
                post_id INT NOT NULL,
                tag_id  INT NOT NULL,
                PRIMARY KEY (post_id, tag_id),
                KEY idx_post_tags_post (post_id),
                KEY idx_post_tags_tag (tag_id),
                CONSTRAINT fk_post_tags_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                CONSTRAINT fk_post_tags_tag  FOREIGN KEY (tag_id)  REFERENCES tags(id)  ON DELETE CASCADE
            )",

            // 5) comments
            "CREATE TABLE IF NOT EXISTS comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                commentText TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                user_id BIGINT NOT NULL,
                post_id INT NOT NULL,
                KEY idx_comments_user (user_id),
                KEY idx_comments_post (post_id),
                CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
            )",

            // 6) likes (composite PKs)
            "CREATE TABLE IF NOT EXISTS post_likes (
                user_id BIGINT NOT NULL,
                post_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, post_id),
                KEY idx_post_likes_post (post_id),
                CONSTRAINT fk_post_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_post_likes_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS comment_likes (
                user_id BIGINT NOT NULL,
                comment_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, comment_id),
                KEY idx_comment_likes_comment (comment_id),
                CONSTRAINT fk_comment_likes_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
                CONSTRAINT fk_comment_likes_comment FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE
            )",

            // 7) user_settings (KV)
            "CREATE TABLE IF NOT EXISTS user_settings (
                entryId INT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT NOT NULL,
                metaKey   VARCHAR(100) NOT NULL,
                metaValue TEXT         NOT NULL,
                KEY idx_user_settings_user (user_id),
                KEY idx_user_settings_key  (metaKey),
                CONSTRAINT fk_user_settings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
        ];
    }

    public function down(): array
    {
        return [
            // Drop dependents first
            "DROP TABLE IF EXISTS comment_likes",
            "DROP TABLE IF EXISTS post_likes",
            "DROP TABLE IF EXISTS comments",
            "DROP TABLE IF EXISTS post_tags",
            "DROP TABLE IF EXISTS tags",
            "DROP TABLE IF EXISTS user_settings",

            // posts: drop FK & column
            "ALTER TABLE posts DROP FOREIGN KEY fk_posts_category",
            "ALTER TABLE posts DROP COLUMN category_id",

            // users: drop subscription* columns
            "ALTER TABLE users
                DROP COLUMN subscriptionEndsAt,
                DROP COLUMN subscriptionCreatedAt,
                DROP COLUMN subscription_status,
                DROP COLUMN subscription",

            // finally categories
            "DROP TABLE IF EXISTS categories",
        ];
    }
}
