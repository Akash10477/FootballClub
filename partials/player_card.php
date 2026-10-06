<?php /** One player card. Expects $pl. */ ?>
<article class="player-card">
    <span class="num"><?= (int) $pl['jersey_number'] ?></span>
    <div class="photo">
        <?php if ($photo = player_photo($pl['photo'])): ?>
            <img src="<?= e($photo) ?>" alt="<?= e($pl['name']) ?>">
        <?php else: ?>
            <?= e(initials($pl['name'])) ?>
        <?php endif; ?>
    </div>
    <?php if ($pl['is_captain']): ?><span class="captain" title="Captain">C</span><?php endif; ?>
    <span class="pos">#<?= (int) $pl['jersey_number'] ?> · <?= e($pl['position']) ?></span>
    <h3><?= e($pl['name']) ?></h3>
    <span class="muted" style="font-size:.85rem"><?= e($pl['nationality']) ?><?= ($age = age_from($pl['date_of_birth'])) ? ' · ' . $age . ' yrs' : '' ?></span>
    <div class="pstats">
        <div><strong><?= (int) $pl['appearances'] ?></strong>Apps</div>
        <div><strong><?= (int) $pl['goals'] ?></strong>Goals</div>
        <div><strong><?= (int) $pl['assists'] ?></strong>Assists</div>
    </div>
</article>
