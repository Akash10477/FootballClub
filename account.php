<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$tickets = db_all(
    "SELECT o.*, t.category, f.opponent, f.is_home, f.kickoff, f.venue
     FROM ticket_orders o
     JOIN ticket_types t ON t.id = o.ticket_type_id
     JOIN fixtures f ON f.id = t.fixture_id
     WHERE o.user_id = ? ORDER BY f.kickoff DESC",
    [$user['id']]
);
$orders = db_all('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC', [$user['id']]);
$items = [];
if ($orders) {
    $ids = array_column($orders, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    foreach (db_all("SELECT * FROM order_items WHERE order_id IN ($in)", $ids) as $it) {
        $items[$it['order_id']][] = $it;
    }
}
$statusPill = ['pending' => 'pill-gold', 'paid' => 'pill-teal', 'shipped' => 'pill-teal', 'delivered' => 'pill-green', 'cancelled' => 'pill-red'];

$pageTitle = 'My account';
require __DIR__ . '/partials/header.php';
?>
<section class="page-hero" style="padding:70px 0 44px">
    <div class="container" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
        <span class="avatar" style="width:72px;height:72px;font-size:1.5rem"><?= e(initials($user['name'])) ?></span>
        <div>
            <div class="eyebrow" style="margin-bottom:8px">Member since <?= fmt_date($user['created_at'], 'F Y') ?></div>
            <h1 style="font-size:2.4rem;margin:0"><?= e($user['name']) ?></h1>
            <span class="muted"><?= e($user['email']) ?><?= $user['phone'] ? ' · ' . e($user['phone']) : '' ?></span>
        </div>
    </div>
</section>

<section class="section" style="padding-top:48px">
    <div class="container">
        <div class="grid grid-3" style="margin-bottom:44px">
            <div class="stat-card"><div class="ico"><?= icon('ticket') ?></div><div><span>Bookings</span><strong><?= count($tickets) ?></strong></div></div>
            <div class="stat-card"><div class="ico teal"><?= icon('package') ?></div><div><span>Store orders</span><strong><?= count($orders) ?></strong></div></div>
            <div class="stat-card"><div class="ico green"><?= icon('coins') ?></div><div><span>Total spent</span><strong><?= money(array_sum(array_column($tickets, 'total')) + array_sum(array_column($orders, 'total'))) ?></strong></div></div>
        </div>

        <div class="section-head" id="tickets"><div><h2>My tickets</h2></div><a class="link-arrow" href="<?= url('tickets.php') ?>">Book more <?= icon('arrow') ?></a></div>
        <?php if ($tickets): ?>
            <div class="table-wrap" style="margin-bottom:56px">
                <table class="table">
                    <thead><tr><th>Match</th><th>Date</th><th>Section</th><th class="num">Qty</th><th class="num">Paid</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($tickets as $t): $past = strtotime($t['kickoff']) < time(); ?>
                        <tr>
                            <td><div class="cell-main"><div><strong><?= $t['is_home'] ? 'vs ' : '@ ' ?><?= e($t['opponent']) ?></strong><small><?= e($t['reference']) ?></small></div></div></td>
                            <td class="nowrap"><?= fmt_date($t['kickoff'], 'j M Y') ?> <span class="muted"><?= fmt_time($t['kickoff']) ?></span></td>
                            <td><span class="pill"><?= e($t['category']) ?></span></td>
                            <td class="num"><?= (int) $t['quantity'] ?></td>
                            <td class="num nowrap"><?= money($t['total']) ?></td>
                            <td class="num"><?php if ($past): ?><span class="pill">Played</span><?php else: ?><a class="btn btn-ghost btn-sm" href="<?= url('ticket.php?ref=' . urlencode($t['reference'])) ?>"><?= icon('eye') ?> E-ticket</a><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card empty" style="margin-bottom:56px"><?= icon('ticket') ?>No tickets yet. <a class="gold" href="<?= url('tickets.php') ?>">Book your first match</a>.</div>
        <?php endif; ?>

        <div class="section-head" id="orders"><div><h2>Store orders</h2></div><a class="link-arrow" href="<?= url('shop.php') ?>">Shop <?= icon('arrow') ?></a></div>
        <?php if ($orders): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Order</th><th>Items</th><th>Placed</th><th>Status</th><th class="num">Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong><?= e($o['reference']) ?></strong></td>
                            <td><?= implode(', ', array_map(fn($i) => e($i['product_name']) . ' ×' . (int) $i['quantity'], $items[$o['id']] ?? [])) ?></td>
                            <td class="nowrap"><?= fmt_date($o['created_at'], 'j M Y') ?></td>
                            <td><span class="pill <?= $statusPill[$o['status']] ?? '' ?>"><?= e($o['status']) ?></span></td>
                            <td class="num nowrap"><?= money($o['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card empty"><?= icon('package') ?>No orders yet.</div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
