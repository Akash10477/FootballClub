<?php /** One product card. Expects $p. */ ?>
<a class="product-card" href="<?= url('product.php?id=' . (int) $p['id']) ?>">
    <div class="thumb">
        <img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
        <span class="pill"><?= e($p['category']) ?></span>
    </div>
    <div class="body">
        <h3><?= e($p['name']) ?></h3>
        <?php if ((int) $p['stock'] === 0): ?>
            <span class="stock-out">Sold out</span>
        <?php elseif ((int) $p['stock'] <= 10): ?>
            <span class="stock-low">Only <?= (int) $p['stock'] ?> left</span>
        <?php else: ?>
            <span class="cat">In stock</span>
        <?php endif; ?>
        <div class="foot">
            <span class="price"><?= money($p['price']) ?></span>
            <span class="link-arrow">View <?= icon('arrow') ?></span>
        </div>
    </div>
</a>
