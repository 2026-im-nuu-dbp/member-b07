<?php
header('Content-Type: text/html; charset=utf-8');
require 'db_config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username');
    $password = post('password');

    if ($username === '' || $password === '') {
        $errors[] = '請輸入帳號與密碼。';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                redirect('index.php');
            }
            $errors[] = '帳號或密碼錯誤。';
        } catch (PDOException $e) {
            $errors[] = '登入失敗：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <title>會員登入 - 討論區</title>
    <style>
        body { font-family: system-ui, -apple-system, Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 520px; margin: 0 auto; }
        .card { background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.12); }
        h1 { margin-top: 0; color: #333; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; color: #333; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
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
            <h1>會員登入</h1>
            <?php if ($errors): ?>
                <div class="error"><?= escape(implode('<br>', $errors)) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">帳號</label>
                    <input type="text" id="username" name="username" value="<?= escape($username) ?>" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label for="password">密碼</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">登入</button>
            </form>
            <p class="hint">還沒有帳號？<a href="register.php">立即註冊</a></p>
        </div>
    </div>
</body>
</html>
