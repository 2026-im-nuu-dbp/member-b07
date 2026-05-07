<?php
// Insert new discussion into database

header('Content-Type: text/html; charset=utf-8');
require 'db_config.php';

$currentUser = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

if (!$currentUser) {
    redirect('login.php');
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

// Validation
if (empty($title) || empty($content)) {
    die('所有欄位都必須填寫。<br><a href="index.php">返回</a>');
}

// Limit input length
$title = substr($title, 0, 200);
$content = substr($content, 0, 10000);
$author = substr($currentUser['nickname'], 0, 100);
$author_avatar = $currentUser['avatar'];
$author_color = $currentUser['color'];

try {
    $stmt = $pdo->prepare('INSERT INTO news (title, content, author, author_avatar, author_color, user_id) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$title, $content, $author, $author_avatar, $author_color, $currentUser['id']]);

    redirect('index.php');
} catch (PDOException $e) {
    die('發表討論失敗: ' . $e->getMessage() . '<br><a href="index.php">返回</a>');
}
