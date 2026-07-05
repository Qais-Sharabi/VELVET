<?php
// ============================================================
//  get_images.php
//  Returns all images for a product as JSON
//  Called by Manager.js when opening edit modal
// ============================================================
header('Content-Type: application/json');
include 'dbconnect.php';
/** @var PDO $pdo */
$product_id = (int)($_GET['product_id'] ?? 0);
if (!$product_id) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("
    SELECT id, image_url, is_primary, sort_order
    FROM   product_images
    WHERE  product_id = ?
    ORDER  BY sort_order ASC, id ASC
");
$stmt->execute([$product_id]);
echo json_encode($stmt->fetchAll());
