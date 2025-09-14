<?php
require __DIR__ . '/../init-app.php'; // if you have bootstrap; else include your autoload
use Helpers\ImageHelper;

// Lightweight inline DB same as ImageHelper::db()
$ref = new ReflectionClass(ImageHelper::class);
$method = $ref->getMethod('db');
$method->setAccessible(true);
$mysqli = $method->invoke(null);

// Find expired or stale (>30d no access)
$q = "SELECT id, stored_path FROM images
      WHERE (expires_at IS NOT NULL AND expires_at <= NOW())
         OR (last_accessed_at < (NOW() - INTERVAL 30 DAY))";
$res = $mysqli->query($q);

$ids = [];
while ($row = $res->fetch_assoc()) {
    $ids[] = (int)$row['id'];
    $abs = dirname(__DIR__) . '/' . $row['stored_path'];
    if (is_file($abs)) @unlink($abs);
}
if ($ids) {
    $in = implode(',', $ids);
    $mysqli->query("DELETE FROM images WHERE id IN ($in)");
}
