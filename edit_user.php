<?php
header('Content-Type: text/html; charset=utf-8');
require 'db_config.php';

if (!is_admin()) {
    redirect('index.php');
}

$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($userId <= 0) {
    redirect('admin_users.php');
}

$colors = member_colors();
$avatars = member_avatars();
$errors = [];

try {
    $stmt = $pdo->prepare('SELECT id, username, nickname, color, avatar, is_admin FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        redirect('admin_users.php');
    }
} catch (PDOException $e) {
    die('讀取會員失敗：' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username');
    $nickname = post('nickname');
    $password = post('password');
    $confirm = post('confirm_password');
    $color = sanitize_choice(post_raw('color'), $colors, array_key_first($colors));
    $avatar = sanitize_choice(post_raw('avatar'), $avatars, $avatars[0]);
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;

    if ($username === '') {
        $errors[] = '請輸入帳號。';
    }
    if ($nickname === '') {
        $errors[] = '請輸入暱稱。';
    }
    if ($password !== '' && $password !== $confirm) {
        $errors[] = '密碼與確認密碼不一致。';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
            $stmt->execute([$username, $userId]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = '此帳號已被使用。';
            }
        } catch (PDOException $e) {
            $errors[] = '驗證失敗：' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            if ($password !== '') {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, nickname = ?, color = ?, avatar = ?, is_admin = ?, password_hash = ? WHERE id = ?');
                $stmt->execute([
                    $username,
                    $nickname,
                    $color,
                    $avatar,
                    $isAdmin,
                    password_hash($password, PASSWORD_DEFAULT),
                    $userId,
                ]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, nickname = ?, color = ?, avatar = ?, is_admin = ? WHERE id = ?');
                $stmt->execute([
                    $username,
                    $nickname,
                    $color,
                    $avatar,
                    $isAdmin,
                    $userId,
                ]);
            }
            redirect('admin_users.php');
        } catch (PDOException $e) {
            $errors[] = '更新失敗：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <title>編輯會員 - 討論區</title>
    <style>
        body { font-family: system-ui, -apple-system, Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 520px; margin: 0 auto; }
        .card { background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.12); }
        h1 { margin-top: 0; color: #333; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; color: #333; }
        input[type="text"], input[type="password"], select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { background: #007bff; color: #fff; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { margin-bottom: 16px; color: #d32f2f; }
        .hint a { color: #007bff; text-decoration: none; }
        .hint a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>編輯會員</h1>
            <?php if ($errors): ?>
                <div class="error"><?= escape(implode('<br>', $errors)) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">帳號</label>
                    <input type="text" id="username" name="username" value="<?= escape(isset($_POST['username']) ? $_POST['username'] : $user['username']) ?>" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label for="nickname">暱稱</label>
                    <input type="text" id="nickname" name="nickname" value="<?= escape(isset($_POST['nickname']) ? $_POST['nickname'] : $user['nickname']) ?>" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="password">密碼（留空不修改）</label>
                    <input type="password" id="password" name="password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">確認密碼</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
                <div class="form-group">
                    <label for="color">顏色</label>
                    <select id="color" name="color">
                        <?php $currentColor = isset($_POST['color']) ? $_POST['color'] : $user['color']; ?>
                        <?php foreach ($colors as $label => $hex): ?>
                            <option value="<?= escape($label) ?>" <?= $label === $currentColor ? 'selected' : '' ?>><?= escape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="avatar">大頭貼</label>
                    <select id="avatar" name="avatar">
                        <?php $currentAvatar = isset($_POST['avatar']) ? $_POST['avatar'] : $user['avatar']; ?>
                        <?php foreach ($avatars as $avatar): ?>
                            <option value="<?= escape($avatar) ?>" <?= $avatar === $currentAvatar ? 'selected' : '' ?>><?= escape($avatar) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <?php $isAdminChecked = isset($_POST['is_admin']) ? 1 : $user['is_admin']; ?>
                    <label><input type="checkbox" name="is_admin" value="1" <?= $isAdminChecked ? 'checked' : '' ?>> 設為管理員</label>
                </div>
                <button type="submit">儲存變更</button>
            </form>
            <p class="hint"><a href="admin_users.php">← 回會員管理</a></p>
        </div>
    </div>
</body>
</html>
