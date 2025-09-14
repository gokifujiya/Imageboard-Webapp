<?php
namespace Commands\Programs;

use Commands\AbstractCommand;
use Commands\Argument;
use Database\MySQLWrapper;

class StateMigrate extends AbstractCommand
{
    protected static ?string $alias = 'state-migrate';

    public static function getArguments(): array
    {
        return [
            (new Argument('init'))
                ->description('Table Initialization (required for this demo).')
                ->required(true),
            (new Argument('dry-run'))
                ->description('Show SQL without executing.')
                ->required(false)
                ->allowAsShort(true),
        ];
    }

    public function execute(): int
    {
        $dryRun = $this->getArgumentValue('dry-run') !== false;

        $this->log("Starting state migration...");
        $desiredSchema = include __DIR__ . '/../../Database/state.php';

        if (!is_array($desiredSchema)) {
            throw new \RuntimeException("State file did not return an array.");
        }

        if ($dryRun) {
            $this->log("--- DRY RUN --- no changes will be applied.");
        }

        // Danger: demo only — wipe before recreating
        $this->cleanDatabase($dryRun);

        // Create tables
        foreach ($desiredSchema as $table => $columns) {
            $sqls = $this->generateCreateTableSql($table, $columns);
            foreach ($sqls as $sql) {
                if ($dryRun) {
                    $this->log($sql . ';');
                } else {
                    $this->exec($sql, "OK: " . $sql);
                }
            }
        }

        // Foreign keys after base tables exist
        foreach ($desiredSchema as $table => $columns) {
            $fkSqls = $this->generateForeignKeySql($table, $columns);
            foreach ($fkSqls as $sql) {
                if ($dryRun) {
                    $this->log($sql . ';');
                } else {
                    $this->exec($sql, "OK: " . $sql);
                }
            }
        }

        $this->log($dryRun ? "State sync (dry-run) complete." : "State sync complete.");
        return 0;
    }

    /** Drop all tables (demo only). */
    private function cleanDatabase(bool $dryRun): void
    {
        $mysqli = new MySQLWrapper();

        if ($dryRun) {
            $this->log("Dropping all tables...");
        } else {
            $this->exec("SET foreign_key_checks = 0");
        }

        $res = $mysqli->query("SHOW TABLES");
        $toDrop = [];
        while ($res && ($row = $res->fetch_row())) {
            $toDrop[] = $row[0];
        }

        // Drop children first-ish (best effort)
        // In real systems, compute dependency graph; here we attempt multiple passes.
        for ($pass = 0; $pass < 3; $pass++) {
            foreach ($toDrop as $i => $t) {
                $sql = "DROP TABLE `$t`";
                if ($dryRun) {
                    $this->log("Dropped $t");
                    unset($toDrop[$i]);
                } else {
                    if ($mysqli->query($sql)) {
                        $this->log("Dropped $t");
                        unset($toDrop[$i]);
                    }
                }
            }
            if (empty($toDrop)) break;
        }

        if (!$dryRun) {
            $this->exec("SET foreign_key_checks = 1");
        }
    }

    /** Build CREATE TABLE and PRIMARY KEY SQL (no FKs yet). */
    private function generateCreateTableSql(string $table, array $columns): array
    {
        $cols = [];
        $pk = [];

        foreach ($columns as $name => $props) {
            $def = '`' . $name . '` ' . $props['dataType'];
            if (!empty($props['constraints'])) {
                $def .= ' ' . $props['constraints'];
            }
            if (isset($props['nullable']) && $props['nullable'] === false) {
                $def .= ' NOT NULL';
            }
            $cols[] = $def;

            if (!empty($props['primaryKey'])) {
                $pk[] = '`' . $name . '`';
            }
        }

        if (!empty($pk)) {
            $cols[] = 'PRIMARY KEY (' . implode(',', $pk) . ')';
        }

        $sql = "CREATE TABLE `{$table}` (" . implode(",\n  ", $cols) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        return [$sql];
    }

    /** Build ALTER TABLE ... ADD CONSTRAINT statements for FKs. */
    private function generateForeignKeySql(string $table, array $columns): array
    {
        $sqls = [];
        foreach ($columns as $name => $props) {
            if (empty($props['foreignKey'])) continue;

            $fk = $props['foreignKey'];
            $refTable  = $fk['referenceTable'];
            $refColumn = $fk['referenceColumn'];
            $onDelete  = !empty($fk['onDelete']) ? " ON DELETE {$fk['onDelete']}" : '';
            $fkName    = sprintf("fk_%s_%s", $table, $name);

            $sqls[] = sprintf(
                "ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`)%s",
                $table, $fkName, $name, $refTable, $refColumn, $onDelete
            );
        }
        return $sqls;
    }

    private function exec(string $sql, string $okMsg = null): void
    {
        $mysqli = new MySQLWrapper();
        $res = $mysqli->query($sql);
        if ($res === false) {
            throw new \RuntimeException("SQL failed: {$sql} :: {$mysqli->error}");
        }
        if ($okMsg) $this->log($okMsg);
    }
}

