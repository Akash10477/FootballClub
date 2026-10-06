<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$positions = ['Goalkeeper', 'Defender', 'Midfielder', 'Forward'];
$action = $_GET['action'] ?? 'list';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$errors = [];

/* ---------- Delete ---------- */
if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    $pl = db_one('SELECT photo FROM players WHERE id = ?', [$id]);
    if ($pl) {
        db_query('DELETE FROM players WHERE id = ?', [$id]);
        delete_upload($pl['photo'], 'players');
        flash('success', 'Player removed from the squad.');
    }
    redirect('admin/players.php');
}

/* ---------- Create / update ---------- */
if (is_post() && ($_POST['action'] ?? '') === 'save') {
    $data = [
        'name'          => input('name'),
        'position'      => input('position'),
        'jersey_number' => (int) input('jersey_number'),
        'date_of_birth' => input('date_of_birth') ?: null,
        'nationality'   => input('nationality') ?: 'Bangladesh',
        'appearances'   => max(0, (int) input('appearances')),
        'goals'         => max(0, (int) input('goals')),
        'assists'       => max(0, (int) input('assists')),
        'is_captain'    => isset($_POST['is_captain']) ? 1 : 0,
    ];
    if (mb_strlen($data['name']) < 2) $errors[] = 'Name is required.';
    if (!in_array($data['position'], $positions, true)) $errors[] = 'Choose a valid position.';
    if ($data['jersey_number'] < 1 || $data['jersey_number'] > 99) $errors[] = 'Jersey number must be between 1 and 99.';
    elseif (db_value('SELECT 1 FROM players WHERE jersey_number = ? AND id <> ?', [$data['jersey_number'], $id])) {
        $errors[] = "Jersey #{$data['jersey_number']} is already taken.";
    }
    if ($data['date_of_birth'] && strtotime($data['date_of_birth']) > strtotime('-14 years')) $errors[] = 'Please check the date of birth.';

    try {
        $photo = $errors ? null : handle_image_upload('photo', 'players');
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }

    if (!$errors) {
        if ($data['is_captain']) {
            db_query('UPDATE players SET is_captain = 0');     // only one captain
        }
        if ($id) {
            $old = db_one('SELECT photo FROM players WHERE id = ?', [$id]);
            if ($photo) { $data['photo'] = $photo; delete_upload($old['photo'] ?? null, 'players'); }
            $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
            db_query("UPDATE players SET $set WHERE id = ?", [...array_values($data), $id]);
            flash('success', "{$data['name']} updated.");
        } else {
            $data['photo'] = $photo;
            $cols = implode(', ', array_keys($data));
            $qs = implode(', ', array_fill(0, count($data), '?'));
            db_query("INSERT INTO players ($cols) VALUES ($qs)", array_values($data));
            flash('success', "{$data['name']} added to the squad.");
        }
        redirect('admin/players.php');
    }
    $action = $id ? 'edit' : 'new';
}

$pageTitle = 'Players';
$active = 'players';

