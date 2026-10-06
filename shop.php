<?php
require __DIR__ . '/includes/bootstrap.php';

$categories = ['Kits', 'Training', 'Accessories', 'Souvenirs'];
$cat = in_array($_GET['category'] ?? '', $categories, true) ? $_GET['category'] : '';
$q   = trim($_GET['q'] ?? '');

$where = ['is_active = 1'];
$params = [];
if ($cat) { $where[] = 'category = ?'; $params[] = $cat; }
if ($q !== '') { $where[] = '(name LIKE ? OR description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
$products = db_all('SELECT * FROM products WHERE ' . implode(' AND ', $where) . ' ORDER BY stock = 0, category, name', $params);

$pageTitle = 'Official store';
$active = 'shop';
require __DIR__ . '/partials/header.php';
?>
<section class="page-hero">
    <div class="hero-bg" style="background-image:url('<?= asset('img/hero-stadium.jpg') ?>')"></div>
    <div class="container">
        <div class="eyebrow">Official store</div>
        <h1>Wear the colours</h1>
        <p>Authentic kits, training wear and souvenirs. Cash on delivery anywhere in Bangladesh.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="toolbar">
            <div class="tabs" style="margin:0">
                <a class="tab <?= $cat === '' ? 'active' : '' ?>" href="<?= url('shop.php') ?>">All</a>
                <?php foreach ($categories as $c): ?>
                    <a class="tab <?= $cat === $c ? 'active' : '' ?>" href="<?= url('shop.php?category=' . urlencode($c)) ?>"><?= e($c) ?></a>
                <?php endforeach; ?>
            </div>
            <form method="get" role="search">
                <?php if ($cat): ?><input type="hidden" name="category" value="<?= e($cat) ?>"><?php endif; ?>
                <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search products…" aria-label="Search products">
                <button class="btn btn-ghost" type="submit" aria-label="Search"><?= icon('search') ?></button>
            </form>
        </div>

        <?php if ($products): ?>
            <div class="grid grid-4">
                <?php foreach ($products as $p) { require __DIR__ . '/partials/product_card.php'; } ?>
            </div>
        <?php else: ?>
            <div class="card empty"><?= icon('shirt') ?>No products match your search.</div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
