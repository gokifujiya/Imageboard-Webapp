<?php
namespace Database;

use Database\MySQLWrapper;

abstract class AbstractSeeder implements Seeder
{
    protected MySQLWrapper $conn;
    protected ?string $tableName = null;

    /**
     * Column specs in order. Example:
     * [
     *   ['column_name' => 'name', 'data_type' => 'string'],
     *   ['column_name' => 'age',  'data_type' => 'int'],
     * ]
     */
    protected array $tableColumns = [];

    // PHP 8 native types -> mysqli bind_param symbols
    public const AVAILABLE_TYPES = [
        'int'    => 'i',
        'float'  => 'd', // PHP float is double precision
        'string' => 's',
    ];

    public function __construct(MySQLWrapper $conn)
    {
        $this->conn = $conn;
    }

    public function seed(): void
    {
        $data = $this->createRowData();

        if ($this->tableName === null) {
            throw new \Exception('Class requires a table name.');
        }
        if (empty($this->tableColumns)) {
            throw new \Exception('Class requires column definitions.');
        }

        foreach ($data as $row) {
            $this->validateRow($row);
            $this->insertRow($row);
        }
    }

    /** Validate each value against $tableColumns types */
    protected function validateRow(array $row): void
    {
        if (count($row) !== count($this->tableColumns)) {
            throw new \Exception('Row column count does not match seeder definition.');
        }

        foreach ($row as $i => $value) {
            $colType = $this->tableColumns[$i]['data_type'];
            $colName = $this->tableColumns[$i]['column_name'];

            if (!isset(static::AVAILABLE_TYPES[$colType])) {
                throw new \InvalidArgumentException("Unsupported data type: {$colType}");
            }

            // get_debug_type returns PHP 8 native names (int, string, float, etc.)
            if (get_debug_type($value) !== $colType) {
                $json = json_encode($value);
                throw new \InvalidArgumentException("Value for {$colName} must be {$colType}. Got: {$json}");
            }
        }
    }

    /** Insert one row using prepared statement */
    protected function insertRow(array $row): void
    {
        $columnNames = array_map(
            fn($c) => $c['column_name'],
            $this->tableColumns
        );

        $placeholders = rtrim(str_repeat('?,', count($row)), ',');
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->tableName,
            implode(', ', $columnNames),
            $placeholders
        );

        $stmt = $this->conn->prepare($sql);

        $bindTypes = implode(array_map(
            fn($c) => static::AVAILABLE_TYPES[$c['data_type']],
            $this->tableColumns
        ));

        $stmt->bind_param($bindTypes, ...array_values($row));
        $stmt->execute();
    }
}
