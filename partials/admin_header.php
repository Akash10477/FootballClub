<?php
/** Admin shell. Set $pageTitle and $active before including. */
require __DIR__ . '/head.php';
$user = current_user();
$active = $active ?? '';
$nav = [
    'Overview' => [
        'dashboard' => ['admin/index.php', 'Dashboard', 'grid'],
    ],
    'Club' => [
        'players'  => ['admin/players.php',  'Players',   'users'],
        'fixtures' => ['admin/fixtures.php', 'Fixtures',  'calendar'],
    ],
    'Commerce' => [
        'tickets'  => ['admin/tickets.php',  'Ticket sales', 'ticket'],
        'products' => ['admin/products.php', 'Products',     'shirt'],
        'orders'   => ['admin/orders.php',   'Store orders', 'package'],
    ],
    'People' => [
        'users'    => ['admin/users.php',    'Members',   'user'],
    ],
];
?>
<body class="admin-body">
<aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= url('admin/index.php') ?>">
        <img src="<?= asset('img/crest.png') ?>" alt="">
        <span>FC Scorpio<small>Admin panel</small></span>
    </a>
    <?php foreach ($nav as $section => $items): ?>
        <div class="side-label"><?= e($section) ?></div>
        <nav class="side-nav">
            <?php foreach ($items as $key => [$href, $label, $ico]): ?>
                <a href="<?= url($href) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= icon($ico) ?> <?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endforeach; ?>
    <div class="bottom side-nav">
        <a href="<?= url('index.php') ?>"><?= icon('external') ?> View website</a>
        <form method="post" action="<?= url('logout.php') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-ghost btn-sm btn-block" style="margin-top:8px"><?= icon('logout') ?> Sign out</button>
        </form>
    </div>
</aside>
<div class="admin-main">
    <header class="admin-top">
        <button class="icon-btn admin-toggle" type="button" data-toggle="#sidebar" aria-label="Menu"><?= icon('menu') ?></button>
        <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
        <div class="right">
            <span class="muted nowrap" style="font-size:.88rem"><?= e($user['name']) ?></span>
            <span class="avatar"><?= e(initials($user['name'])) ?></span>
        </div>
    </header>
    <?php require __DIR__ . '/flash.php'; ?>
    <div class="admin-content">