/* ---------- Form ---------- */
if ($action === 'new' || $action === 'edit') {
    $pl = $id ? db_one('SELECT * FROM players WHERE id = ?', [$id]) : null;
    if ($action === 'edit' && !$pl) redirect('admin/players.php');
    $v = fn($k, $d = '') => e($_POST[$k] ?? $pl[$k] ?? $d);
    $pageTitle = $pl ? 'Edit player' : 'Add player';
    require __DIR__ . '/../partials/admin_header.php';
    ?>
    <a class="link-arrow" href="<?= url('admin/players.php') ?>" style="margin-bottom:18px"><?= icon('back') ?> All players</a>
    <div class="card form-card">
        <?php if ($errors): ?><div class="alert alert-error"><?= icon('alert') ?><div><?= implode('<br>', array_map('e', $errors)) ?></div></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="form-grid cols-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) $id ?>">
            <div class="field full"><label>Full name</label><input class="input" name="name" value="<?= $v('name') ?>" required></div>
            <div class="field"><label>Position</label>
                <select class="select" name="position" required>
                    <?php foreach ($positions as $p): ?><option <?= selected($p, $_POST['position'] ?? $pl['position'] ?? '') ?>><?= $p ?></option><?php endforeach; ?>
                </select></div>
            <div class="field"><label>Jersey number</label><input class="input" type="number" min="1" max="99" name="jersey_number" value="<?= $v('jersey_number') ?>" required></div>
            <div class="field"><label>Date of birth</label><input class="input" type="date" name="date_of_birth" value="<?= $v('date_of_birth') ?>"></div>
            <div class="field"><label>Nationality</label><input class="input" name="nationality" value="<?= $v('nationality', 'Bangladesh') ?>"></div>
            <div class="field"><label>Appearances</label><input class="input" type="number" min="0" name="appearances" value="<?= $v('appearances', 0) ?>"></div>
            <div class="field"><label>Goals</label><input class="input" type="number" min="0" name="goals" value="<?= $v('goals', 0) ?>"></div>
            <div class="field"><label>Assists</label><input class="input" type="number" min="0" name="assists" value="<?= $v('assists', 0) ?>"></div>
            <div class="field" style="justify-content:flex-end"><label class="checkbox"><input type="checkbox" name="is_captain" <?= ($_POST['is_captain'] ?? $pl['is_captain'] ?? 0) ? 'checked' : '' ?>> Club captain</label></div>
            <div class="field full"><label>Photo <span class="faint">(optional, JPG/PNG/WEBP up to 2 MB)</span></label>
                <div style="display:flex;gap:14px;align-items:center">
                    <img class="preview-img" id="pv" src="<?= e(player_photo($pl['photo'] ?? null) ?? '') ?>" alt="" <?= empty($pl['photo']) ? 'hidden' : '' ?>>
                    <input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-preview="#pv">
                </div></div>
            <div class="form-actions full"><button class="btn btn-primary" type="submit"><?= icon('check') ?> <?= $pl ? 'Save changes' : 'Add player' ?></button>
                <a class="btn btn-ghost" href="<?= url('admin/players.php') ?>">Cancel</a></div>
        </form>
    </div>
    <?php
    require __DIR__ . '/../partials/admin_footer.php';
    exit;
}

/* ---------- List ---------- */
$filter = in_array($_GET['position'] ?? '', $positions, true) ? $_GET['position'] : '';
$players = $filter
    ? db_all('SELECT * FROM players WHERE position = ? ORDER BY jersey_number', [$filter])
    : db_all("SELECT * FROM players ORDER BY FIELD(position,'Goalkeeper','Defender','Midfielder','Forward'), jersey_number");
require __DIR__ . '/../partials/admin_header.php';
?>
<div class="toolbar">
    <form method="get">
        <select class="select" name="position" onchange="this.form.submit()" aria-label="Filter by position">
            <option value="">All positions</option>
            <?php foreach ($positions as $p): ?><option <?= selected($p, $filter) ?>><?= $p ?></option><?php endforeach; ?>
        </select>
    </form>
    <a class="btn btn-primary" href="?action=new"><?= icon('plus') ?> Add player</a>
</div>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>#</th><th>Player</th><th>Position</th><th>Age</th><th class="num">Apps</th><th class="num">Goals</th><th class="num">Assists</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($players as $pl): ?>
            <tr>
                <td><strong class="gold" style="font-family:var(--font-display);font-size:1.2rem"><?= (int) $pl['jersey_number'] ?></strong></td>
                <td><div class="cell-main">
                    <?php if ($ph = player_photo($pl['photo'])): ?><img class="thumb-sm" src="<?= e($ph) ?>" alt="" style="border-radius:50%"><?php else: ?><span class="avatar"><?= e(initials($pl['name'])) ?></span><?php endif; ?>
                    <div><strong><?= e($pl['name']) ?> <?= $pl['is_captain'] ? '<span class="pill pill-gold">C</span>' : '' ?></strong><small><?= e($pl['nationality']) ?></small></div>
                </div></td>
                <td><?= e($pl['position']) ?></td>
                <td><?= age_from($pl['date_of_birth']) ?? '—' ?></td>
                <td class="num"><?= (int) $pl['appearances'] ?></td>
                <td class="num"><?= (int) $pl['goals'] ?></td>
                <td class="num"><?= (int) $pl['assists'] ?></td>
                <td><div class="actions">
                    <a class="btn btn-ghost btn-sm" href="?action=edit&id=<?= (int) $pl['id'] ?>"><?= icon('edit') ?> Edit</a>
                    <form method="post" data-confirm="Remove <?= e($pl['name']) ?> from the squad?">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $pl['id'] ?>">
                        <button class="btn btn-danger btn-sm" type="submit" aria-label="Delete"><?= icon('trash') ?></button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$players): ?><tr><td colspan="8" class="empty">No players yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
