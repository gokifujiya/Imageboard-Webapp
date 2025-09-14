<?php
namespace Database;

class MySQLWrapper extends \mysqli
{
    public function __construct()
    {
        // Load DB config array like:
        // return ['host'=>'127.0.0.1','username'=>'devuser','password'=>'<YOUR_PASS>','database'=>'practice_db','port'=>3306,'charset'=>'utf8mb4'];
        $configFile = dirname(__DIR__) . '/config/database.php';
        if (!is_file($configFile)) {
            throw new \RuntimeException("DB config not found at {$configFile}");
        }
        $cfg = include $configFile;

        $host     = $cfg['host']     ?? '127.0.0.1';
        $user     = $cfg['username'] ?? 'devuser';
        $pass     = $cfg['password'] ?? 't';
        $db       = $cfg['database'] ?? 'practice_db';
        $port     = (int)($cfg['port'] ?? 3306);
        $charset  = $cfg['charset'] ?? 'utf8mb4';

        // Throw exceptions on errors
        \mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        parent::__construct($host, $user, $pass, $db, $port);
        $this->set_charset($charset);
    }

    /** For compatibility with earlier code */
    public function get_charset(): ?string
    {
        try {
            return $this->character_set_name();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
