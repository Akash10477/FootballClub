<?php
require __DIR__ . '/includes/bootstrap.php';

$nextMatch = db_one(
    "SELECT f.*, EXISTS(SELECT 1 FROM ticket_types t WHERE t.fixture_id = f.id) AS has_tickets
     FROM fixtures f WHERE f.status = 'scheduled' AND f.kickoff > NOW() ORDER BY f.kickoff LIMIT 1"
);
$upcoming = db_all(
    "SELECT f.*, EXISTS(SELECT 1 FROM ticket_types t WHERE t.fixture_id = f.id) AS has_tickets
     FROM fixtures f WHERE f.status = 'scheduled' AND f.kickoff > NOW() ORDER BY f.kickoff LIMIT 1, 3"
);
$results = db_all("SELECT * FROM fixtures WHERE status = 'finished' ORDER BY kickoff DESC LIMIT 5");
$record = db_one(
    "SELECT COUNT(*) AS played,
            SUM(goals_for > goals_against) AS won,
            COALESCE(SUM(goals_for), 0) AS scored
     FROM fixtures WHERE status = 'finished'"
);
$topPlayers = db_all('SELECT * FROM players ORDER BY goals DESC, assists DESC LIMIT 4');
$products   = db_all('SELECT * FROM products WHERE is_active = 1 ORDER BY stock = 0, created_at DESC, id LIMIT 4');
$squadSize  = (int) db_value('SELECT COUNT(*) FROM players');

$active = 'home';
require __DIR__ . '/partials/header.php';
?>

<section class="hero">
    <div class="hero-bg" style="background-image:url('<?= asset('img/hero-stadium.jpg') ?>')"></div>
    <div class="container hero-grid">
        <div>
            <img class="hero-crest" src="<?= asset('img/crest.png') ?>" alt="FC Scorpio crest">
            <div class="eyebrow">Official club website</div>
            <h1>Where passion<span>meets the pitch</span></h1>
            <p class="lead">Talent, teamwork and tenacity. Follow the squad, book your seat at Scorpio Arena and wear the colours with pride.</p>
            <div class="hero-actions">
                <a class="btn btn-primary btn-lg" href="<?= url('tickets.php') ?>"><?= icon('ticket') ?> Buy tickets</a>
                <a class="btn btn-ghost btn-lg" href="<?= url('fixtures.php') ?>">View fixtures</a>
            </div>
        </div>

        <?php if ($nextMatch): ?>
        <aside class="match-card" aria-label="Next match">
            <div class="label">
                <span class="pill pill-gold">Next match</span>
                <span><?= e($nextMatch['competition']) ?></span>
            </div>
            <div class="teams">
                <?php
                $us   = '<div class="team-badge"><img src="' . asset('img/crest.png') . '" alt=""></div><div class="team-name">FC Scorpio</div>';
                $them = '<div class="team-badge">' . e(initials($nextMatch['opponent'])) . '</div><div class="team-name">' . e($nextMatch['opponent']) . '</div>';
                ?>
                <div><?= $nextMatch['is_home'] ? $us : $them ?></div>
                <div class="vs">VS</div>
                <div><?= $nextMatch['is_home'] ? $them : $us ?></div>
            </div>
            <div class="countdown" data-kickoff="<?= e(date('c', strtotime($nextMatch['kickoff']))) ?>">
                <div><strong data-unit="d">00</strong><span>Days</span></div>
                <div><strong data-unit="h">00</strong><span>Hrs</span></div>
                <div><strong data-unit="m">00</strong><span>Min</span></div>
                <div><strong data-unit="s">00</strong><span>Sec</span></div>
            </div>
            <div class="meta-list">
                <div><?= icon('calendar') ?> <?= fmt_date($nextMatch['kickoff'], 'l, j F Y') ?> · <?= fmt_time($nextMatch['kickoff']) ?></div>
                <div><?= icon('pin') ?> <?= e($nextMatch['venue']) ?></div>
            </div>
            <?php if ($nextMatch['has_tickets']): ?>
                <a class="btn btn-primary btn-block" href="<?= url('tickets.php#fixture-' . (int) $nextMatch['id']) ?>">Get match tickets <?= icon('arrow') ?></a>
            <?php else: ?>
                <a class="btn btn-ghost btn-block" href="<?= url('fixtures.php') ?>">Full schedule <?= icon('arrow') ?></a>
            <?php endif; ?>
        </aside>
        <?php endif; ?>
    </div>
</section>

<div class="container stats-strip">
    <div class="inner">
        <div class="item"><strong><?= (int) $record['played'] ?></strong><span>Matches played</span></div>
        <div class="item"><strong class="gold"><?= (int) $record['won'] ?></strong><span>Wins this season</span></div>
        <div class="item"><strong><?= (int) $record['scored'] ?></strong><span>Goals scored</span></div>
        <div class="item"><strong><?= $squadSize ?></strong><span>First-team players</span></div>
    </div>
</div>

<section class="section">
    <div class="container grid grid-2" style="align-items:start">
        <div>
            <div class="section-head">
                <div><div class="eyebrow">Matchday</div><h2>Upcoming</h2></div>
                <a class="link-arrow" href="<?= url('fixtures.php') ?>">All fixtures <?= icon('arrow') ?></a>
            </div>
            <div class="fixture-list">
                <?php foreach ($upcoming as $f) { require __DIR__ . '/partials/fixture_row.php'; } ?>
                <?php if (!$upcoming): ?><div class="card empty">No more fixtures announced yet.</div><?php endif; ?>
            </div>
        </div>
        <div>
            <div class="section-head">
                <div><div class="eyebrow">Form guide</div><h2>Latest results</h2></div>
                <div class="form-guide">
                    <?php foreach (array_reverse($results) as $r): $o = match_outcome($r); ?>
                        <span class="result-badge <?= $o[1] ?>" title="<?= e($r['opponent']) ?>"><?= $o[0] ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="fixture-list">
                <?php foreach (array_slice($results, 0, 3) as $f) { require __DIR__ . '/partials/fixture_row.php'; } ?>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <div><div class="eyebrow">First team</div><h2>Top performers</h2><p>The players making the difference this season.</p></div>
            <a class="link-arrow" href="<?= url('squad.php') ?>">Full squad <?= icon('arrow') ?></a>
        </div>
        <div class="grid grid-4">
            <?php foreach ($topPlayers as $pl) { require __DIR__ . '/partials/player_card.php'; } ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div><div class="eyebrow">Official store</div><h2>Wear the colours</h2><p>Kits, training wear and souvenirs, delivered anywhere in Bangladesh.</p></div>
            <a class="link-arrow" href="<?= url('shop.php') ?>">Visit the shop <?= icon('arrow') ?></a>
        </div>
        <div class="grid grid-4">
            <?php foreach ($products as $p) { require __DIR__ . '/partials/product_card.php'; } ?>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="cta-band">
            <div class="hero-bg" style="background-image:url('<?= asset('img/stands.jpg') ?>')"></div>
            <div>
                <h2>Join the Scorpio family</h2>
                <p>Create a free account to book tickets, order merchandise and keep every e-ticket in one place.</p>
            </div>
            <a class="btn btn-primary btn-lg" href="<?= url(is_logged_in() ? 'tickets.php' : 'register.php') ?>"><?= is_logged_in() ? 'Book your seat' : 'Create free account' ?> <?= icon('arrow') ?></a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
