<?php
// Database configuration

session_start();

$host = 'localhost';
$db = 'tests_db';
$user = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('資料庫連線失敗: ' . $e->getMessage());
}

function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function post($key, $default = '')
{
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function post_raw($key, $default = '')
{
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function member_colors()
{
    return [
        '紅色' => '#ffdddd',
        '綠色' => '#ddffdd',
        '藍色' => '#ddddff',
        '黃色' => '#fff0b3',
        '水色' => '#d0f0ff',
    ];
}

function member_color_hex($color)
{
    $colors = member_colors();
    if (array_key_exists($color, $colors)) {
        return $colors[$color];
    }
    if (in_array($color, $colors, true)) {
        return $color;
    }
    return reset($colors);
}

function member_avatars()
{
    return ['😄', '😎', '🐱', '🐶', '🦊'];
}

function sanitize_choice($value, array $options, $default)
{
    if (array_key_exists($value, $options)) {
        return $value;
    }
    return in_array($value, $options, true) ? $value : $default;
}

function current_user()
{
    global $pdo;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;

    if ($user === null) {
        $stmt = $pdo->prepare('SELECT id, username, nickname, color, avatar, is_admin FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            unset($_SESSION['user_id']);
            return null;
        }
    }

    return $user;
}

function is_logged_in()
{
    return current_user() !== null;
}

function is_admin()
{
    $user = current_user();
    return $user && $user['is_admin'];
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}
