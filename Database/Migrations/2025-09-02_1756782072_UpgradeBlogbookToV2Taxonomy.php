<?php
namespace Database\Migrations;

use Database\SchemaMigration;

class UpgradeBlogbookToV2Taxonomy implements SchemaMigration
{
    public function up(): array
    {
        return [
            // A) Move subscriptions to their own table
            "CREATE TABLE IF NOT EXISTS subscriptions (
                subscriptionID INT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT NOT NULL,
                subscription VARCHAR(50) NOT NULL,
                subscription_status VARCHAR(50) NOT NULL,
                subscriptionCreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                subscriptionEndsAt DATETIME NULL,
                KEY idx_subscriptions_user (user_id),
                CONSTRAINT fk_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",

            // B) Replace categories/tags/post_tags with taxonomy system
            "CREATE TABLE IF NOT EXISTS taxonomies (
                taxonomyID INT AUTO_INCREMENT PRIMARY KEY,
                taxonomyName VARCHAR(100) NOT NULL,
                description  VARCHAR(255) NULL,
                UNIQUE KEY uq_taxonomy_name (taxonomyName)
            )",
            "CREATE TABLE IF NOT EXISTS taxonomy_terms (
                taxonomyTermID INT AUTO_INCREMENT PRIMARY KEY,
                taxonomyID INT NOT NULL,
                taxonomyTermName VARCHAR(100) NOT NULL,
                description VARCHAR(255) NULL,
                parentTaxonomyTerm INT NULL,
                KEY idx_terms_taxonomy (taxonomyID),
                KEY idx_terms_parent (parentTaxonomyTerm),
                CONSTRAINT fk_terms_taxonomy FOREIGN KEY (taxonomyID) REFERENCES taxonomies(taxonomyID) ON DELETE CASCADE,
                CONSTRAINT fk_terms_parent   FOREIGN KEY (parentTaxonomyTerm) REFERENCES taxonomy_terms(taxonomyTermID) ON DELETE SET NULL
            )",
            "CREATE TABLE IF NOT EXISTS post_taxonomy (
                postTaxonomyID INT AUTO_INCREMENT PRIMARY KEY,
                postID INT NOT NULL,
                taxonomyID INT NOT NULL,
                taxonomyTermID INT NOT NULL,
                KEY idx_pt_post (postID),
                KEY idx_pt_tax (taxonomyID),
                KEY idx_pt_term (taxonomyTermID),
                CONSTRAINT fk_pt_post  FOREIGN KEY (postID)         REFERENCES posts(id)             ON DELETE CASCADE,
                CONSTRAINT fk_pt_tax   FOREIGN KEY (taxonomyID)     REFERENCES taxonomies(taxonomyID) ON DELETE CASCADE,
                CONSTRAINT fk_pt_term  FOREIGN KEY (taxonomyTermID) REFERENCES taxonomy_terms(taxonomyTermID) ON DELETE CASCADE
            )",

            // C) Remove old columns/tables no longer used in V2
            // posts: drop category_id
            "ALTER TABLE posts DROP FOREIGN KEY fk_posts_category",
            "ALTER TABLE posts DROP COLUMN category_id",
            // users: drop inline subscription fields
            "ALTER TABLE users
                DROP COLUMN subscriptionEndsAt,
                DROP COLUMN subscriptionCreatedAt,
                DROP COLUMN subscription_status,
                DROP COLUMN subscription",
            // drop old category/tag tables
            "DROP TABLE IF EXISTS post_tags",
            "DROP TABLE IF EXISTS tags",
            "DROP TABLE IF EXISTS categories",
        ];
    }

    public function down(): array
    {
        return [
            // 1) Recreate categories/tags/post_tags and re-add posts.category_id
            "CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                categoryName VARCHAR(100) NOT NULL,
                UNIQUE KEY uq_categories_name (categoryName)
            )",
            "ALTER TABLE posts
                ADD COLUMN category_id INT NOT NULL DEFAULT 1",
            "INSERT IGNORE INTO categories (id, categoryName) VALUES (1, 'General')",
            "ALTER TABLE posts
                ADD CONSTRAINT fk_posts_category
                    FOREIGN KEY (category_id) REFERENCES categories(id)
                    ON DELETE RESTRICT ON UPDATE CASCADE",
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

            // 2) Put subscription fields back on users and drop subscriptions table
            "ALTER TABLE users
                ADD COLUMN subscription            VARCHAR(50)  NULL AFTER password,
                ADD COLUMN subscription_status     VARCHAR(50)  NULL AFTER subscription,
                ADD COLUMN subscriptionCreatedAt   DATETIME     NULL AFTER subscription_status,
                ADD COLUMN subscriptionEndsAt      DATETIME     NULL AFTER subscriptionCreatedAt",
            "DROP TABLE IF EXISTS subscriptions",

            // 3) Drop taxonomy system
            "DROP TABLE IF EXISTS post_taxonomy",
            "DROP TABLE IF EXISTS taxonomy_terms",
            "DROP TABLE IF EXISTS taxonomies",
        ];
    }
}
