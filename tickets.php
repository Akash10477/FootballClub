<?php
require __DIR__ . '/includes/bootstrap.php';

const MAX_TICKETS_PER_ORDER = 10;

/* ---------- Buy tickets ---------- */
if (is_post()) {
    $user   = require_login();
    $typeId = (int) ($_POST['ticket_type_id'] ?? 0);
    $qty    = (int) ($_POST['quantity'] ?? 0);

    if ($qty < 1 || $qty > MAX_TICKETS_PER_ORDER) {
        flash('error', 'You can buy between 1 and ' . MAX_TICKETS_PER_ORDER . ' tickets per order.');
        redirect('tickets.php');
    }

    try {
        $ref = db_transaction(function (PDO $pdo) use ($typeId, $qty, $user) {
            // Lock the row so two fans can't buy the last seats at the same time.
            $stmt = $pdo->prepare(
                "SELECT t.*, f.kickoff, f.status FROM ticket_types t
                 JOIN fixtures f ON f.id = t.fixture_id WHERE t.id = ? FOR UPDATE"
            );
            $stmt->execute([$typeId]);
            $type = $stmt->fetch();

            if (!$type || $type['status'] !== 'scheduled' || strtotime($type['kickoff']) <= time()) {
                throw new RuntimeException('Tickets for this match are no longer on sale.');
            }
            $left = (int) $type['capacity'] - (int) $type['sold'];
            if ($left < $qty) {
                throw new RuntimeException($left > 0 ? "Only $left seat(s) left in this section." : 'This section is sold out.');
            }

            $pdo->prepare('UPDATE ticket_types SET sold = sold + ? WHERE id = ?')->execute([$qty, $typeId]);
            $ref = reference('TKT');
            $pdo->prepare(
                'INSERT INTO ticket_orders (reference, user_id, ticket_type_id, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$ref, $user['id'], $typeId, $qty, $type['price'], $type['price'] * $qty]);
            return $ref;
        });
        flash('success', 'Booking confirmed! Your e-ticket is ready.');
        redirect('ticket.php?ref=' . urlencode($ref));
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
        redirect('tickets.php');
    }
}

/* ---------- List matches on sale ---------- */
$rows = db_all(
    "SELECT f.id AS fixture_id, f.opponent, f.competition, f.venue, f.is_home, f.kickoff,
            t.id, t.category, t.price, t.capacity, t.sold
     FROM fixtures f JOIN ticket_types t ON t.fixture_id = f.id
     WHERE f.status = 'scheduled' AND f.kickoff > NOW()
     ORDER BY f.kickoff, FIELD(t.category, 'Premium', 'Standard', 'Economy')"
);
$matches = [];
foreach ($rows as $r) {
    $matches[$r['fixture_id']]['fixture'] = $r;
    $matches[$r['fixture_id']]['tiers'][]  = $r;
}
$tierInfo = [
    'Premium'  => 'Padded centre-line seats with the best view and lounge access.',
    'Standard' => 'Great sideline views in the main stand.',
    'Economy'  => 'Behind-the-goal terraces where the loudest fans sing.',
];

$pageTitle = 'Match tickets';
$active = 'tickets';
require __DIR__ . '/partials/header.php';
?>
<section class="page-hero">
    <div class="hero-bg" style="background-image:url('<?= asset('img/stands.jpg') ?>')"></div>
    <div class="container">
        <div class="eyebrow">Scorpio Arena</div>
        <h1>Match tickets</h1>
        <p>Pick your match and your seat. Your e-ticket is issued instantly and saved in your account.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (!is_logged_in()): ?>
            <div class="alert alert-info"><?= icon('info') ?>
                <div><a class="gold" href="<?= url('login.php?next=' . urlencode(url('tickets.php'))) ?>"><strong>Sign in</strong></a> or <a class="gold" href="<?= url('register.php') ?>"><strong>create a free account</strong></a> to book tickets.</div>
            </div>
        <?php endif; ?>

        <?php foreach ($matches as $fid => $m): $f = $m['fixture']; ?>
            <article class="ticket-block" id="fixture-<?= (int) $fid ?>">
                <div class="ticket-head">
                    <div>
                        <h3><?= $f['is_home'] ? '<span class="us">FC Scorpio</span> vs ' . e($f['opponent']) : e($f['opponent']) . ' vs <span class="us">FC Scorpio</span>' ?></h3>
                        <div class="ticket-meta">
                            <span><?= icon('calendar') ?> <?= fmt_date($f['kickoff'], 'l, j F') ?></span>
                            <span><?= icon('clock') ?> <?= fmt_time($f['kickoff']) ?></span>
                            <span><?= icon('pin') ?> <?= e($f['venue']) ?></span>
                        </div>
                    </div>
                    <span class="pill pill-teal"><?= e($f['competition']) ?></span>
                </div>
                <div class="ticket-tiers">
                    <?php foreach ($m['tiers'] as $t):
                        $left = max(0, $t['capacity'] - $t['sold']);
                        $pct  = $t['capacity'] ? round($t['sold'] / $t['capacity'] * 100) : 100; ?>
                        <div class="tier">
                            <div class="tier-name">
                                <span><?= e($t['category']) ?></span>
                                <span class="price"><?= money($t['price']) ?></span>
                            </div>
                            <p class="tier-desc"><?= e($tierInfo[$t['category']] ?? '') ?></p>
                            <div class="bar" title="<?= $pct ?>% sold"><span style="width:<?= $pct ?>%"></span></div>
                            <small class="<?= $left === 0 ? 'stock-out' : ($left <= 20 ? 'stock-low' : 'muted') ?>">
                                <?= $left === 0 ? 'Sold out' : ($left <= 20 ? "Almost gone: $left left" : number_format($left) . ' seats available') ?>
                            </small>
                            <?php if ($left > 0): ?>
                                <form method="post" data-unit-price="<?= (float) $t['price'] ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="ticket_type_id" value="<?= (int) $t['id'] ?>">
                                    <div class="qty">
                                        <button type="button" data-step="-1" aria-label="Fewer"><?= icon('minus') ?></button>
                                        <input type="number" name="quantity" value="1" min="1" max="<?= min($left, MAX_TICKETS_PER_ORDER) ?>" aria-label="Quantity">
                                        <button type="button" data-step="1" aria-label="More"><?= icon('plus') ?></button>
                                    </div>
                                    <button class="btn btn-primary btn-sm" type="submit" style="flex:1">Book · <span data-total><?= money($t['price']) ?></span></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (!$matches): ?>
            <div class="card empty"><?= icon('ticket') ?>No tickets on sale right now. Check back soon!</div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
