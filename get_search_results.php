<?php
include 'dbconnect.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo '[]'; exit; }

$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.slug, p.base_price, p.sale_price, p.is_sale,
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary=1 LIMIT 1) as image_url
    FROM products p
    WHERE p.is_active = 1 AND (p.name LIKE ? OR p.description LIKE ?)
    ORDER BY p.created_at DESC LIMIT 8
");
$stmt->execute(['%'.$q.'%', '%'.$q.'%']);
echo json_encode($stmt->fetchAll());
