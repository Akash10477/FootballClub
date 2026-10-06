<?php
require __DIR__ . '/includes/bootstrap.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null, '');
if (is_logged_in()) {
    redirect($next ?: 'account.php');
}

$errors = [];
if (is_post()) {
    $name     = input('name');
    $email    = mb_strtolower(input('email'));
    $phone    = input('phone');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirmation'] ?? '';

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100)  $errors['name'] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))       $errors['email'] = 'Please enter a valid email address.';
    elseif (db_value('SELECT 1 FROM users WHERE email = ?', [$email])) $errors['email'] = 'An account with this email already exists.';
    if ($phone !== '' && !preg_match('/^\+?[0-9\s-]{10,15}$/', $phone)) $errors['phone'] = 'Please enter a valid phone number.';
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Use at least 8 characters, including a letter and a number.';
    }
    if ($password !== $confirm) $errors['password_confirmation'] = 'Passwords do not match.';

    if (!$errors) {
        // New sign-ups are always fans; admins are promoted from the admin panel.
        db_query('INSERT INTO users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)',
            [$name, $email, $phone ?: null, password_hash($password, PASSWORD_DEFAULT)]);
        login_user(['id' => db()->lastInsertId()]);
        flash('success', "Welcome to the Scorpio family, $name!");
        redirect($next ?: 'index.php');
    }
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<span class="field-error">' . e($errors[$key]) . '</span>' : '';
}

$pageTitle = 'Create account';
require __DIR__ . '/partials/header.php';
?>
<div class="auth-wrap">
    <div class="auth-side">
        <div class="hero-bg" style="background-image:url('<?= asset('img/stands.jpg') ?>')"></div>
        <blockquote>Join us. Cheer with us. <span>Grow with us.</span></blockquote>
    </div>
    <div class="auth-main">
        <div class="auth-card">
            <div class="eyebrow">Free membership</div>
            <h1>Create account</h1>
            <p class="sub">Book match tickets, shop the store and keep your e-tickets in one place.</p>

            <form method="post" class="form-grid" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">
                <div class="field">
                    <label for="name">Full name</label>
                    <input class="input <?= isset($errors['name']) ? 'has-error' : '' ?>" id="name" name="name" value="<?= old('name') ?>" required autocomplete="name">
                    <?= field_error($errors, 'name') ?>
                </div>
                <div class="form-grid cols-2">
                    <div class="field">
                        <label for="email">Email</label>
                        <input class="input <?= isset($errors['email']) ? 'has-error' : '' ?>" type="email" id="email" name="email" value="<?= old('email') ?>" required autocomplete="email">
                        <?= field_error($errors, 'email') ?>
                    </div>
                    <div class="field">
                        <label for="phone">Phone <span class="faint">(optional)</span></label>
                        <input class="input <?= isset($errors['phone']) ? 'has-error' : '' ?>" id="phone" name="phone" value="<?= old('phone') ?>" placeholder="01XXXXXXXXX" autocomplete="tel">
                        <?= field_error($errors, 'phone') ?>
                    </div>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input class="input <?= isset($errors['password']) ? 'has-error' : '' ?>" type="password" id="password" name="password" required autocomplete="new-password">
                    <?= field_error($errors, 'password') ?: '<span class="hint">At least 8 characters, with a letter and a number.</span>' ?>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <input class="input <?= isset($errors['password_confirmation']) ? 'has-error' : '' ?>" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                    <?= field_error($errors, 'password_confirmation') ?>
                </div>
                <button class="btn btn-primary btn-lg btn-block" type="submit">Create account</button>
            </form>
            <p class="muted" style="margin-top:22px">Already a member? <a class="gold" href="<?= url('login.php') ?>"><strong>Sign in</strong></a></p>
        </div>
    </div>
</div>
</main>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
