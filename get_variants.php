<?php
// ============================================================
//  get_variants.php
//  Called by Manager.js when opening edit modal
//  Returns JSON array of variants for a given product_id
// ============================================================
header('Content-Type: application/json');
include 'dbconnect.php';
/** @var PDO $pdo */
$product_id = (int)($_GET['product_id'] ?? 0);
if (!$product_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT size, color, color_hex, stock_qty
    FROM   product_variants
    WHERE  product_id = ?
    ORDER  BY id ASC
");
$stmt->execute([$product_id]);
echo json_encode($stmt->fetchAll());
