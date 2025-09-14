<?php
require __DIR__ . '/../vendor/autoload.php';

use Database\MySQLWrapper;

$mysqli = new MySQLWrapper();

// Insert car
$mysqli->query("
  INSERT INTO cars (make, model, year, color, price, mileage, transmission, engine, status)
  VALUES ('Toyota', 'Corolla', 2020, 'Blue', 20000, 1500, 'Automatic', 'Gasoline', 'Available')
");
$carId = $mysqli->insert_id;

// Insert parts
$mysqli->query("
  INSERT INTO parts (name, description, price, quantityInStock)
  VALUES ('Brake Pad', 'High Quality Brake Pad', 45.99, 100),
         ('Oil Filter', 'Long-lasting Oil Filter', 10.99, 200)
");

$partIds = [];
$res = $mysqli->query("SELECT id FROM parts ORDER BY id DESC LIMIT 2");
while ($row = $res->fetch_assoc()) { $partIds[] = (int)$row['id']; }

// Link car to parts (quantity is per car usage)
foreach ($partIds as $pid) {
    $mysqli->query("INSERT INTO car_part (carID, partID, quantity) VALUES ($carId, $pid, 1)");
}

echo "Seeded 1 car, 2 parts, and links (many-to-many)." . PHP_EOL;
$mysqli->close();
