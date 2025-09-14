<?php
namespace Database\Migrations;

use Database\SchemaMigration;

class CreateUsersTestTable1 implements SchemaMigration
{
    public function up(): array
    {
        return [
            "CREATE TABLE test_users (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(255)
            )"
        ];
    }

    public function down(): array
    {
        return [
            "DROP TABLE test_users"
        ];
    }
}

