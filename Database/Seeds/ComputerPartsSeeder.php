<?php
namespace Database\Seeds;

use Database\AbstractSeeder;
use Faker\Factory as Faker;
use Carbon\Carbon;

class ComputerPartsSeeder extends AbstractSeeder
{
    // generate this many rows (change to 10000 as requested)
    protected int $rows = 10000;

    protected ?string $tableName = 'computer_parts';

    protected array $tableColumns = [
        ['data_type' => 'string', 'column_name' => 'name'],
        ['data_type' => 'string', 'column_name' => 'type'],
        ['data_type' => 'string', 'column_name' => 'brand'],
        ['data_type' => 'string', 'column_name' => 'model_number'],
        ['data_type' => 'string', 'column_name' => 'release_date'],
        ['data_type' => 'string', 'column_name' => 'description'],
        ['data_type' => 'int',    'column_name' => 'performance_score'],
        ['data_type' => 'float',  'column_name' => 'market_price'],
        ['data_type' => 'float',  'column_name' => 'rsm'],
        ['data_type' => 'float',  'column_name' => 'power_consumptionw'],
        ['data_type' => 'float',  'column_name' => 'lengthm'],
        ['data_type' => 'float',  'column_name' => 'widthm'],
        ['data_type' => 'float',  'column_name' => 'heightm'],
        ['data_type' => 'int',    'column_name' => 'lifespan'],
        // created_at / updated_at are automatic via DEFAULTs, so we don’t seed them
    ];

    public function createRowData(): array
    {
        $faker = Faker::create();
        $types  = ['CPU','GPU','SSD','RAM','Motherboard','PSU','Case','Cooler'];
        $brands = ['AMD','Intel','NVIDIA','Samsung','Corsair','ASUS','MSI','Gigabyte','EVGA','Seagate','Crucial'];

        $rows = [];
        for ($i = 0; $i < $this->rows; $i++) {
            $type  = $faker->randomElement($types);
            $brand = $faker->randomElement($brands);

            $rows[] = [
                // name
                $brand.' '.$type.' '.$faker->bothify('Model-###?'),
                $type,
                $brand,
                // model_number
                strtoupper($faker->bothify('??-########')),
                // release_date (YYYY-MM-DD string to match VARCHAR/DATE)
                Carbon::now()->subDays($faker->numberBetween(0, 3650))->toDateString(),
                // description
                $faker->sentence(12),
                // performance_score (0–100)
                $faker->numberBetween(60, 99),
                // market_price
                (float) $faker->randomFloat(2, 39, 1999),
                // rsm (random spec metric)
                (float) $faker->randomFloat(2, 0.01, 0.20),
                // power_consumptionw
                (float) $faker->randomFloat(1, 1, 400),
                // dimensions meters
                (float) $faker->randomFloat(3, 0.03, 0.40),
                (float) $faker->randomFloat(3, 0.02, 0.30),
                (float) $faker->randomFloat(3, 0.002, 0.08),
                // lifespan (years)
                $faker->numberBetween(3, 10),
            ];
        }
        return $rows;
    }

    /**
     * Optional: speed up bulk seeding by reusing one prepared statement in a transaction.
     * This overrides AbstractSeeder::seed() for performance at 10k+ rows.
     */
    public function seed(): void
    {
        $data = $this->createRowData();
        if ($this->tableName === null || empty($this->tableColumns)) {
            throw new \Exception('Seeder misconfigured.');
        }

        // Build the INSERT once
        $columnNames = array_column($this->tableColumns, 'column_name');
        $placeholders = implode(',', array_fill(0, count($columnNames), '?'));
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->tableName,
            implode(', ', $columnNames),
            $placeholders
        );

        $types = implode('', array_map(fn($c) => static::AVAILABLE_TYPES[$c['data_type']], $this->tableColumns));

        // Transaction + single prepared statement
        $this->conn->begin_transaction();
        $stmt = $this->conn->prepare($sql);
        foreach ($data as $row) {
            $this->validateRow($row);
            $stmt->bind_param($types, ...array_values($row));
            $stmt->execute();
        }
        $stmt->close();
        $this->conn->commit();
    }
}

