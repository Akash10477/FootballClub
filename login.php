<?php
require __DIR__ . '/includes/bootstrap.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null, '');
if (is_logged_in()) {
    redirect($next ?: (is_admin() ? 'admin/index.php' : 'account.php'));
}

$error = '';
if (is_post()) {
    $email = mb_strtolower(input('email'));
    $user  = db_one('SELECT * FROM users WHERE email = ?', [$email]);

    // Same message for wrong email or wrong password, so attackers can't discover accounts.
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            db_query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($_POST['password'], PASSWORD_DEFAULT), $user['id']]);
        }
        login_user($user);
        flash('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '!');
        redirect($next ?: ($user['role'] === 'admin' ? 'admin/index.php' : 'index.php'));
    }
    $error = 'Incorrect email or password.';
}

$pageTitle = 'Sign in';
require __DIR__ . '/partials/header.php';
?>
<div class="auth-wrap">
    <div class="auth-side">
        <div class="hero-bg" style="background-image:url('<?= asset('img/goal-net.jpg') ?>')"></div>
        <blockquote>Train hard. Play harder. <span>Dream the hardest.</span></blockquote>
    </div>
    <div class="auth-main">
        <div class="auth-card">
            <div class="eyebrow">Members area</div>
            <h1>Sign in</h1>
            <p class="sub">Fans and club staff use the same sign-in.</p>

            <?php if ($error): ?><div class="alert alert-error"><?= icon('alert') ?><?= e($error) ?></div><?php endif; ?>

            <form method="post" class="form-grid" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">
                <div class="field">
                    <label for="email">Email</label>
                    <input class="input" type="email" id="email" name="email" value="<?= old('email') ?>" required autocomplete="email" autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input class="input" type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button class="btn btn-primary btn-lg btn-block" type="submit">Sign in</button>
            </form>
            <p class="muted" style="margin-top:22px">New to FC Scorpio? <a class="gold" href="<?= url('register.php' . ($next ? '?next=' . urlencode($next) : '')) ?>"><strong>Create an account</strong></a></p>

            <div class="demo-box">
                <strong>Demo accounts</strong><br>
                Admin: <code>admin@fcscorpio.com</code> / <code>Admin@123</code><br>
                Fan: <code>fan@fcscorpio.com</code> / <code>Fan@12345</code>
            </div>
        </div>
    </div>
</div>
</main>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
