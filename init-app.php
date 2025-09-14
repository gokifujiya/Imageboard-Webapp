<?php
require __DIR__ . '/vendor/autoload.php';

use Database\MySQLWrapper;

$opts = getopt('', ['migrate']);

if (isset($opts['migrate'])) {
    echo "Database migration enabled.\n";
    require __DIR__ . '/Database/setup.php';
    echo "Database migration ended.\n";
}

try {
    $mysqli = new MySQLWrapper();

    $charset = $mysqli->get_charset();
    if ($charset === null) {
        throw new Exception('Charset could not be read');
    }

    printf("%s's charset: %s\n", $mysqli->getDatabaseName(), $charset->charset);
    printf("collation: %s\n", $charset->collation);

    $mysqli->close();
} catch (Throwable $e) {
    echo "Connection failed: " . $e->getMessage() . PHP_EOL;
}
