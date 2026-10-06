</main>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="about">
                <a class="brand" href="<?= url('index.php') ?>">
                    <img src="<?= asset('img/crest.png') ?>" alt="">
                    <span>FC Scorpio<small>Official website</small></span>
                </a>
                <p>More than a football club: a community of players, supporters and dreamers united by the beautiful game.</p>
            </div>
            <div>
                <h4>Club</h4>
                <ul>
                    <li><a href="<?= url('about.php') ?>">About us</a></li>
                    <li><a href="<?= url('squad.php') ?>">First team</a></li>
                    <li><a href="<?= url('fixtures.php') ?>">Fixtures &amp; results</a></li>
                </ul>
            </div>
            <div>
                <h4>Fans</h4>
                <ul>
                    <li><a href="<?= url('tickets.php') ?>">Match tickets</a></li>
                    <li><a href="<?= url('shop.php') ?>">Official store</a></li>
                    <li><a href="<?= url(is_logged_in() ? 'account.php' : 'register.php') ?>"><?= is_logged_in() ? 'My account' : 'Become a member' ?></a></li>
                </ul>
            </div>
            <div>
                <h4>Contact</h4>
                <ul>
                    <li class="contact-line"><?= icon('pin') ?> Scorpio Arena, Dhaka</li>
                    <li class="contact-line"><?= icon('mail') ?> hello@fcscorpio.com</li>
                    <li class="contact-line"><?= icon('phone') ?> +880 1700-000000</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© <?= date('Y') ?> FC Scorpio. All rights reserved.</span>
            <span>Together, we are unstoppable.</span>
        </div>
    </div>
</footer>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
