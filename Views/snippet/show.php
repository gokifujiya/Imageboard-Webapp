<?php
/** @var array $snippet */
$language = $snippet['language'] ?: 'plaintext';
$isExpired = $snippet === null;
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<?php if ($language !== 'plaintext'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/<?= htmlspecialchars($language) ?>.min.js"></script>
<?php endif; ?>
<script>addEventListener('DOMContentLoaded', () => { document.querySelectorAll('pre code').forEach(el => hljs.highlightElement(el)); });</script>

<?php if (!$snippet): ?>
  <div class="alert alert-warning">Expired Snippet</div>
<?php else: ?>
  <h2>Snippet <?= htmlspecialchars($snippet['slug']) ?></h2>
  <p>
    <strong>Language:</strong> <?= htmlspecialchars($language) ?> |
    <strong>Created:</strong> <?= htmlspecialchars($snippet['created_at']) ?> |
    <strong>Expires:</strong> <?= htmlspecialchars($snippet['expires_at'] ?? 'Never') ?>
    &nbsp;|&nbsp;<a href="/raw/<?= htmlspecialchars($snippet['slug']) ?>" target="_blank">Raw</a>
  </p>
  <pre><code class="<?= htmlspecialchars($language) ?>"><?= htmlspecialchars($snippet['content']) ?></code></pre>
<?php endif; ?>
