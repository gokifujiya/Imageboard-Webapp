<h2><?= htmlspecialchars($thread->getSubject() ?? 'No subject') ?></h2>
<p><?= nl2br(htmlspecialchars($thread->getContent())) ?></p>
<small>Posted at <?= htmlspecialchars($thread->getCreatedAt()) ?></small>

<h3>Replies</h3>
<?php foreach ($replies as $reply): ?>
    <div class="reply">
        <p><?= nl2br(htmlspecialchars($reply->getContent())) ?></p>
        <?php if ($reply->getImagePath()): ?>
            <img src="/uploads/<?= htmlspecialchars($reply->getImagePath()) ?>" width="150" />
        <?php endif; ?>
        <small>Reply at <?= htmlspecialchars($reply->getCreatedAt()) ?></small>
    </div>
<?php endforeach; ?>

