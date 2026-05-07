<?php
// Insert reply into database

header('Content-Type: text/html; charset=utf-8');
require 'db_config.php';

$currentUser = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

if (!$currentUser) {
    redirect('login.php');
}

$newsId = isset($_POST['news_id']) ? intval($_POST['news_id']) : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

// Validation
if ($newsId <= 0) {
    die('無效的討論 ID。<br><a href="index.php">返回</a>');
}

if (empty($content)) {
    die('所有欄位都必須填寫。<br><a href="show_news.php?id=' . $newsId . '">返回</a>');
}

// Check if news exists
try {
    $stmt = $pdo->prepare('SELECT id FROM news WHERE id = ?');
    $stmt->execute([$newsId]);
    if (!$stmt->fetch()) {
        die('找不到此討論。<br><a href="index.php">返回首頁</a>');
    }
} catch (PDOException $e) {
    die('驗證失敗: ' . $e->getMessage());
}

// Limit input length
$content = substr($content, 0, 10000);
$author = substr($currentUser['nickname'], 0, 100);
$author_avatar = $currentUser['avatar'];
$author_color = $currentUser['color'];

try {
    $stmt = $pdo->prepare('INSERT INTO replies (news_id, content, author, author_avatar, author_color, user_id) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$newsId, $content, $author, $author_avatar, $author_color, $currentUser['id']]);

    redirect('show_news.php?id=' . $newsId);
} catch (PDOException $e) {
    die('發表回應失敗: ' . $e->getMessage() . '<br><a href="show_news.php?id=' . $newsId . '">返回</a>');
}
