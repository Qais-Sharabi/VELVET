<?php
// 1. Load database and session helpers
include 'dbconnect.php';
include 'auth.php'; // Required to access $_SESSION['user_id']
/** @var PDO $pdo */

// 2. Auth check using your existing helper function
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = (int)currentUserId(); // Using your helper to get the ID
$action  = $_GET['action'] ?? ($_POST['action'] ?? '');

// ── ADD TO BAG LOGIC ─────────────────────────────────────────────
// Check for product_id instead of just the button name for better reliability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {

    $product_id = (int)$_POST['product_id'];
    $variant_id = (int)($_POST['variant_id'] ?? 0);
    $quantity   = max(1, (int)($_POST['qty'] ?? 1));

    if (!$product_id) {
        header("Location: shop.php");
        exit();
    }

    // Validate the variant exists for this specific product
    if ($variant_id > 0) {
        $chk = $pdo->prepare("SELECT id, stock_qty FROM product_variants WHERE id = ? AND product_id = ?");
        $chk->execute([$variant_id, $product_id]);
        $variant = $chk->fetch();

        if (!$variant) {
            header("Location: product_details.php?slug=" . $_POST['slug'] . "&cart_error=invalid_variant");
            exit();
        }

        // Double check stock before adding
        if ($variant['stock_qty'] < $quantity) {
            header("Location: product_details.php?slug=" . $_POST['slug'] . "&cart_error=out_of_stock");
            exit();
        }
    } else {
        // Fallback: If no variant ID was sent, try to find a default one
        $sv = $pdo->prepare("SELECT id FROM product_variants WHERE product_id = ? AND stock_qty > 0 LIMIT 1");
        $sv->execute([$product_id]);
        $variant_id = (int)$sv->fetchColumn();
    }

    if ($variant_id < 1) {
        header("Location: product_details.php?slug=" . $_POST['slug'] . "&cart_error=no_variants");
        exit();
    }

    // Insert or Update Quantity
    // Using ON DUPLICATE KEY ensures that if the user adds the same item again,
    // it just increases the count instead of failing.
    $stmt = $pdo->prepare("
        INSERT INTO cart_items (user_id, product_id, variant_id, quantity)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + ?
    ");

    try {
        $stmt->execute([$user_id, $product_id, $variant_id, $quantity, $quantity]);
        // Redirect back with success message for the Toast
        header("Location: product_details.php?slug=" . $_POST['slug'] . "&cart_success=1");
    } catch (Exception $e) {
        header("Location: product_details.php?slug=" . $_POST['slug'] . "&cart_error=db_error");
    }
    exit();
}

// ── REMOVE ITEM ──────────────────────────────────────────────────
if ($action === 'remove' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
    header("Location: shopping bag.php");
    exit();
}

// ── INCREASE QTY ─────────────────────────────────────────────────
if ($action === 'increase' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sv = $pdo->prepare("
        SELECT pv.stock_qty, ci.quantity
        FROM cart_items ci 
        JOIN product_variants pv ON ci.variant_id = pv.id
        WHERE ci.id = ? AND ci.user_id = ?
    ");
    $sv->execute([$id, $user_id]);
    $row = $sv->fetch();

    if ($row && $row['quantity'] < $row['stock_qty']) {
        $pdo->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE id = ? AND user_id = ?")
            ->execute([$id, $user_id]);
    }
    header("Location: shopping bag.php");
    exit();
}

// ── DECREASE QTY ─────────────────────────────────────────────────
if ($action === 'decrease' && isset($_GET['id'])) {
    $id  = (int)$_GET['id'];
    $sv  = $pdo->prepare("SELECT quantity FROM cart_items WHERE id = ? AND user_id = ?");
    $sv->execute([$id, $user_id]);
    $row = $sv->fetch();

    if ($row) {
        if ($row['quantity'] <= 1) {
            $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
        } else {
            $pdo->prepare("UPDATE cart_items SET quantity = quantity - 1 WHERE id = ? AND user_id = ?")
                ->execute([$id, $user_id]);
        }
    }
    header("Location: shopping bag.php");
    exit();
}

// Final Fallback
header("Location: index.php");
exit();