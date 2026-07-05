<?php
include 'dbconnect.php';
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login_btn'])) {
    header('Location: login.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if (!$email || !$password) {
    header('Location: login.php?error=missing_fields');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php?error=no_account');
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    header('Location: login.php?error=wrong_password');
    exit;
}

// Successful login — populate session
populateSession($user);

// Remember Me cookie (30 days)
if ($remember) {
    setcookie('remember_user', $user['id'], time() + (30 * 24 * 3600), '/', '', false, true);
}

// Redirect admin to manager, everyone else to intended page or index
$next = $_GET['next'] ?? ($_POST['next'] ?? '');
if ($user['role'] === 'admin') {
    header('Location: Manager.php');
} elseif ($next) {
    header('Location: ' . urldecode($next));
} else {
    header('Location: index.php');
}
exit;
