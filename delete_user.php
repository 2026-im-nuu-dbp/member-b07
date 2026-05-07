<?php
header('Content-Type: text/html; charset=utf-8');
require 'db_config.php';

if (!is_admin()) {
    redirect('index.php');
}

$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$currentUser = current_user();

if ($userId <= 0 || $userId === $currentUser['id']) {
    redirect('admin_users.php');
}

try {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$userId]);
} catch (PDOException $e) {
    die('刪除失敗：' . $e->getMessage());
}

redirect('admin_users.php');
