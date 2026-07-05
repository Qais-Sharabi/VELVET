<?php
include 'dbconnect.php';
include 'auth.php';

requireLogin();

$user_id = currentUserId();
$back    = $_SERVER['HTTP_REFERER'] ?? 'index.php';

// ADD
if (isset($_POST['add_to_wishlist'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    if ($product_id) {
        $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)")
            ->execute([$user_id, $product_id]);
    }
    header("Location: $back");
    exit;
}

// REMOVE
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $wish_id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?")
        ->execute([$wish_id, $user_id]);
    header("Location: $back");
    exit;
}

header("Location: index.php");
exit;
