<?php $flashes = pull_flashes(); if ($flashes): ?>
<div class="flash-stack" role="status" aria-live="polite">
    <?php foreach ($flashes as $f):
        $ico = ['success' => 'check', 'error' => 'alert'][$f['type']] ?? 'info'; ?>
        <div class="flash flash-<?= e($f['type']) ?>">
            <?= icon($ico) ?>
            <div><?= e($f['message']) ?></div>
            <button type="button" aria-label="Dismiss"><?= icon('x') ?></button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
