<?php
header('Content-Type: text/html; charset=utf-8');
require 'db_config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$colors = member_colors();
$avatars = member_avatars();
$errors = [];
$values = [
    'username' => '',
    'nickname' => '',
    'color' => array_key_first($colors),
    'avatar' => $avatars[0],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['username'] = post('username');
    $password = post('password');
    $confirm = post('confirm_password');
    $values['nickname'] = post('nickname');
    $values['color'] = sanitize_choice(post_raw('color'), $colors, array_key_first($colors));
    $values['avatar'] = sanitize_choice(post_raw('avatar'), $avatars, $avatars[0]);

    if ($values['username'] === '') {
        $errors[] = '請輸入帳號。';
    }
    if ($values['nickname'] === '') {
        $errors[] = '請輸入暱稱。';
    }
    if ($password === '') {
        $errors[] = '請輸入密碼。';
    }
    if ($password !== $confirm) {
        $errors[] = '密碼與確認密碼不一致。';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
            $stmt->execute([$values['username']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = '此帳號已被使用。';
            }
        } catch (PDOException $e) {
            $errors[] = '驗證失敗：' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->query('SELECT COUNT(*) FROM users');
            $userCount = (int) $stmt->fetchColumn();
            $isAdmin = $userCount === 0 ? 1 : 0;

            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, nickname, color, avatar, is_admin) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $values['username'], 
                password_hash($password, PASSWORD_DEFAULT),
                $values['nickname'], 
                $values['color'], 
                $values['avatar'], 
                $isAdmin,
            ]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            redirect('index.php');
        } catch (PDOException $e) {
            $errors[] = '註冊失敗：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <title>會員註冊 - 討論區</title>
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
        .hint { margin-top: 14px; }
        .hint a { color: #007bff; text-decoration: none; }
        .hint a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>會員註冊</h1>
            <?php if ($errors): ?>
                <div class="error"><?= escape(implode('<br>', $errors)) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">帳號</label>
                    <input type="text" id="username" name="username" value="<?= escape($values['username']) ?>" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label for="password">密碼</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">確認密碼</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <label for="nickname">暱稱</label>
                    <input type="text" id="nickname" name="nickname" value="<?= escape($values['nickname']) ?>" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="color">喜歡顏色</label>
                    <select id="color" name="color">
                        <?php foreach ($colors as $label => $hex): ?>
                            <option value="<?= escape($label) ?>" <?= $label === $values['color'] ? 'selected' : '' ?>><?= escape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="avatar">大頭貼</label>
                    <select id="avatar" name="avatar">
                        <?php foreach ($avatars as $avatar): ?>
                            <option value="<?= escape($avatar) ?>" <?= $avatar === $values['avatar'] ? 'selected' : '' ?>><?= escape($avatar) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">註冊</button>
            </form>
            <p class="hint">已經有帳號？<a href="login.php">前往登入</a></p>
        </div>
    </div>
</body>
</html>
