<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$categories = ['Premium', 'Standard', 'Economy'];
$statuses   = ['scheduled' => 'Scheduled', 'finished' => 'Finished', 'postponed' => 'Postponed'];
$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$errors = [];

/* ---------- Delete ---------- */
if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    $sold = (int) db_value('SELECT COALESCE(SUM(sold), 0) FROM ticket_types WHERE fixture_id = ?', [$id]);
    if ($sold > 0) {
        flash('error', "This match has $sold ticket(s) sold, so it can't be deleted. Mark it as postponed instead.");
    } else {
        db_query('DELETE FROM fixtures WHERE id = ?', [$id]);
        flash('success', 'Fixture deleted.');
    }
    redirect('admin/fixtures.php');
}

/* ---------- Create / update ---------- */
if (is_post() && ($_POST['action'] ?? '') === 'save') {
    $data = [
        'opponent'      => input('opponent'),
        'competition'   => input('competition') ?: 'Premier Division',
        'venue'         => input('venue'),
        'is_home'       => input('is_home') === '1' ? 1 : 0,
        'kickoff'       => input('kickoff'),
        'status'        => input('status'),
        'goals_for'     => input('goals_for') === '' ? null : (int) input('goals_for'),
        'goals_against' => input('goals_against') === '' ? null : (int) input('goals_against'),
    ];
    if (mb_strlen($data['opponent']) < 2) $errors[] = 'Opponent is required.';
    if (mb_strlen($data['venue']) < 2) $errors[] = 'Venue is required.';
    if (!strtotime($data['kickoff'])) $errors[] = 'Kick-off date and time are required.';
    if (!isset($statuses[$data['status']])) $errors[] = 'Invalid status.';
    if ($data['status'] === 'finished' && ($data['goals_for'] === null || $data['goals_against'] === null)) {
        $errors[] = 'Enter the final score for a finished match.';
    }
    if ($data['status'] !== 'finished') {
        $data['goals_for'] = $data['goals_against'] = null;
    }
    $data['kickoff'] = date('Y-m-d H:i:s', strtotime($data['kickoff']));

    // Ticket categories: leave price blank to not sell that section.
    $existing = $id ? array_column(db_all('SELECT * FROM ticket_types WHERE fixture_id = ?', [$id]), null, 'category') : [];
    $tiers = [];
    foreach ($categories as $c) {
        $price = trim($_POST['price'][$c] ?? '');
        $cap   = trim($_POST['capacity'][$c] ?? '');
        $sold  = (int) ($existing[$c]['sold'] ?? 0);
        if ($price === '' && $cap === '') {
            if ($sold > 0) $errors[] = "$c already has $sold ticket(s) sold and can't be removed.";
            $tiers[$c] = null;
            continue;
        }
        if (!is_numeric($price) || $price <= 0) $errors[] = "Enter a valid $c price.";
        if (!ctype_digit($cap) || (int) $cap < 1) $errors[] = "Enter a valid $c capacity.";
        elseif ((int) $cap < $sold) $errors[] = "$c capacity can't be below the $sold seats already sold.";
        $tiers[$c] = ['price' => (float) $price, 'capacity' => (int) $cap];
    }

    if (!$errors) {
        db_transaction(function () use (&$id, $data, $tiers, $existing) {
            if ($id) {
                $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
                db_query("UPDATE fixtures SET $set WHERE id = ?", [...array_values($data), $id]);
            } else {
                db_query('INSERT INTO fixtures (' . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')', array_values($data));
                $id = (int) db()->lastInsertId();
            }
            foreach ($tiers as $c => $t) {
                if ($t === null) {
                    db_query('DELETE FROM ticket_types WHERE fixture_id = ? AND category = ?', [$id, $c]);
                } elseif (isset($existing[$c])) {
                    db_query('UPDATE ticket_types SET price = ?, capacity = ? WHERE id = ?', [$t['price'], $t['capacity'], $existing[$c]['id']]);
                } else {
                    db_query('INSERT INTO ticket_types (fixture_id, category, price, capacity) VALUES (?, ?, ?, ?)', [$id, $c, $t['price'], $t['capacity']]);
                }
            }
        });
        flash('success', 'Fixture saved.');
        redirect('admin/fixtures.php');
    }
    $action = $id ? 'edit' : 'new';
}

