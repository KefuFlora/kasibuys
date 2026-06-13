<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("UPDATE listings SET status = 'deleted' WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
}

header('Location: dashboard.php');
exit;
?>