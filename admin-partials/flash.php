<?php $flashes = get_flashes(); ?>
<?php if (!empty($flashes)): ?>
<div class="flash-messages">
    <?php foreach ($flashes as $flash): ?>
    <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>">
        <?= htmlspecialchars($flash['message']) ?>
        <button class="flash__close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
