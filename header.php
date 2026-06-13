<?php
date_default_timezone_set('Africa/Johannesburg');
if (defined('HEADER_LOADED')) return;
define('HEADER_LOADED', true);

ini_set('memory_limit', '256M');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

if (!defined('LANGUAGES_LOADED')) {
    require_once __DIR__ . '/../config/languages.php';
}

$notif_count = 0;
$msg_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $notif_count = (int)$stmt->fetchColumn();

        $msg_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
        $msg_stmt->execute([$_SESSION['user_id']]);
        $msg_count = (int)$msg_stmt->fetchColumn();
    } catch (Exception $e) {
        $notif_count = 0;
        $msg_count = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KasiBuys</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="/index.php" class="logo">Kasi<span>Buys</span></a>

        <form action="/search.php" method="GET" class="search-bar">
            <input type="text" name="q" placeholder="<?= $t['search_placeholder'] ?>" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>

            <button class="mobile-menu-btn" onclick="toggleMobileMenu()" id="mobile-menu-btn">
    <i class="fas fa-bars"></i>
</button>
        <div class="nav-links">
            <!-- DARK MODE TOGGLE -->
            <button class="dark-mode-toggle" onclick="toggleDarkMode()" id="dark-mode-btn">
                🌙 Dark
            </button>

            <script>
            function toggleDarkMode() {
                const body = document.body;
                const btn = document.getElementById('dark-mode-btn');
                if (body.classList.contains('dark-mode')) {
                    body.classList.remove('dark-mode');
                    btn.textContent = '🌙 Dark';
                    localStorage.setItem('darkMode', 'off');
                } else {
                    body.classList.add('dark-mode');
                    btn.textContent = '☀️ Light';
                    localStorage.setItem('darkMode', 'on');
                }
            }
            (function() {
                const darkMode = localStorage.getItem('darkMode');
                if (darkMode === 'on') {
                    document.documentElement.classList.add('dark-mode');
                    document.addEventListener('DOMContentLoaded', function() {
                        document.body.classList.add('dark-mode');
                        const btn = document.getElementById('dark-mode-btn');
                        if (btn) btn.textContent = '☀️ Light';
                    });
                }
            })();
            </script>
                

            <!-- LANGUAGE SWITCHER -->
            <div style="position:relative;" class="nav-dropdown">
                <button style="background:none;border:1px solid rgba(255,255,255,0.3);color:white;padding:6px 12px;border-radius:20px;cursor:pointer;font-size:0.85rem;">
                    🌍 <?= strtoupper($_SESSION['lang'] ?? 'en') ?>
                </button>
                <div class="dropdown-menu" style="min-width:150px;">
                    <a href="/set-language.php?lang=en">🇿🇦 English</a>
                    <a href="/set-language.php?lang=zu">🇿🇦 IsiZulu</a>
                    <a href="/set-language.php?lang=st">🇿🇦 Sesotho</a>
                    <a href="/set-language.php?lang=ts">🇿🇦 Xitsonga</a>
                </div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/create-listing.php" class="btn-primary"><i class="fas fa-plus"></i> <?= $t['sell'] ?></a>
                <a href="/cart.php"><i class="fas fa-shopping-cart"></i></a>
                <a href="/messages.php" style="position:relative;">
                    <i class="fas fa-envelope" style="color:white;"></i>
                    <?php if ($msg_count > 0): ?>
                        <span class="badge"><?= $msg_count ?></span>
                    <?php endif; ?>
                </a>
                <div class="nav-dropdown">
                    <img src="/<?= $_SESSION['profile_photo'] ?? 'images/default-avatar.png' ?>" class="nav-avatar" alt="Profile">
                    <div class="dropdown-menu">
                        <a href="/dashboard.php"><?= $t['dashboard'] ?></a>
                        <a href="/profile.php?id=<?= $_SESSION['user_id'] ?>"><?= $t['my_profile'] ?></a>
                        <a href="/listings.php?seller=<?= $_SESSION['user_id'] ?>">Manage Listings</a>
                        <a href="/orders.php"><?= $t['my_orders'] ?></a>
                        <a href="/messages.php"><?= $t['messages'] ?>
                            <?php if ($msg_count > 0): ?>
                                <span class="badge" style="position:relative;top:0;right:0;margin-left:5px;"><?= $msg_count ?></span>
                            <?php endif; ?>
                        </a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="/admin/dashboard.php">Admin Panel</a>
                        <?php endif; ?>
                        <a href="/logout.php" class="logout"><?= $t['logout'] ?></a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/login.php" style="color:white;"><?= $t['login'] ?></a>
                <a href="/register.php" class="btn-primary"><?= $t['register'] ?></a>
            <?php endif; ?>
        </div>
    </div>
   <!--Mobile phone toggle-->
     <script>
function toggleMobileMenu() {
    const nav = document.querySelector('.nav-links');
    const btn = document.getElementById('mobile-menu-btn');
    nav.classList.toggle('open');
    btn.innerHTML = nav.classList.contains('open') 
        ? '<i class="fas fa-times"></i>' 
        : '<i class="fas fa-bars"></i>';
}

// Close menu when clicking outside
document.addEventListener('click', function(e) {
    const nav = document.querySelector('.nav-links');
    const btn = document.getElementById('mobile-menu-btn');
    if (nav && btn && !nav.contains(e.target) && !btn.contains(e.target)) {
        nav.classList.remove('open');
        btn.innerHTML = '<i class="fas fa-bars"></i>';
    }
});

// Close menu when a link is clicked
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', function() {
        const nav = document.querySelector('.nav-links');
        const btn = document.getElementById('mobile-menu-btn');
        if (nav && btn) {
            nav.classList.remove('open');
            btn.innerHTML = '<i class="fas fa-bars"></i>';
        }
    });
});
</script>
        
</nav>