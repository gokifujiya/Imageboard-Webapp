<?php
use Database\MySQLWrapper;

$mysqli = new MySQLWrapper();

$scripts = [
    __DIR__ . '/Examples/blogbook-setup.sql',
    __DIR__ . '/Examples/blogbook-update-taxonomy-and-subscriptions.sql'
];

foreach ($scripts as $file) {
    if (!file_exists($file)) {
        printf("Skipping %s (not found)\n", $file);
        continue;
    }

    $sql = file_get_contents($file);
    if (!$mysqli->multi_query($sql)) {
        throw new Exception("Error running $file: " . $mysqli->error);
    }

    // 🔑 Always flush all results, even if successful
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());

    printf("Executed %s\n", basename($file));
}

print("Successfully ran BlogBook setup/upgrade." . PHP_EOL);

