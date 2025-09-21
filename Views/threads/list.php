<h1>Threads</h1>
<?php foreach ($threads as $thread): ?>
    <div>
        <a href="/thread?id=<?= $thread->getId() ?>">
            <?= htmlspecialchars($thread->getSubject() ?? "No Subject") ?>
        </a>
        <p><?= nl2br(htmlspecialchars($thread->getContent())) ?></p>
    </div>
<?php endforeach; ?>

