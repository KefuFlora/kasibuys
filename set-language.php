<?php
session_start();
$allowed = ['en', 'zu', 'st', 'ts'];
$lang = $_GET['lang'] ?? 'en';
if (in_array($lang, $allowed)) {
    $_SESSION['lang'] = $lang;
}
$redirect = $_SERVER['HTTP_REFERER'] ?? '/index.php';
header("Location: $redirect");
exit;
?>
