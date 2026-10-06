<?php
require __DIR__ . '/includes/bootstrap.php';

$sql = "SELECT f.*, EXISTS(SELECT 1 FROM ticket_types t WHERE t.fixture_id = f.id) AS has_tickets FROM fixtures f";
$upcoming = db_all("$sql WHERE f.status <> 'finished' AND f.kickoff > NOW() - INTERVAL 1 DAY ORDER BY f.kickoff");
$results  = db_all("$sql WHERE f.status = 'finished' ORDER BY f.kickoff DESC");

$pageTitle = 'Fixtures & results';
$active = 'fixtures';
require __DIR__ . '/partials/header.php';
?>
<section class="page-hero">
    <div class="hero-bg" style="background-image:url('<?= asset('img/stadium-night.jpg') ?>')"></div>
    <div class="container">
        <div class="eyebrow">Season 2026/27</div>
        <h1>Fixtures &amp; results</h1>
        <p>Every match on the calendar, with kick-off times, venues and final scores.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="tabs" data-filter-group="#fixtures-wrap">
            <button class="tab active" type="button" data-filter="all">All</button>
            <button class="tab" type="button" data-filter="upcoming">Upcoming (<?= count($upcoming) ?>)</button>
            <button class="tab" type="button" data-filter="results">Results (<?= count($results) ?>)</button>
        </div>
        <div id="fixtures-wrap">
            <div data-group="upcoming">
                <div class="group-title">Upcoming</div>
                <div class="fixture-list">
                    <?php foreach ($upcoming as $f) { require __DIR__ . '/partials/fixture_row.php'; } ?>
                    <?php if (!$upcoming): ?><div class="card empty"><?= icon('calendar') ?>No upcoming fixtures yet.</div><?php endif; ?>
                </div>
            </div>
            <div data-group="results">
                <div class="group-title">Results</div>
                <div class="fixture-list">
                    <?php foreach ($results as $f) { require __DIR__ . '/partials/fixture_row.php'; } ?>
                    <?php if (!$results): ?><div class="card empty"><?= icon('trophy') ?>No results yet this season.</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
