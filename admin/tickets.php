<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$fixtures = db_all(
    "SELECT f.id, f.opponent, f.is_home, f.kickoff FROM fixtures f
     WHERE EXISTS (SELECT 1 FROM ticket_types t WHERE t.fixture_id = f.id) ORDER BY f.kickoff DESC"
);
$fixtureId = (int) ($_GET['fixture'] ?? 0);
$q = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($fixtureId) { $where[] = 'f.id = ?'; $params[] = $fixtureId; }
if ($q !== '') { $where[] = '(o.reference LIKE ? OR u.name LIKE ? OR u.email LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$base = "FROM ticket_orders o JOIN ticket_types t ON t.id = o.ticket_type_id JOIN fixtures f ON f.id = t.fixture_id JOIN users u ON u.id = o.user_id $whereSql";
$summary = db_one("SELECT COUNT(*) AS bookings, COALESCE(SUM(o.quantity), 0) AS seats, COALESCE(SUM(o.total), 0) AS revenue $base", $params);
$pg = paginate((int) $summary['bookings'], 20, (int) ($_GET['page'] ?? 1));
$rows = db_all("SELECT o.*, t.category, f.opponent, f.is_home, f.kickoff, u.name, u.email $base ORDER BY o.created_at DESC LIMIT {$pg['limit']} OFFSET {$pg['offset']}", $params);

$pageTitle = 'Ticket sales';
$active = 'tickets';
require __DIR__ . '/../partials/admin_header.php';
?>
<div class="grid grid-3" style="margin-bottom:22px">
    <div class="stat-card"><div class="ico"><?= icon('ticket') ?></div><div><span>Bookings</span><strong><?= number_format($summary['bookings']) ?></strong></div></div>
    <div class="stat-card"><div class="ico teal"><?= icon('users') ?></div><div><span>Seats booked online</span><strong><?= number_format($summary['seats']) ?></strong></div></div>
    <div class="stat-card"><div class="ico green"><?= icon('coins') ?></div><div><span>Revenue</span><strong><?= money($summary['revenue']) ?></strong></div></div>
</div>
<div class="toolbar">
    <form method="get">
        <select class="select" name="fixture" onchange="this.form.submit()" aria-label="Filter by match">
            <option value="">All matches</option>
            <?php foreach ($fixtures as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= selected($f['id'], $fixtureId) ?>><?= fmt_date($f['kickoff'], 'j M') ?> · <?= $f['is_home'] ? 'vs' : '@' ?> <?= e($f['opponent']) ?></option>
            <?php endforeach; ?>
        </select>
        <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Reference, name or email">
        <button class="btn btn-ghost" type="submit"><?= icon('search') ?></button>
    </form>
    <a class="btn btn-ghost" href="<?= url('admin/fixtures.php') ?>"><?= icon('edit') ?> Edit prices &amp; capacity</a>
</div>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Reference</th><th>Fan</th><th>Match</th><th>Section</th><th class="num">Qty</th><th class="num">Total</th><th>Booked</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><strong><?= e($r['reference']) ?></strong></td>
                <td><?= e($r['name']) ?><br><small class="muted"><?= e($r['email']) ?></small></td>
                <td class="nowrap"><?= $r['is_home'] ? 'vs' : '@' ?> <?= e($r['opponent']) ?><br><small class="muted"><?= fmt_date($r['kickoff'], 'j M Y') ?></small></td>
                <td><span class="pill"><?= e($r['category']) ?></span></td>
                <td class="num"><?= (int) $r['quantity'] ?></td>
                <td class="num"><?= money($r['total']) ?></td>
                <td class="nowrap"><small><?= fmt_date($r['created_at'], 'j M, g:i A') ?></small></td>
                <td><a class="btn btn-ghost btn-sm" href="<?= url('ticket.php?ref=' . urlencode($r['reference'])) ?>" target="_blank"><?= icon('eye') ?></a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="empty">No bookings found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($pg['pages'] > 1): ?>
<nav class="pagination">
    <?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
        <?= $i === $pg['page'] ? "<span class=\"current\">$i</span>" : '<a href="?' . e(http_build_query(['fixture' => $fixtureId ?: null, 'q' => $q ?: null, 'page' => $i])) . "\">$i</a>" ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
