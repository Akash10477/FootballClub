<?php
require __DIR__ . '/includes/bootstrap.php';

$p = db_one('SELECT * FROM products WHERE id = ? AND is_active = 1', [(int) ($_GET['id'] ?? 0)]);
if (!$p) {
    http_response_code(404);
    flash('error', 'That product is no longer available.');
    redirect('shop.php');
}
$inCart  = cart()[$p['id']] ?? 0;
$maxAdd  = max(0, (int) $p['stock'] - $inCart);
$related = db_all('SELECT * FROM products WHERE is_active = 1 AND id <> ? ORDER BY category = ? DESC, RAND() LIMIT 4', [$p['id'], $p['category']]);

$pageTitle = $p['name'];
$active = 'shop';
require __DIR__ . '/partials/header.php';
?>
<section class="section">
    <div class="container">
        <a class="link-arrow" href="<?= url('shop.php') ?>" style="margin-bottom:24px"><?= icon('back') ?> Back to shop</a>
        <div class="product-detail">
            <div class="gallery"><img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['name']) ?>"></div>
            <div>
                <span class="pill pill-teal"><?= e($p['category']) ?></span>
                <h1 style="margin-top:14px"><?= e($p['name']) ?></h1>
                <div class="price" style="font-size:2rem;margin-bottom:16px"><?= money($p['price']) ?></div>
                <p class="muted" style="font-size:1.02rem"><?= nl2br(e($p['description'])) ?></p>

                <?php if ((int) $p['stock'] === 0): ?>
                    <p class="stock-out" style="font-size:.95rem">Sold out. Check back soon.</p>
                <?php elseif ($maxAdd === 0): ?>
                    <p class="stock-low">You already have all available stock in your cart.</p>
                    <a class="btn btn-primary" href="<?= url('cart.php') ?>">Go to cart <?= icon('arrow') ?></a>
                <?php else: ?>
                    <p class="<?= $p['stock'] <= 10 ? 'stock-low' : 'muted' ?>" style="font-size:.9rem">
                        <?= $p['stock'] <= 10 ? 'Only ' . (int) $p['stock'] . ' left in stock' : 'In stock and ready to ship' ?>
                    </p>
                    <form method="post" action="<?= url('cart.php') ?>" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:18px">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                        <div class="qty">
                            <button type="button" data-step="-1" aria-label="Fewer"><?= icon('minus') ?></button>
                            <input type="number" name="quantity" value="1" min="1" max="<?= $maxAdd ?>" aria-label="Quantity">
                            <button type="button" data-step="1" aria-label="More"><?= icon('plus') ?></button>
                        </div>
                        <button class="btn btn-primary btn-lg" type="submit"><?= icon('cart') ?> Add to cart</button>
                    </form>
                <?php endif; ?>

                <div class="perks">
                    <div><?= icon('package') ?> Delivery across Bangladesh in 2–5 working days</div>
                    <div><?= icon('coins') ?> Cash on delivery available</div>
                    <div><?= icon('shield') ?> 100% official FC Scorpio merchandise</div>
                </div>
            </div>
        </div>

        <?php if ($related): ?>
            <div class="section-head" style="margin-top:80px"><div><h2>You may also like</h2></div></div>
            <div class="grid grid-4">
                <?php foreach ($related as $p) { require __DIR__ . '/partials/product_card.php'; } ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
