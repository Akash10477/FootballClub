<?php
/** Public site header. Set $active to highlight a nav item. */
require __DIR__ . '/head.php';
$user   = current_user();
$active = $active ?? '';
$links  = [
    'home'     => ['index.php',    'Home'],
    'club'     => ['about.php',    'Club'],
    'squad'    => ['squad.php',    'Squad'],
    'fixtures' => ['fixtures.php', 'Fixtures'],
    'tickets'  => ['tickets.php',  'Tickets'],
    'shop'     => ['shop.php',     'Shop'],
];
?>
<body>
<a class="sr-only" href="#main">Skip to content</a>
<header class="site-header">
    <div class="container">
        <a class="brand" href="<?= url('index.php') ?>">
            <img src="<?= asset('img/crest.png') ?>" alt="">
            <span>FC Scorpio<small>Official website</small></span>
        </a>

        <nav class="nav" id="main-nav" aria-label="Main">
            <?php foreach ($links as $key => [$href, $label]): ?>
                <a href="<?= url($href) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <a class="icon-btn" href="<?= url('cart.php') ?>" aria-label="Cart">
                <?= icon('cart') ?>
                <?php if (cart_count() > 0): ?><span class="badge-dot"><?= cart_count() ?></span><?php endif; ?>
            </a>

            <?php if ($user): ?>
                <div class="dropdown">
                    <button class="dropdown-toggle" type="button" aria-haspopup="true">
                        <span class="avatar"><?= e(initials($user['name'])) ?></span>
                        <span class="name"><?= e(explode(' ', $user['name'])[0]) ?></span>
                        <?= icon('chevron') ?>
                    </button>
                    <div class="dropdown-menu">
                        <div class="who"><strong><?= e($user['name']) ?></strong><span><?= e($user['email']) ?></span></div>
                        <a href="<?= url('account.php') ?>"><?= icon('user') ?> My account</a>
                        <a href="<?= url('account.php#tickets') ?>"><?= icon('ticket') ?> My tickets</a>
                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="<?= url('admin/index.php') ?>"><?= icon('grid') ?> Admin panel</a>
                        <?php endif; ?>
                        <form method="post" action="<?= url('logout.php') ?>">
                            <?= csrf_field() ?>
                            <button type="submit"><?= icon('logout') ?> Sign out</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a class="btn btn-ghost btn-sm hide-sm" href="<?= url('login.php') ?>">Sign in</a>
                <a class="btn btn-primary btn-sm" href="<?= url('register.php') ?>">Join the club</a>
            <?php endif; ?>

            <button class="icon-btn nav-toggle" type="button" data-toggle="#main-nav" aria-label="Menu" aria-expanded="false"><?= icon('menu') ?></button>
        </div>
    </div>
</header>
<?php require __DIR__ . '/flash.php'; ?>
<main id="main">
