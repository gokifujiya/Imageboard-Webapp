<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<h2>Upload Image</h2>

<form method="post" action="/images/upload" enctype="multipart/form-data" class="mb-4">
  <div class="mb-3">
    <label class="form-label">Choose image (JPEG/PNG/GIF, max 5MB)</label>
    <input type="file" name="image" accept="image/*" class="form-control" required>
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

  <button type="submit" class="btn btn-primary">Upload</button>
</form>
