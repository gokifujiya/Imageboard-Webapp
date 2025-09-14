<?php
require __DIR__.'/../vendor/autoload.php';

$db = new Database\MySQLWrapper();

// Find images not viewed for 30 days (or never viewed and older than 30 days)
$sql = "SELECT id, storage_path FROM images
        WHERE ( (last_view_at IS NOT NULL AND last_view_at < (NOW() - INTERVAL 30 DAY))
                OR (last_view_at IS NULL AND created_at < (NOW() - INTERVAL 30 DAY)) )";
$res = $db->query($sql);
while ($row = $res->fetch_assoc()) {
    $abs = __DIR__ . '/../public/' . $row['storage_path'];
    if (is_file($abs)) @unlink($abs);
    $db->query("DELETE FROM images WHERE id=".(int)$row['id']);
}
