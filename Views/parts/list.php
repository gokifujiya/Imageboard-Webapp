<?php
/** @var array $parts */
/** @var string|null $type */
?>
<h2>
  Computer Parts
  <?php if (!empty($type)): ?>
    (Type: <?= htmlspecialchars($type) ?>)
  <?php endif; ?>
</h2>

<?php if (empty($parts)): ?>
  <p>No parts found.</p>
<?php else: ?>
  <ul>
    <?php foreach ($parts as $part): ?>
      <li>
        <strong><?= htmlspecialchars($part->getName()) ?></strong>
        (<?= htmlspecialchars($part->getType()) ?>) -
        <?= htmlspecialchars($part->getBrand()) ?> |
        $<?= htmlspecialchars($part->getMarketPrice()) ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
