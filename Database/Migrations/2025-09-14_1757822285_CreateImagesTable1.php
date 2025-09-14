<?php
namespace Database\Migrations;

class CreateImagesTable1
{
    public function up(): array
    {
        return [<<<SQL
CREATE TABLE images (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  slug VARCHAR(20) NOT NULL UNIQUE,
  delete_token CHAR(32) NOT NULL,
  orig_name VARCHAR(255) NOT NULL,
  mime VARCHAR(100) NOT NULL,
  ext  VARCHAR(10)  NOT NULL,
  size_bytes BIGINT NOT NULL,
  storage_path VARCHAR(255) NOT NULL,
  view_count INT NOT NULL DEFAULT 0,
  last_view_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NULL,
  ip_hash CHAR(64) NOT NULL,
  INDEX idx_created_at (created_at),
  INDEX idx_last_view (last_view_at)
)
SQL];
    }

    public function down(): array
    {
        return ['DROP TABLE images'];
    }
}

