<?php
require __DIR__ . '/includes/bootstrap.php';

$players = db_all("SELECT * FROM players
                   ORDER BY FIELD(position, 'Goalkeeper', 'Defender', 'Midfielder', 'Forward'), jersey_number");
$groups = [];
foreach ($players as $p) {
    $groups[$p['position']][] = $p;
}
$plural = ['Goalkeeper' => 'Goalkeepers', 'Defender' => 'Defenders', 'Midfielder' => 'Midfielders', 'Forward' => 'Forwards'];

$pageTitle = 'First-team squad';
$active = 'squad';
require __DIR__ . '/partials/header.php';
?>
<section class="page-hero">
    <div class="hero-bg" style="background-image:url('<?= asset('img/stadium-night.jpg') ?>')"></div>
    <div class="container">
        <div class="eyebrow">Season 2026/27</div>
        <h1>First-team squad</h1>
        <p>The <?= count($players) ?> players carrying the Scorpio badge this season.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="tabs" data-filter-group="#squad">
            <button class="tab active" data-filter="all" type="button">All players</button>
            <?php foreach (array_keys($groups) as $pos): ?>
                <button class="tab" data-filter="<?= e($pos) ?>" type="button"><?= e($plural[$pos]) ?></button>
            <?php endforeach; ?>
        </div>

        <div id="squad">
            <?php foreach ($groups as $pos => $list): ?>
                <div data-group="<?= e($pos) ?>">
                    <div class="group-title"><?= e($plural[$pos]) ?> <span class="faint" style="font-size:.9rem"><?= count($list) ?></span></div>
                    <div class="grid grid-4">
                        <?php foreach ($list as $pl) { require __DIR__ . '/partials/player_card.php'; } ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$players): ?>
                <div class="card empty"><?= icon('users') ?>The squad will be announced soon.</div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
