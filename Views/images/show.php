<?php /** @var array $image */ ?>
<h2>Your Image</h2>

<p><strong>Views:</strong> <?= (int)$image['views'] ?></p>

<div class="mb-3">
  <img src="/<?= htmlspecialchars($image['stored_path'], ENT_QUOTES) ?>" alt="uploaded" class="img-fluid">
</div>

<div class="card p-3">
  <p class="mb-1"><strong>Share URL:</strong>
    <code><?= '/' . htmlspecialchars($image['media_type'], ENT_QUOTES) . '/' . htmlspecialchars($image['slug'], ENT_QUOTES) ?></code>
  </p>
  <p class="mb-0"><strong>Delete URL:</strong>
    <code><?= '/d/' . htmlspecialchars($image['delete_token'], ENT_QUOTES) ?></code>
  </p>
</div>

