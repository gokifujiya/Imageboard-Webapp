<?php /** @var \Models\ComputerPart|null $part */ ?>
<div class="col-12">
    <form action="#" method="post" id="update-part-form" class="d-flex row">
        <?php if ($part?->getId() !== null): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$part->getId()) ?>"><br>
        <?php endif; ?>

        <input type="text" name="name" value="<?= htmlspecialchars($part?->getName() ?? '') ?>" placeholder="Name" required><br>
        <input type="text" name="type" value="<?= htmlspecialchars($part?->getType() ?? '') ?>" placeholder="Type" required><br>
        <input type="text" name="brand" value="<?= htmlspecialchars($part?->getBrand() ?? '') ?>" placeholder="Brand" required><br>
        <input type="text" name="modelNumber" value="<?= htmlspecialchars($part?->getModelNumber() ?? '') ?>" placeholder="Model Number" required><br>
        <input type="text" name="releaseDate" value="<?= htmlspecialchars($part?->getReleaseDate() ?? '') ?>" placeholder="Release Date (YYYY-MM-DD)" required><br>
        <textarea name="description" placeholder="Description" required><?= htmlspecialchars($part?->getDescription() ?? '') ?></textarea><br>

        <input type="number" name="performanceScore" value="<?= htmlspecialchars((string)($part?->getPerformanceScore() ?? '')) ?>" placeholder="Performance Score" required><br>
        <input type="number" step="0.01" name="marketPrice" value="<?= htmlspecialchars((string)($part?->getMarketPrice() ?? '')) ?>" placeholder="Market Price" required><br>
        <input type="number" step="0.01" name="rsm" value="<?= htmlspecialchars((string)($part?->getRsm() ?? '')) ?>" placeholder="RSM" required><br>
        <input type="number" step="0.1" name="powerConsumptionW" value="<?= htmlspecialchars((string)($part?->getPowerConsumptionW() ?? '')) ?>" placeholder="Power Consumption (W)" required><br>

        <label>Dimensions (L x W x H):</label><br>
        <input type="number" step="0.001" name="lengthM" value="<?= htmlspecialchars((string)($part?->getLengthM() ?? '')) ?>" placeholder="Length (meters)" required>
        <input type="number" step="0.001" name="widthM"  value="<?= htmlspecialchars((string)($part?->getWidthM()  ?? '')) ?>" placeholder="Width (meters)" required>
        <input type="number" step="0.001" name="heightM" value="<?= htmlspecialchars((string)($part?->getHeightM() ?? '')) ?>" placeholder="Height (meters)" required><br>

        <input type="number" name="lifespan" value="<?= htmlspecialchars((string)($part?->getLifespan() ?? '')) ?>" placeholder="Lifespan (years)" required><br>

        <input type="submit" value="Save Part">
    </form>
</div>

<script src="/js/app.js"></script>
