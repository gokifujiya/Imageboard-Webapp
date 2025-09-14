<?php
namespace Database\Migrations;

use Database\SchemaMigration;

class CreateCarPartsTable1 implements SchemaMigration
{
    public function up(): array
    {
        return [
            "CREATE TABLE car_parts (
                id INT PRIMARY KEY AUTO_INCREMENT,
                carID INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                price FLOAT NOT NULL,
                quantityInStock INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (carID) REFERENCES cars(id) ON DELETE CASCADE
            )"
        ];
    }

    public function down(): array
    {
        return [
            "DROP TABLE car_parts"
        ];
    }
}

