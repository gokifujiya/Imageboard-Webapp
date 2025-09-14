<?php
require __DIR__ . '/../vendor/autoload.php';

use Database\MySQLWrapper;
use Faker\Factory as Faker;

$faker = Faker::create();
$mysqli = new MySQLWrapper();

// 20 cars
$carIds = [];
for ($i = 0; $i < 20; $i++) {
    $make  = $faker->randomElement(['Toyota','Honda','Nissan','Tesla','Ford','BMW','Audi']);
    $model = ucfirst($faker->word());
    $year  = (int)$faker->numberBetween(2005, 2025);
    $color = ucfirst($faker->safeColorName());
    $price = (float)$faker->randomFloat(2, 5000, 60000);
    $miles = (float)$faker->randomFloat(1, 0, 200000);
    $trans = $faker->randomElement(['Manual','Automatic']);
    $engine= $faker->randomElement(['Gasoline','Hybrid','Electric','Diesel']);
    $status= $faker->randomElement(['Available','Sold','Used']);

    $mysqli->query("
      INSERT INTO cars (make, model, year, color, price, mileage, transmission, engine, status)
      VALUES ('{$make}','{$model}',{$year},'{$color}',{$price},{$miles},'{$trans}','{$engine}','{$status}')
    ");
    $carIds[] = $mysqli->insert_id;
}

// 100 parts
$partIds = [];
for ($i = 0; $i < 100; $i++) {
    $name  = ucfirst($faker->words(2, true));
    $desc  = $faker->sentence(6);
    $price = (float)$faker->randomFloat(2, 5, 500);
    $qty   = (int)$faker->numberBetween(1, 500);

    $mysqli->query("
      INSERT INTO parts (name, description, price, quantityInStock)
      VALUES ('{$name}','{$desc}',{$price},{$qty})
    ");
    $partIds[] = $mysqli->insert_id;
}

// link randomly
foreach ($carIds as $cid) {
    $count = rand(3, 8);
    $picked = (array)array_rand(array_flip($partIds), $count);
    foreach ($picked as $pid) {
        $quantity = rand(1, 4);
        $mysqli->query("INSERT IGNORE INTO car_part (carID, partID, quantity) VALUES ({$cid}, {$pid}, {$quantity})");
    }
}

echo "Seeded 20 cars, 100 parts, and random links." . PHP_EOL;
$mysqli->close();
