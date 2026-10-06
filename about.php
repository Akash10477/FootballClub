<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'About the club';
$active = 'club';
require __DIR__ . '/partials/header.php';

$values = [
    ['shield', 'Respect',     'Respect for the game, our opponents, officials and each other, every single match.'],
    ['trophy', 'Improvement', 'A commitment to getting better every day, on the training pitch and beyond it.'],
    ['users',  'Teamwork',    'No individual is bigger than the badge. We win together and we learn together.'],
    ['star',   'Integrity',   'Honesty and fair play on and off the field. How we win matters as much as winning.'],
];
?>
<section class="page-hero">
    <div class="hero-bg" style="background-image:url('<?= asset('img/stadium-night.jpg') ?>')"></div>
    <div class="container">
        <div class="eyebrow">The club</div>
        <h1>Welcome to FC Scorpio</h1>
        <p>A growing community of dedicated players, passionate fans and visionary leaders. Football isn't just a game to us. It's a way of life.</p>
    </div>
</section>

<section class="section">
    <div class="container about-split">
        <div class="img"><img src="<?= asset('img/ball-grass.jpg') ?>" alt="Match ball on the pitch at night" loading="lazy"></div>
        <div>
            <div class="eyebrow">Our story</div>
            <h2>Built on talent, teamwork &amp; tenacity</h2>
            <p class="muted">FC Scorpio was founded with a vision to unite talent, teamwork and tenacity. We believe football inspires discipline, unity and the relentless pursuit of excellence.</p>
            <p class="muted">We train hard, play harder and dream the hardest. From grassroots to competitive matches, we aim to nurture talent and create real opportunities for young players to shine.</p>
            <p class="muted">Whether you're a die-hard supporter, a rising athlete or simply someone who loves the beautiful game, you're always welcome in the FC Scorpio family.</p>
            <div class="hero-actions" style="margin-top:26px">
                <a class="btn btn-primary" href="<?= url('squad.php') ?>">Meet the squad</a>
                <a class="btn btn-ghost" href="<?= url('fixtures.php') ?>">See fixtures</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <div><div class="eyebrow">What we stand for</div><h2>Our values</h2></div>
        </div>
        <div class="grid grid-4">
            <?php foreach ($values as [$ico, $title, $text]): ?>
                <div class="card value-card card-hover">
                    <div class="icon-wrap"><?= icon($ico) ?></div>
                    <h3><?= e($title) ?></h3>
                    <p><?= e($text) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container text-center" style="max-width:760px">
        <img src="<?= asset('img/crest.png') ?>" alt="" style="width:90px;margin:0 auto 22px">
        <h2>Join us. Cheer with us. Grow with us.</h2>
        <p class="muted" style="font-size:1.1rem">Together, we are unstoppable. Together, we are FC Scorpio.</p>
        <div class="hero-actions" style="justify-content:center;margin-top:24px">
            <a class="btn btn-primary btn-lg" href="<?= url('tickets.php') ?>"><?= icon('ticket') ?> Come to a match</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
