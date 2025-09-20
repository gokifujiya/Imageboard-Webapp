<?php
namespace Database;

use Exception;
use mysqli;

class MySQLWrapper extends mysqli
{
    public function __construct(
        ?string $hostname = 'localhost',
        ?string $username = null,
        ?string $password = null,
        ?string $database = null,
        ?int $port = null,
        ?string $socket = null
    ) {
        // Throw mysqli exceptions instead of warnings/notices
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        // Pull creds from environment/config if not passed
        // Prefer your project's config/database.php if present
        if ($username === null || $password === null || $database === null) {
            $cfg = @include __DIR__ . '/../config/database.php';
            if (is_array($cfg)) {
                $username = $username ?? ($cfg['user']     ?? null);
                $password = $password ?? ($cfg['password'] ?? null);
                $database = $database ?? ($cfg['name']     ?? null);
                $hostname = $hostname ?? ($cfg['host']     ?? 'localhost');
                $port     = $port     ?? ($cfg['port']     ?? null);
            } else {
                // fallback to env if you use them
                $username = $username ?? getenv('DATABASE_USER');
                $password = $password ?? getenv('DATABASE_USER_PASSWORD');
                $database = $database ?? getenv('DATABASE_NAME');
            }
        }

        parent::__construct($hostname, $username, $password, $database, $port, $socket);
        // optional: $this->set_charset('utf8mb4');
    }

    /** Return current default database name. */
    public function getDatabaseName(): string
    {
        return $this->query("SELECT database() AS the_db")->fetch_row()[0];
    }

    /** Prepared SELECT that returns all rows as associative arrays. */
    public function prepareAndFetchAll(string $sql, string $types, array $params): ?array
    {
        $this->validateTypesAndParams($types, $params);

        $stmt = $this->prepare($sql);
        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result === false) {
            throw new Exception(sprintf('Error fetching data on query: %s', $sql));
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /** Prepared INSERT/UPDATE/DELETE that returns bool success. */
    public function prepareAndExecute(string $sql, string $types, array $params): bool
    {
        $this->validateTypesAndParams($types, $params);

        $stmt = $this->prepare($sql);
        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        return $stmt->execute();
    }

    private function validateTypesAndParams(string $types, array $params): void
    {
        if (strlen($types) !== count($params)) {
            throw new Exception(
                sprintf('Type and param count mismatch: types=%d params=%d', strlen($types), count($params))
            );
        }
    }
}

