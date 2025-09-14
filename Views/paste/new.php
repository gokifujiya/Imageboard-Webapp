<?php // Simple form with Monaco editor + language + expiry ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">

<h2>Create Snippet</h2>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form id="snippet-form" method="post" action="/paste/create">
  <div class="mb-3">
    <label class="form-label">Language (optional)</label>
    <select name="language" class="form-select">
      <option value="">Auto/Plain</option>
      <option>javascript</option>
      <option>typescript</option>
      <option>php</option>
      <option>python</option>
      <option>java</option>
      <option>c</option>
      <option>cpp</option>
      <option>go</option>
      <option>rust</option>
      <option>json</option>
      <option>yaml</option>
      <option>markdown</option>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Expires</label>
    <select name="expiry" class="form-select">
      <option value="keep">Keep (No expiry)</option>
      <option value="10m">10 minutes</option>
      <option value="1h">1 hour</option>
      <option value="1d">1 day</option>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Snippet</label>
    <div id="editor" style="height: 300px; border:1px solid #ddd;"></div>
    <textarea id="content" name="content" class="d-none"></textarea>
  </div>

  <button type="submit" class="btn btn-primary">Create</button>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.49.0/min/vs/loader.min.js"></script>
<script>
  // Monaco boot
  require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.49.0/min/vs' }});
  require(['vs/editor/editor.main'], function() {
    const editor = monaco.editor.create(document.getElementById('editor'), {
      value: '',
      language: 'plaintext',
      automaticLayout: true
    });
    document.getElementById('snippet-form').addEventListener('submit', function() {
      document.getElementById('content').value = editor.getValue();
    });
  });
</script>
