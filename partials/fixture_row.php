<?php
/** One fixture row. Expects $f (fixture row), optional $showTicketsLink. */
$outcome = match_outcome($f);
$home = $f['is_home'] ? '<span class="us">FC Scorpio</span> vs ' . e($f['opponent']) : e($f['opponent']) . ' vs <span class="us">FC Scorpio</span>';
?>
<article class="fixture">
    <div class="date">
        <strong><?= date('d', strtotime($f['kickoff'])) ?></strong>
        <span><?= date('M · D', strtotime($f['kickoff'])) ?></span>
    </div>
    <div>
        <div class="teams-line"><?= $home ?></div>
        <div class="sub">
            <span><?= icon('trophy') ?><?= e($f['competition']) ?></span>
            <span><?= icon('clock') ?><?= fmt_time($f['kickoff']) ?></span>
            <span><?= icon('pin') ?><?= e($f['venue']) ?></span>
        </div>
    </div>
    <div>
        <?php if ($outcome): ?>
            <div class="score">
                <span class="result-badge <?= $outcome[1] ?>"><?= $outcome[0] ?></span>
                <?= $f['is_home'] ? (int) $f['goals_for'] . ' – ' . (int) $f['goals_against'] : (int) $f['goals_against'] . ' – ' . (int) $f['goals_for'] ?>
            </div>
        <?php elseif ($f['status'] === 'postponed'): ?>
            <span class="pill pill-red">Postponed</span>
        <?php elseif (!empty($f['has_tickets'])): ?>
            <a class="btn btn-primary btn-sm" href="<?= url('tickets.php#fixture-' . (int) $f['id']) ?>"><?= icon('ticket') ?> Tickets</a>
        <?php else: ?>
            <span class="pill"><?= $f['is_home'] ? 'Home' : 'Away' ?></span>
        <?php endif; ?>
    </div>
</article>
