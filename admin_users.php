<?php
header('Content-Type: text/html; charset=utf-8');
require 'db_config.php';

if (!is_admin()) {
    redirect('index.php');
}

$colors = member_colors();
$avatars = member_avatars();
$errors = [];
$success = '';
$newUser = [
    'username' => '',
    'nickname' => '',
    'color' => array_key_first($colors),
    'avatar' => $avatars[0],
    'is_admin' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUser['username'] = post('username');
    $password = post('password');
    $confirm = post('confirm_password');
    $newUser['nickname'] = post('nickname');
    $newUser['color'] = sanitize_choice(post_raw('color'), $colors, array_key_first($colors));
    $newUser['avatar'] = sanitize_choice(post_raw('avatar'), $avatars, $avatars[0]);
    $newUser['is_admin'] = isset($_POST['is_admin']) ? 1 : 0;

    if ($newUser['username'] === '') {
        $errors[] = '請輸入帳號。';
    }
    if ($password === '') {
        $errors[] = '請輸入密碼。';
    }
    if ($password !== $confirm) {
        $errors[] = '密碼與確認密碼不一致。';
    }
    if ($newUser['nickname'] === '') {
        $errors[] = '請輸入暱稱。';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
            $stmt->execute([$newUser['username']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = '此帳號已存在。';
            }
        } catch (PDOException $e) {
            $errors[] = '驗證失敗：' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, nickname, color, avatar, is_admin) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $newUser['username'],
                password_hash($password, PASSWORD_DEFAULT),
                $newUser['nickname'],
                $newUser['color'],
                $newUser['avatar'],
                $newUser['is_admin'],
            ]);
            $success = '已新增會員。';
            $newUser = [
                'username' => '',
                'nickname' => '',
                'color' => array_key_first($colors),
                'avatar' => $avatars[0],
                'is_admin' => 0,
            ];
        } catch (PDOException $e) {
            $errors[] = '新增會員失敗：' . $e->getMessage();
        }
    }
}

try {
    $stmt = $pdo->query('SELECT id, username, nickname, color, avatar, is_admin, created_at FROM users ORDER BY created_at DESC');
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = '讀取會員失敗：' . $e->getMessage();
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <title>會員管理 - 討論區</title>
    <style>
        body { font-family: system-ui, -apple-system, Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header a { color: #007bff; text-decoration: none; }
        .header a:hover { text-decoration: underline; }
        .card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.12); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f8f9ff; }
        .actions a { margin-right: 12px; color: #007bff; text-decoration: none; }
        .actions a:hover { text-decoration: underline; }
        .form-group { margin-bottom: 14px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; color: #333; }
        input[type="text"], input[type="password"], select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { background: #007bff; color: #fff; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: #d32f2f; margin-bottom: 12px; }
        .success { color: #2a7f2a; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>會員管理</h1>
            <div>
                <a href="index.php">回討論區</a>
                <a href="logout.php">登出</a>
            </div>
        </div>

        <?php if ($errors): ?>
            <div class="error"><?= escape(implode('<br>', $errors)) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= escape($success) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>現有會員</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>帳號</th>
                        <th>暱稱</th>
                        <th>大頭貼</th>
                        <th>顏色</th>
                        <th>管理員</th>
                        <th>建立時間</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= escape($user['id']) ?></td>
                            <td><?= escape($user['username']) ?></td>
                            <td><?= escape($user['nickname']) ?></td>
                            <td><?= escape($user['avatar']) ?></td>
                            <td><?= escape($user['color']) ?></td>
                            <td><?= $user['is_admin'] ? '是' : '否' ?></td>
                            <td><?= escape($user['created_at']) ?></td>
                            <td class="actions">
                                <a href="edit_user.php?id=<?= $user['id'] ?>">編輯</a>
                                <?php if ($user['id'] !== current_user()['id']): ?>
                                    <a href="delete_user.php?id=<?= $user['id'] ?>" onclick="return confirm('確定刪除此會員？');">刪除</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>新增會員</h2>
            <form method="post">
                <div class="form-group">
                    <label for="username">帳號</label>
                    <input type="text" id="username" name="username" value="<?= escape($newUser['username']) ?>" maxlength="50" required>
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
                    <input type="text" id="nickname" name="nickname" value="<?= escape($newUser['nickname']) ?>" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="color">顏色</label>
                    <select id="color" name="color">
                        <?php foreach ($colors as $label => $hex): ?>
                            <option value="<?= escape($label) ?>" <?= $label === $newUser['color'] ? 'selected' : '' ?>><?= escape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="avatar">大頭貼</label>
                    <select id="avatar" name="avatar">
                        <?php foreach ($avatars as $avatar): ?>
                            <option value="<?= escape($avatar) ?>" <?= $avatar === $newUser['avatar'] ? 'selected' : '' ?>><?= escape($avatar) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="is_admin" value="1" <?= $newUser['is_admin'] ? 'checked' : '' ?>> 設為管理員</label>
                </div>
                <button type="submit">新增會員</button>
            </form>
        </div>
    </div>
</body>
</html>
