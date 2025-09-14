<?php
namespace Commands\Programs;

use Commands\AbstractCommand;
use Commands\Argument;
use App\Helpers\Settings;

class DbWipe extends AbstractCommand
{
    protected static ?string $alias = 'db-wipe';

    public static function getArguments(): array
    {
        return [
            (new Argument('yes'))
                ->description('Confirm destructive wipe. Required.')
                ->required(false)
                ->allowAsShort(true),
            (new Argument('backup'))
                ->description('Create a mysqldump before wiping. Optional path: --backup /path/file.sql')
                ->required(false)
                ->allowAsShort(true),
        ];
    }

    public function execute(): int
    {
        // Require explicit confirmation
        $confirmed = $this->getArgumentValue('yes');
        if ($confirmed === false) {
            $this->log("Refusing to wipe. Pass --yes to confirm.");
            return 1;
        }

        $db   = Settings::env('DATABASE_NAME');
        $user = Settings::env('DATABASE_USER');
        $pass = Settings::env('DATABASE_USER_PASSWORD');
        $host = 'localhost';

        // Optional backup
        $backupOpt = $this->getArgumentValue('backup');
        if ($backupOpt !== false) {
            $outfile = (is_string($backupOpt) && $backupOpt !== '1' && $backupOpt !== 'true')
                ? $backupOpt
                : (getcwd() . "/backups/{$db}_" . date('Ymd_His') . ".sql");

            $dir = dirname($outfile);
            if (!is_dir($dir)) mkdir($dir, 0775, true);

            $cmd = sprintf(
                "mysqldump -h %s -u %s -p%s %s > %s 2>&1",
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($db),
                escapeshellarg($outfile)
            );
            $this->log("Backing up to: $outfile");
            $out = shell_exec($cmd);
            if ($out) $this->log(trim($out));
        }

        // Use base mysqli WITHOUT selecting a database
        $mysqli = new \mysqli($host, $user, $pass);
        if ($mysqli->connect_errno) {
            $this->log("Connect error: {$mysqli->connect_error}");
            return 2;
        }

        $this->log("Dropping database `$db`...");
        $mysqli->query("DROP DATABASE IF EXISTS `{$db}`");

        $this->log("Recreating database `$db`...");
        $mysqli->query("CREATE DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");

        $mysqli->close();
        $this->log("Done.");
        return 0;
    }
}
