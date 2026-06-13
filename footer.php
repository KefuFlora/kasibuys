<?php
if (!defined('LANGUAGES_LOADED')) {
    require_once __DIR__ . '/../config/languages.php';
}
?>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-brand">
            <h2>Kasi<span>Buys</span></h2>
            <p>South Africa's local buy & sell KasiBuys.</p>
        </div>
<div class="footer-links">
    <h4><?= $t['marketplace'] ?></h4>
    <a href="/index.php">Home</a>
    <a href="/listings.php"><?= $t['browse'] ?></a>
    <a href="/create-listing.php"><?= $t['sell'] ?></a>
</div>
<div class="footer-links">
    <h4><?= $t['account'] ?></h4>
    <?php if (!isset($_SESSION['user_id'])): ?>
    <a href="/register.php"><?= $t['register'] ?></a>
    <a href="/login.php"><?= $t['login'] ?></a>
<?php else: ?>
    <a href="/dashboard.php"><?= $t['dashboard'] ?></a>
    <a href="/logout.php"><?= $t['logout'] ?></a>
<?php endif; ?>
    <a href="/dashboard.php"><?= $t['dashboard'] ?></a>
</div>
<div class="footer-links">
    <h4><?= $t['support'] ?></h4>
    <a href="/help-centre.php"><?= $t['help'] ?></a>
    <a href="/contact.php"><?= $t['contact'] ?></a>
    <a href="/report.php"><?= $t['report'] ?></a>
</div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> KasiBuys. All rights reserved.</p>
    </div>
</footer>
<script src="/js/main.js"></script>
</body>
</html>