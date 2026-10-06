<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$statuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
$pill = ['pending' => 'pill-gold', 'paid' => 'pill-teal', 'shipped' => 'pill-teal', 'delivered' => 'pill-green', 'cancelled' => 'pill-red'];

/* ---------- Update status (cancelling puts stock back) ---------- */
if (is_post()) {
    $id = (int) ($_POST['id'] ?? 0);
    $new = $_POST['status'] ?? '';
    $order = db_one('SELECT * FROM orders WHERE id = ?', [$id]);
    if ($order && in_array($new, $statuses, true) && $new !== $order['status']) {
        if ($order['status'] === 'cancelled') {
            flash('error', 'Cancelled orders cannot be reopened.');
        } else {
            db_transaction(function () use ($order, $new) {
                db_query('UPDATE orders SET status = ? WHERE id = ?', [$new, $order['id']]);
                if ($new === 'cancelled') {
                    db_query('UPDATE products p JOIN order_items i ON i.product_id = p.id
                              SET p.stock = p.stock + i.quantity WHERE i.order_id = ?', [$order['id']]);
                }
            });
            flash('success', "Order {$order['reference']} marked as $new." . ($new === 'cancelled' ? ' Stock was returned.' : ''));
        }
    }
    redirect('admin/orders.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

$filter = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$counts = array_column(db_all('SELECT status, COUNT(*) AS n FROM orders GROUP BY status'), 'n', 'status');
$total  = (int) db_value('SELECT COUNT(*) FROM orders' . ($filter ? ' WHERE status = ?' : ''), $filter ? [$filter] : []);
$pg     = paginate($total, 15, (int) ($_GET['page'] ?? 1));
$orders = db_all(
    'SELECT o.*, u.email FROM orders o JOIN users u ON u.id = o.user_id'
    . ($filter ? ' WHERE o.status = ?' : '') . " ORDER BY o.created_at DESC LIMIT {$pg['limit']} OFFSET {$pg['offset']}",
    $filter ? [$filter] : []
);
$items = [];
if ($orders) {
    $ids = array_column($orders, 'id');
    foreach (db_all('SELECT * FROM order_items WHERE order_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids) as $it) {
        $items[$it['order_id']][] = $it;
    }
}

$pageTitle = 'Store orders';
$active = 'orders';
require __DIR__ . '/../partials/admin_header.php';
?>
<div class="tabs">
    <a class="tab <?= !$filter ? 'active' : '' ?>" href="?">All (<?= array_sum($counts) ?>)</a>
    <?php foreach ($statuses as $s): ?>
        <a class="tab <?= $filter === $s ? 'active' : '' ?>" href="?status=<?= $s ?>"><?= ucfirst($s) ?> (<?= (int) ($counts[$s] ?? 0) ?>)</a>
    <?php endforeach; ?>
</div>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Order</th><th>Customer &amp; delivery</th><th>Items</th><th class="num">Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td class="nowrap"><strong><?= e($o['reference']) ?></strong><br><small class="muted"><?= fmt_date($o['created_at'], 'j M Y, g:i A') ?></small></td>
                <td><strong><?= e($o['shipping_name']) ?></strong> · <?= e($o['shipping_phone']) ?><br><small class="muted"><?= e($o['shipping_address']) ?></small></td>
                <td><?php foreach ($items[$o['id']] ?? [] as $i): ?><div><?= e($i['product_name']) ?> <span class="muted">×<?= (int) $i['quantity'] ?></span></div><?php endforeach; ?></td>
                <td class="num nowrap"><?= money($o['total']) ?></td>
                <td>
                    <?php if ($o['status'] === 'cancelled'): ?>
                        <span class="pill pill-red">Cancelled</span>
                    <?php else: ?>
                    <form method="post" data-confirm="Update this order's status?">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                        <select class="select" name="status" onchange="this.form.requestSubmit()" style="padding:7px 34px 7px 10px;min-width:130px">
                            <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= selected($s, $o['status']) ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
                        </select>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="5" class="empty">No orders found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($pg['pages'] > 1): ?>
<nav class="pagination">
    <?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
        <?= $i === $pg['page'] ? "<span class=\"current\">$i</span>" : '<a href="?' . e(http_build_query(['status' => $filter, 'page' => $i])) . "\">$i</a>" ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
