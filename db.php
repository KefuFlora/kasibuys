<?php
$host = 'fdb1032.awardspace.net';
$dbname = '4765725_kasibuys';
$username = '4765725_kasibuys';
$password = 'God@First_19';

try {
    if (!extension_loaded('pdo_mysql')) {
        throw new Exception('PDO MySQL driver not available');
    }
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET time_zone = '+02:00'");
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}