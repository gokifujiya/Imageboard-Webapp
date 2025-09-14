<?php
namespace Commands\Programs;

use Commands\AbstractCommand;
use Commands\Argument;
use Database\MySQLWrapper;

class SyncSchema extends AbstractCommand
{
    protected static ?string $alias = 'sync-schema';

    public static function getArguments(): array
    {
        return [
            (new Argument('dry-run'))->description('Show what would change, but do not apply.')->required(false)->allowAsShort(true),
        ];
    }

    public function execute(): int
    {
        $this->log("Loading schema state...");

        $stateFile = __DIR__ . '/../../Database/state.php';
        if (!is_file($stateFile)) {
            $this->log("Missing state file: $stateFile");
            $this->log("Create it first (see Database/state.php example), then re-run.");
            return 1;
        }

        /** @var array $state */
        $state = include $stateFile;
        if (!is_array($state)) {
            $this->log("State file must return an array.");
            return 1;
        }

        $dryRun = $this->getArgumentValue('dry-run') !== false;
        $db = new MySQLWrapper();

        // Drop all tables safely
        $this->log("Dropping all tables...");
        $db->query("SET FOREIGN_KEY_CHECKS=0");
        $tablesRes = $db->query("SHOW TABLES");
        while ($row = $tablesRes->fetch_array()) {
            $tbl = $row[0];
            $db->query("DROP TABLE IF EXISTS `{$tbl}`");
            $this->log("Dropped {$tbl}");
        }
        $db->query("SET FOREIGN_KEY_CHECKS=1");

        // Recreate according to desired state
        $this->log("Recreating schema from state...");
        $sqls = $this->buildCreateSqlFromState($state);

        if ($dryRun) {
            $this->log("--- DRY RUN --- would execute:");
            foreach ($sqls as $s) $this->log($s . ";");
            return 0;
        }

        foreach ($sqls as $s) {
            if ($db->query($s) === false) {
                throw new \RuntimeException("Failed: {$s} :: ".$db->error);
            }
            $this->log("OK: {$s}");
        }

        $this->log("State sync complete.");
        return 0;
    }

    /**
     * Super-simple translator from the state array to CREATE TABLE DDL.
     * Assumes: MySQL, no quoting of identifiers in state keys, basic types, and
     * FK references by table/column names as given in the state.
     */
    private function buildCreateSqlFromState(array $state): array
    {
        $ddl = [];

        // First create all tables (without FKs)
        foreach ($state as $table => $cols) {
            $colsSql = [];
            $pks = [];
            foreach ($cols as $name => $def) {
                $parts = [];
                $parts[] = "`{$name}` {$def['dataType']}";
                if (!empty($def['constraints'])) $parts[] = $def['constraints'];
                $parts[] = ($def['nullable'] ?? false) ? "NULL" : "NOT NULL";
                $colsSql[] = implode(' ', $parts);
                if (!empty($def['primaryKey'])) $pks[] = "`{$name}`";
            }
            if ($pks) $colsSql[] = "PRIMARY KEY (" . implode(',', $pks) . ")";
            $ddl[] = "CREATE TABLE `{$table}` (" . implode(",\n  ", $colsSql) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }

        // Then add FKs
        foreach ($state as $table => $cols) {
            foreach ($cols as $name => $def) {
                if (!empty($def['foreignKey'])) {
                    $fk = $def['foreignKey'];
                    $onDelete = !empty($fk['onDelete']) ? " ON DELETE {$fk['onDelete']}" : "";
                    $ddl[] = "ALTER TABLE `{$table}` ADD CONSTRAINT `fk_{$table}_{$name}` FOREIGN KEY (`{$name}`) REFERENCES `{$fk['referenceTable']}`(`{$fk['referenceColumn']}`){$onDelete}";
                }
            }
        }

        return $ddl;
    }
}

