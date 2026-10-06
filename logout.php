<?php
require __DIR__ . '/includes/bootstrap.php';

// Logout only via POST (with CSRF token), so a link on another site can't sign users out.
if (is_post()) {
    logout_user();
    session_start();
    flash('success', 'You have been signed out.');
}
redirect('index.php');
