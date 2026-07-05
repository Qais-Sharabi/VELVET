<?php
// ── Start session once ────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Database Connection (PDO) ─────────────────────────────────
$host    = 'localhost';
$dbname  = 'velvet_shop';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn     = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// ── Remember Me — auto-login from cookie ─────────────────────
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    $remembered_id = (int)$_COOKIE['remember_user'];
    if ($remembered_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$remembered_id]);
        $rememberedUser = $stmt->fetch();
        if ($rememberedUser) {
            $_SESSION['user_id']     = $rememberedUser['id'];
            $_SESSION['user_name']   = $rememberedUser['full_name'];
            $_SESSION['user_letter'] = strtoupper(substr($rememberedUser['full_name'], 0, 1));
            $_SESSION['role']        = $rememberedUser['role'];
        }
    }
}
