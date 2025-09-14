<?php
use Database\MySQLWrapper;
$db = new MySQLWrapper();
$stmt = $db->prepare("SELECT * FROM computer_parts ORDER BY RAND() LIMIT 1");
$stmt->execute();
$part = $stmt->get_result()->fetch_assoc();
if (!$part) { echo "No part found"; exit; }
?>
<h3><?= htmlspecialchars($part['name']) ?></h3>
<p><strong>Type:</strong> <?= htmlspecialchars($part['type']) ?> |
<strong>Brand:</strong> <?= htmlspecialchars($part['brand']) ?></p>
