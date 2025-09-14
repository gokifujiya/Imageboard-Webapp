<?php
namespace Database\Migrations;

use Database\SchemaMigration;

class CreateSnippetsTable1 implements SchemaMigration
{
    public function up(): array
    {
        return [
            // Keep slug unique and short (e.g., 10–16 chars). Add indexes for lookups/expiry sweeps.
            "CREATE TABLE snippets (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                slug VARCHAR(32) NOT NULL UNIQUE,
                language VARCHAR(64) NULL,
                content MEDIUMTEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NULL,
                ip_hash CHAR(64) NULL,
                user_agent VARCHAR(255) NULL,
                INDEX idx_expires_at (expires_at),
                INDEX idx_language (language)
            )"
        ];
    }

    public function down(): array
    {
        return ["DROP TABLE snippets"];
    }
}

