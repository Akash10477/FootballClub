<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$categories = ['Kits', 'Training', 'Accessories', 'Souvenirs'];
$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$errors = [];

/* ---------- Delete (hard-delete only if never ordered; otherwise hide) ---------- */
if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    $p = db_one('SELECT * FROM products WHERE id = ?', [$id]);
    if ($p) {
        if (db_value('SELECT 1 FROM order_items WHERE product_id = ? LIMIT 1', [$id])) {
            db_query('UPDATE products SET is_active = 0 WHERE id = ?', [$id]);
            flash('success', "{$p['name']} has past orders, so it was hidden from the store instead of deleted.");
        } else {
            db_query('DELETE FROM products WHERE id = ?', [$id]);
            if (!str_ends_with((string) $p['image'], '.svg')) delete_upload($p['image'], 'products');
            flash('success', "{$p['name']} deleted.");
        }
    }
    redirect('admin/products.php');
}

/* ---------- Create / update ---------- */
if (is_post() && ($_POST['action'] ?? '') === 'save') {
    $data = [
        'name'        => input('name'),
        'category'    => input('category'),
        'description' => input('description'),
        'price'       => input('price'),
        'stock'       => input('stock'),
        'is_active'   => isset($_POST['is_active']) ? 1 : 0,
    ];
    if (mb_strlen($data['name']) < 2) $errors[] = 'Product name is required.';
    if (!in_array($data['category'], $categories, true)) $errors[] = 'Choose a category.';
    if (!is_numeric($data['price']) || $data['price'] <= 0) $errors[] = 'Price must be a positive number.';
    if (!ctype_digit($data['stock'])) $errors[] = 'Stock must be a whole number (0 or more).';
    if (!$id && empty($_FILES['image']['name'])) $errors[] = 'Please upload a product image.';

    try {
        $image = $errors ? null : handle_image_upload('image', 'products');
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }

    if (!$errors) {
        if ($id) {
            $old = db_one('SELECT image FROM products WHERE id = ?', [$id]);
            if ($image) {
                $data['image'] = $image;
                if (!str_ends_with((string) $old['image'], '.svg')) delete_upload($old['image'], 'products');
            }
            $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
            db_query("UPDATE products SET $set WHERE id = ?", [...array_values($data), $id]);
        } else {
            $data['image'] = $image;
            db_query('INSERT INTO products (' . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')', array_values($data));
        }
        flash('success', "{$data['name']} saved.");
        redirect('admin/products.php');
    }
    $action = $id ? 'edit' : 'new';
}

$pageTitle = 'Products';
$active = 'products';

/* ---------- Form ---------- */
if ($action === 'new' || $action === 'edit') {
    $p = $id ? db_one('SELECT * FROM products WHERE id = ?', [$id]) : null;
    if ($action === 'edit' && !$p) redirect('admin/products.php');
    $v = fn($k, $d = '') => e($_POST[$k] ?? $p[$k] ?? $d);
    $pageTitle = $p ? 'Edit product' : 'Add product';
    require __DIR__ . '/../partials/admin_header.php';
    ?>
    <a class="link-arrow" href="<?= url('admin/products.php') ?>" style="margin-bottom:18px"><?= icon('back') ?> All products</a>
    <div class="card form-card">
        <?php if ($errors): ?><div class="alert alert-error"><?= icon('alert') ?><div><?= implode('<br>', array_map('e', $errors)) ?></div></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="form-grid cols-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) $id ?>">
            <div class="field full"><label>Product name</label><input class="input" name="name" value="<?= $v('name') ?>" required></div>
            <div class="field"><label>Category</label>
                <select class="select" name="category"><?php foreach ($categories as $c): ?><option <?= selected($c, $_POST['category'] ?? $p['category'] ?? '') ?>><?= $c ?></option><?php endforeach; ?></select></div>
            <div class="field"><label>Price (৳)</label><input class="input" type="number" step="0.01" min="1" name="price" value="<?= $v('price') ?>" required></div>
            <div class="field"><label>Stock</label><input class="input" type="number" min="0" name="stock" value="<?= $v('stock', 0) ?>" required></div>
            <div class="field" style="justify-content:flex-end"><label class="checkbox"><input type="checkbox" name="is_active" <?= ($_POST ? isset($_POST['is_active']) : ($p['is_active'] ?? 1)) ? 'checked' : '' ?>> Visible in store</label></div>
            <div class="field full"><label>Description</label><textarea class="textarea" name="description"><?= $v('description') ?></textarea></div>
            <div class="field full"><label>Image <span class="faint">(square works best · JPG/PNG/WEBP up to 2 MB)</span></label>
                <div style="display:flex;gap:14px;align-items:center">
                    <img class="preview-img" id="pv" src="<?= $p ? e(product_image($p['image'])) : '' ?>" alt="" <?= $p ? '' : 'hidden' ?>>
                    <input class="input" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-preview="#pv" <?= $p ? '' : 'required' ?>>
                </div></div>
            <div class="form-actions full"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save product</button>
                <a class="btn btn-ghost" href="<?= url('admin/products.php') ?>">Cancel</a></div>
        </form>
    </div>
    <?php
    require __DIR__ . '/../partials/admin_footer.php';
    exit;
}

/* ---------- List ---------- */
$products = db_all('SELECT p.*, COALESCE((SELECT SUM(quantity) FROM order_items WHERE product_id = p.id), 0) AS units_sold
                    FROM products p ORDER BY p.is_active DESC, p.category, p.name');
require __DIR__ . '/../partials/admin_header.php';
?>
<div class="toolbar">
    <span class="muted"><?= count($products) ?> products</span>
    <a class="btn btn-primary" href="?action=new"><?= icon('plus') ?> Add product</a>
</div>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Product</th><th>Category</th><th class="num">Price</th><th class="num">Stock</th><th class="num">Sold</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><div class="cell-main"><img class="thumb-sm" src="<?= e(product_image($p['image'])) ?>" alt=""><div><strong><?= e($p['name']) ?></strong></div></div></td>
                <td><?= e($p['category']) ?></td>
                <td class="num"><?= money($p['price']) ?></td>
                <td class="num"><span class="<?= $p['stock'] == 0 ? 'stock-out' : ($p['stock'] <= 10 ? 'stock-low' : '') ?>"><?= (int) $p['stock'] ?></span></td>
                <td class="num"><?= (int) $p['units_sold'] ?></td>
                <td><span class="pill <?= $p['is_active'] ? 'pill-green' : '' ?>"><?= $p['is_active'] ? 'Live' : 'Hidden' ?></span></td>
                <td><div class="actions">
                    <a class="btn btn-ghost btn-sm" href="?action=edit&id=<?= (int) $p['id'] ?>"><?= icon('edit') ?> Edit</a>
                    <form method="post" data-confirm="Delete <?= e($p['name']) ?>?">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button class="btn btn-danger btn-sm" type="submit" aria-label="Delete"><?= icon('trash') ?></button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
