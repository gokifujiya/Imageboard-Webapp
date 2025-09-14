<?php if ($ok): ?>
<div class="alert alert-success">Image deleted.</div>
<?php else: ?>
<div class="alert alert-danger">Invalid delete link or already deleted.</div>
<?php endif; ?>

<p><a href="/images/upload" class="btn btn-secondary">Upload another</a></p>