$pageTitle = 'Fixtures';
$active = 'fixtures';

/* ---------- Form ---------- */
if ($action === 'new' || $action === 'edit') {
    $f = $id ? db_one('SELECT * FROM fixtures WHERE id = ?', [$id]) : null;
    if ($action === 'edit' && !$f) redirect('admin/fixtures.php');
    $types = $id ? array_column(db_all('SELECT * FROM ticket_types WHERE fixture_id = ?', [$id]), null, 'category') : [];
    $v = fn($k, $d = '') => e($_POST[$k] ?? $f[$k] ?? $d);
    $kick = $_POST['kickoff'] ?? (isset($f['kickoff']) ? date('Y-m-d\TH:i', strtotime($f['kickoff'])) : '');
    $pageTitle = $f ? 'Edit fixture' : 'Add fixture';
    $defaults = ['Premium' => [1500, 200], 'Standard' => [700, 800], 'Economy' => [300, 1500]];
    require __DIR__ . '/../partials/admin_header.php';
    ?>
    <a class="link-arrow" href="<?= url('admin/fixtures.php') ?>" style="margin-bottom:18px"><?= icon('back') ?> All fixtures</a>
    <form method="post" class="form-card stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <?php if ($errors): ?><div class="alert alert-error"><?= icon('alert') ?><div><?= implode('<br>', array_map('e', $errors)) ?></div></div><?php endif; ?>

        <div class="card">
            <div class="card-title"><h3>Match details</h3></div>
            <div class="form-grid cols-2">
                <div class="field"><label>Opponent</label><input class="input" name="opponent" value="<?= $v('opponent') ?>" required></div>
                <div class="field"><label>Competition</label><input class="input" name="competition" value="<?= $v('competition', 'Premier Division') ?>" list="comps"></div>
                <datalist id="comps"><option>Premier Division</option><option>Federation Cup</option><option>Friendly</option></datalist>
                <div class="field"><label>Home or away</label>
                    <select class="select" name="is_home"><option value="1" <?= selected(1, $_POST['is_home'] ?? $f['is_home'] ?? 1) ?>>Home</option><option value="0" <?= selected(0, $_POST['is_home'] ?? $f['is_home'] ?? 1) ?>>Away</option></select></div>
                <div class="field"><label>Venue</label><input class="input" name="venue" value="<?= $v('venue', 'Scorpio Arena, Dhaka') ?>" required></div>
                <div class="field"><label>Kick-off</label><input class="input" type="datetime-local" name="kickoff" value="<?= e($kick) ?>" required></div>
                <div class="field"><label>Status</label>
                    <select class="select" name="status"><?php foreach ($statuses as $k => $l): ?><option value="<?= $k ?>" <?= selected($k, $_POST['status'] ?? $f['status'] ?? 'scheduled') ?>><?= $l ?></option><?php endforeach; ?></select></div>
                <div class="field"><label>FC Scorpio goals</label><input class="input" type="number" min="0" name="goals_for" value="<?= $v('goals_for') ?>"><span class="hint">Only for finished matches</span></div>
                <div class="field"><label>Opponent goals</label><input class="input" type="number" min="0" name="goals_against" value="<?= $v('goals_against') ?>"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-title"><h3>Ticket sales</h3><span class="muted" style="font-size:.85rem">Leave a row empty to not sell that section</span></div>
            <div class="table-wrap" style="border:0;background:none">
                <table class="table">
                    <thead><tr><th>Section</th><th>Price (৳)</th><th>Capacity</th><th class="num">Sold</th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $c):
                        $t = $types[$c] ?? null;
                        $pr = $_POST['price'][$c] ?? ($t ? (float) $t['price'] : (!$f ? $defaults[$c][0] : ''));
                        $cp = $_POST['capacity'][$c] ?? ($t ? $t['capacity'] : (!$f ? $defaults[$c][1] : '')); ?>
                        <tr>
                            <td><strong><?= $c ?></strong></td>
                            <td><input class="input" type="number" step="1" min="0" name="price[<?= $c ?>]" value="<?= e($pr) ?>"></td>
                            <td><input class="input" type="number" min="<?= (int) ($t['sold'] ?? 0) ?>" name="capacity[<?= $c ?>]" value="<?= e($cp) ?>"></td>
                            <td class="num"><?= (int) ($t['sold'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save fixture</button>
            <a class="btn btn-ghost" href="<?= url('admin/fixtures.php') ?>">Cancel</a></div>
    </form>
    <?php
    require __DIR__ . '/../partials/admin_footer.php';
    exit;
}

/* ---------- List ---------- */
$show = ($_GET['show'] ?? 'upcoming') === 'past' ? 'past' : 'upcoming';
$fixtures = db_all(
    "SELECT f.*, COALESCE(SUM(t.sold), 0) AS sold, COALESCE(SUM(t.capacity), 0) AS capacity
     FROM fixtures f LEFT JOIN ticket_types t ON t.fixture_id = f.id
     WHERE " . ($show === 'past' ? "f.status = 'finished' OR f.kickoff < NOW()" : "f.status <> 'finished' AND f.kickoff >= NOW()") . "
     GROUP BY f.id ORDER BY f.kickoff " . ($show === 'past' ? 'DESC' : 'ASC')
);
require __DIR__ . '/../partials/admin_header.php';
?>
<div class="toolbar">
    <div class="tabs" style="margin:0">
        <a class="tab <?= $show === 'upcoming' ? 'active' : '' ?>" href="?show=upcoming">Upcoming</a>
        <a class="tab <?= $show === 'past' ? 'active' : '' ?>" href="?show=past">Past &amp; results</a>
    </div>
    <a class="btn btn-primary" href="?action=new"><?= icon('plus') ?> Add fixture</a>
</div>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Date</th><th>Match</th><th>Competition</th><th>Status</th><th>Result / tickets</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($fixtures as $f): $o = match_outcome($f); ?>
            <tr>
                <td class="nowrap"><strong><?= fmt_date($f['kickoff'], 'j M Y') ?></strong><br><small class="muted"><?= fmt_time($f['kickoff']) ?></small></td>
                <td><strong><?= $f['is_home'] ? 'vs' : '@' ?> <?= e($f['opponent']) ?></strong><br><small class="muted"><?= e($f['venue']) ?></small></td>
                <td><?= e($f['competition']) ?></td>
                <td><span class="pill <?= ['scheduled' => 'pill-teal', 'finished' => '', 'postponed' => 'pill-red'][$f['status']] ?>"><?= e($statuses[$f['status']]) ?></span></td>
                <td>
                    <?php if ($o): ?>
                        <div class="score" style="font-size:1.1rem"><span class="result-badge <?= $o[1] ?>"><?= $o[0] ?></span><?= (int) $f['goals_for'] ?> – <?= (int) $f['goals_against'] ?></div>
                    <?php elseif ($f['capacity']): ?>
                        <small class="muted"><?= number_format($f['sold']) ?> / <?= number_format($f['capacity']) ?> sold</small>
                    <?php else: ?><small class="faint">—</small><?php endif; ?>
                </td>
                <td><div class="actions">
                    <a class="btn btn-ghost btn-sm" href="?action=edit&id=<?= (int) $f['id'] ?>"><?= icon('edit') ?> <?= $f['status'] === 'scheduled' && strtotime($f['kickoff']) < time() ? 'Add result' : 'Edit' ?></a>
                    <form method="post" data-confirm="Delete this fixture?">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                        <button class="btn btn-danger btn-sm" type="submit" aria-label="Delete"><?= icon('trash') ?></button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$fixtures): ?><tr><td colspan="6" class="empty">Nothing here yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
