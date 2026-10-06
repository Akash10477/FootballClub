<?php
/** Printable e-ticket for a booking. Only its owner (or an admin) can view it. */
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$t = db_one(
    "SELECT o.*, t.category, f.opponent, f.is_home, f.kickoff, f.venue, f.competition, u.name AS holder
     FROM ticket_orders o
     JOIN ticket_types t ON t.id = o.ticket_type_id
     JOIN fixtures f ON f.id = t.fixture_id
     JOIN users u ON u.id = o.user_id
     WHERE o.reference = ?",
    [$_GET['ref'] ?? '']
);
if (!$t || ((int) $t['user_id'] !== (int) $user['id'] && $user['role'] !== 'admin')) {
    http_response_code(404);
    flash('error', 'Ticket not found.');
    redirect('account.php');
}

$pageTitle = 'E-ticket ' . $t['reference'];
require __DIR__ . '/partials/header.php';
?>
<section class="section">
    <div class="container">
        <div class="no-print" style="max-width:720px;margin:0 auto 22px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <a class="link-arrow" href="<?= url('account.php#tickets') ?>"><?= icon('back') ?> My tickets</a>
            <button class="btn btn-ghost btn-sm" onclick="window.print()" type="button"><?= icon('printer') ?> Print / save PDF</button>
        </div>

        <div class="eticket">
            <div class="main">
                <span class="pill pill-gold"><?= e($t['competition']) ?></span>
                <h2 style="margin:16px 0 4px;font-size:2rem">
                    <?= $t['is_home'] ? 'FC Scorpio vs ' . e($t['opponent']) : e($t['opponent']) . ' vs FC Scorpio' ?>
                </h2>
                <p class="muted" style="margin:0"><?= icon('pin') ?> <?= e($t['venue']) ?></p>
                <div class="kv">
                    <div><span>Date</span><strong><?= fmt_date($t['kickoff'], 'j M Y') ?></strong></div>
                    <div><span>Kick-off</span><strong><?= fmt_time($t['kickoff']) ?></strong></div>
                    <div><span>Section</span><strong><?= e($t['category']) ?></strong></div>
                    <div><span>Ticket holder</span><strong><?= e($t['holder']) ?></strong></div>
                    <div><span>Admits</span><strong><?= (int) $t['quantity'] ?> <?= $t['quantity'] > 1 ? 'people' : 'person' ?></strong></div>
                    <div><span>Paid</span><strong><?= money($t['total']) ?></strong></div>
                </div>
            </div>
            <div class="stub">
                <img src="<?= asset('img/crest.png') ?>" alt="" style="width:70px">
                <span class="muted" style="font-size:.72rem;letter-spacing:.16em;text-transform:uppercase">Booking reference</span>
                <span class="ref"><?= e($t['reference']) ?></span>
                <span class="muted" style="font-size:.78rem">Show this at the gate. Gates open 90 minutes before kick-off.</span>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
