<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$stats = [
    'fans'         => (int) db_value("SELECT COUNT(*) FROM users WHERE role = 'fan'"),
    'ticket_rev'   => (float) db_value('SELECT COALESCE(SUM(total), 0) FROM ticket_orders'),
    'tickets_sold' => (int) db_value('SELECT COALESCE(SUM(quantity), 0) FROM ticket_orders'),
    'store_rev'    => (float) db_value("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status <> 'cancelled'"),
    'pending'      => (int) db_value("SELECT COUNT(*) FROM orders WHERE status = 'pending'"),
    'low_stock'    => (int) db_value('SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock <= 10'),
];
$upcoming = db_all(
    "SELECT f.*, COALESCE(SUM(t.sold), 0) AS sold, COALESCE(SUM(t.capacity), 0) AS capacity
     FROM fixtures f LEFT JOIN ticket_types t ON t.fixture_id = f.id
     WHERE f.status = 'scheduled' AND f.kickoff > NOW()
     GROUP BY f.id ORDER BY f.kickoff LIMIT 5"
);
$recentOrders = db_all(
    "SELECT o.reference, o.total, o.status, o.created_at, u.name, 'Store' AS kind FROM orders o JOIN users u ON u.id = o.user_id
     UNION ALL
     SELECT o.reference, o.total, 'paid', o.created_at, u.name, 'Tickets' FROM ticket_orders o JOIN users u ON u.id = o.user_id
     ORDER BY created_at DESC LIMIT 8"
);
$lowStock = db_all('SELECT id, name, stock, image FROM products WHERE is_active = 1 AND stock <= 10 ORDER BY stock LIMIT 5');

$pageTitle = 'Dashboard';
$active = 'dashboard';
require __DIR__ . '/../partials/admin_header.php';
?>
<div class="grid grid-4" style="margin-bottom:26px">
    <div class="stat-card"><div class="ico"><?= icon('coins') ?></div><div><span>Ticket revenue</span><strong><?= money($stats['ticket_rev']) ?></strong><span><?= number_format($stats['tickets_sold']) ?> online bookings</span></div></div>
    <div class="stat-card"><div class="ico teal"><?= icon('package') ?></div><div><span>Store revenue</span><strong><?= money($stats['store_rev']) ?></strong><span><?= $stats['pending'] ?> order(s) pending</span></div></div>
    <div class="stat-card"><div class="ico green"><?= icon('users') ?></div><div><span>Registered fans</span><strong><?= number_format($stats['fans']) ?></strong><span>Members</span></div></div>
    <div class="stat-card"><div class="ico red"><?= icon('alert') ?></div><div><span>Low / out of stock</span><strong><?= $stats['low_stock'] ?></strong><span>Products at 10 or fewer</span></div></div>
</div>

<div class="grid" style="grid-template-columns:1.4fr 1fr;align-items:start">
    <div class="card">
        <div class="card-title"><h3>Upcoming matches</h3><a class="link-arrow" href="<?= url('admin/fixtures.php') ?>">Manage <?= icon('arrow') ?></a></div>
        <?php if ($upcoming): ?>
        <div class="table-wrap" style="border:0;background:none">
            <table class="table">
                <thead><tr><th>Match</th><th>Kick-off</th><th style="width:36%">Seats sold</th></tr></thead>
                <tbody>
                <?php foreach ($upcoming as $f): $pct = $f['capacity'] ? round($f['sold'] / $f['capacity'] * 100) : 0; ?>
                    <tr>
                        <td><strong><?= $f['is_home'] ? 'vs' : '@' ?> <?= e($f['opponent']) ?></strong><br><small class="muted"><?= e($f['competition']) ?></small></td>
                        <td class="nowrap"><?= fmt_date($f['kickoff'], 'j M') ?> · <?= fmt_time($f['kickoff']) ?></td>
                        <td>
                            <?php if ($f['capacity']): ?>
                                <div class="bar"><span style="width:<?= $pct ?>%"></span></div>
                                <small class="muted"><?= number_format($f['sold']) ?> / <?= number_format($f['capacity']) ?> (<?= $pct ?>%)</small>
                            <?php else: ?><small class="faint">No tickets on sale</small><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?><div class="empty">No upcoming fixtures.</div><?php endif; ?>
    </div>

    <div class="card">
        <div class="card-title"><h3>Stock alerts</h3><a class="link-arrow" href="<?= url('admin/products.php') ?>">Products <?= icon('arrow') ?></a></div>
        <?php foreach ($lowStock as $p): ?>
            <div class="cell-main" style="padding:10px 0;border-bottom:1px solid var(--border)">
                <img class="thumb-sm" src="<?= e(product_image($p['image'])) ?>" alt="" style="width:40px;height:40px;border-radius:10px">
                <div style="flex:1"><strong><?= e($p['name']) ?></strong></div>
                <span class="pill <?= $p['stock'] == 0 ? 'pill-red' : 'pill-gold' ?>"><?= $p['stock'] == 0 ? 'Out' : (int) $p['stock'] . ' left' ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (!$lowStock): ?><div class="empty" style="padding:20px">All products well stocked.</div><?php endif; ?>
    </div>
</div>

<div class="card" style="margin-top:22px">
    <div class="card-title"><h3>Recent activity</h3><a class="link-arrow" href="<?= url('admin/orders.php') ?>">All orders <?= icon('arrow') ?></a></div>
    <div class="table-wrap" style="border:0;background:none">
        <table class="table">
            <thead><tr><th>Reference</th><th>Type</th><th>Customer</th><th>Date</th><th>Status</th><th class="num">Amount</th></tr></thead>
            <tbody>
            <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td><strong><?= e($o['reference']) ?></strong></td>
                    <td><span class="pill <?= $o['kind'] === 'Tickets' ? 'pill-gold' : 'pill-teal' ?>"><?= e($o['kind']) ?></span></td>
                    <td><?= e($o['name']) ?></td>
                    <td class="nowrap"><?= fmt_date($o['created_at'], 'j M, g:i A') ?></td>
                    <td><?= e(ucfirst($o['status'])) ?></td>
                    <td class="num"><?= money($o['total']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recentOrders): ?><tr><td colspan="6" class="empty">No activity yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
