<?php
require __DIR__ . '/../includes/bootstrap.php';
$me = require_admin();

/* ---------- Change role ---------- */
if (is_post()) {
    $id = (int) ($_POST['id'] ?? 0);
    $role = $_POST['role'] ?? '';
    if ($id === (int) $me['id']) {
        flash('error', "You can't change your own role.");
    } elseif (in_array($role, ['fan', 'admin'], true)) {
        db_query('UPDATE users SET role = ? WHERE id = ?', [$role, $id]);
        flash('success', $role === 'admin' ? 'Member promoted to admin.' : 'Admin access removed.');
    }
    redirect('admin/users.php');
}

$q = trim($_GET['q'] ?? '');
$params = $q !== '' ? ["%$q%", "%$q%"] : [];
$where = $q !== '' ? 'WHERE u.name LIKE ? OR u.email LIKE ?' : '';
$total = (int) db_value("SELECT COUNT(*) FROM users u $where", $params);
$pg = paginate($total, 20, (int) ($_GET['page'] ?? 1));
$users = db_all(
    "SELECT u.*,
        (SELECT COALESCE(SUM(total), 0) FROM ticket_orders WHERE user_id = u.id) +
        (SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = u.id AND status <> 'cancelled') AS spent,
        (SELECT COALESCE(SUM(quantity), 0) FROM ticket_orders WHERE user_id = u.id) AS tickets
     FROM users u $where ORDER BY u.role = 'admin' DESC, u.created_at DESC LIMIT {$pg['limit']} OFFSET {$pg['offset']}",
    $params
);

$pageTitle = 'Members';
$active = 'users';
require __DIR__ . '/../partials/admin_header.php';
?>
<div class="toolbar">
    <form method="get">
        <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or email">
        <button class="btn btn-ghost" type="submit"><?= icon('search') ?></button>
    </form>
    <span class="muted"><?= number_format($total) ?> member(s)</span>
</div>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Member</th><th>Phone</th><th>Joined</th><th class="num">Tickets</th><th class="num">Spent</th><th>Role</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><div class="cell-main"><span class="avatar"><?= e(initials($u['name'])) ?></span><div><strong><?= e($u['name']) ?></strong><small><?= e($u['email']) ?></small></div></div></td>
                <td><?= e($u['phone'] ?: '—') ?></td>
                <td class="nowrap"><?= fmt_date($u['created_at'], 'j M Y') ?></td>
                <td class="num"><?= (int) $u['tickets'] ?></td>
                <td class="num"><?= money($u['spent']) ?></td>
                <td><span class="pill <?= $u['role'] === 'admin' ? 'pill-gold' : '' ?>"><?= e($u['role']) ?></span></td>
                <td><div class="actions">
                    <?php if ((int) $u['id'] !== (int) $me['id']): ?>
                        <form method="post" data-confirm="<?= $u['role'] === 'admin' ? 'Remove admin access for ' . e($u['name']) . '?' : 'Give ' . e($u['name']) . ' full admin access?' ?>">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'fan' : 'admin' ?>">
                            <button class="btn btn-ghost btn-sm" type="submit"><?= icon('shield') ?> <?= $u['role'] === 'admin' ? 'Make fan' : 'Make admin' ?></button>
                        </form>
                    <?php else: ?><small class="faint">You</small><?php endif; ?>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?><tr><td colspan="7" class="empty">No members found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($pg['pages'] > 1): ?>
<nav class="pagination">
    <?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
        <?= $i === $pg['page'] ? "<span class=\"current\">$i</span>" : '<a href="?' . e(http_build_query(['q' => $q ?: null, 'page' => $i])) . "\">$i</a>" ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
