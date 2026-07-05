<?php
// ============================================================
//  process_add_product.php — Final Version
//  Handles add, edit (with selective image deletion), delete
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

$log = [];

// ── Collect POST ─────────────────────────────────────────────
$action         = trim($_POST['action']         ?? '');
$name           = trim($_POST['name']           ?? '');
$gender         = trim($_POST['gender']         ?? '');
$classification = trim($_POST['classification'] ?? '');
$price          = (float)($_POST['price']       ?? 0);
$sale_raw       = trim($_POST['sale_price']     ?? '');
$sale_price     = ($sale_raw !== '' && $sale_raw !== '0' && (float)$sale_raw > 0)
    ? (float)$sale_raw : null;
$is_sale        = $sale_price !== null ? 1 : 0;
$is_new         = (int)($_POST['is_new']        ?? 0);
$is_bestseller  = (int)($_POST['is_bestseller'] ?? 0);
$is_active      = (int)($_POST['is_active']     ?? 1);
$description    = trim($_POST['description']    ?? '');
$product_id     = (int)($_POST['product_id']    ?? 0);

// Image IDs to delete / keep (sent by Manager.js in edit mode)
$delete_image_ids = json_decode($_POST['delete_image_ids'] ?? '[]', true) ?: [];
$keep_image_ids   = json_decode($_POST['keep_image_ids']   ?? '[]', true) ?: [];

$log[] = "action=$action name='$name' gender=$gender class=$classification price=$price";
$log[] = "is_new=$is_new is_bestseller=$is_bestseller is_active=$is_active";
$log[] = "delete_image_ids=" . implode(',', $delete_image_ids);
$log[] = "keep_image_ids="   . implode(',', $keep_image_ids);
$log[] = "variants=" . ($_POST['variants'] ?? 'none');

// ── DB ────────────────────────────────────────────────────────
try {
    include 'dbconnect.php';
    /** @var PDO $pdo */
    $log[] = "DB: connected";
} catch (Exception $e) {
    echo "DB CONNECTION FAILED: " . $e->getMessage(); exit;
}

// ── DELETE product ────────────────────────────────────────────
if ($action === 'delete') {
    if (!$product_id) { echo "ERROR: no product_id"; exit; }
    try {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$product_id]);
        echo "SUCCESS_PRODUCT_DELETED";
    } catch (Exception $e) {
        echo "DELETE ERROR: " . $e->getMessage();
    }
    exit;
}

// ── Validate ──────────────────────────────────────────────────
if (!$name || !$gender || !$classification || $price <= 0) {
    echo "ERROR: Missing fields. name='$name' gender='$gender' class='$classification' price=$price";
    exit;
}

// ── Category ──────────────────────────────────────────────────
$cat_slug = $gender . '-' . $classification;
try {
    $cs = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
    $cs->execute([$cat_slug]);
    $cat = $cs->fetch();
    if (!$cat) {
        $all = $pdo->query("SELECT slug FROM categories")->fetchAll(PDO::FETCH_COLUMN);
        echo "ERROR: Category '$cat_slug' not found. Available: " . implode(', ', $all);
        exit;
    }
    $category_id = $cat['id'];
    $log[] = "Category id=$category_id";
} catch (Exception $e) {
    echo "CATEGORY ERROR: " . $e->getMessage(); exit;
}

// ── Unique slug ───────────────────────────────────────────────
$slug_base = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
$slug = $slug_base; $counter = 1;
while (true) {
    $sc = $pdo->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
    $sc->execute([$slug, $product_id]);
    if (!$sc->fetch()) break;
    $slug = $slug_base . '-' . (++$counter);
}
$log[] = "Slug: '$slug'";

// ── Transaction ───────────────────────────────────────────────
try {
    $pdo->beginTransaction();

    // ── INSERT or UPDATE ──────────────────────────────────────
    if ($action === 'add') {
        $st = $pdo->prepare("
            INSERT INTO products
                (category_id, name, slug, description, base_price, sale_price,
                 is_sale, is_new, is_bestseller, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([
            $category_id, $name, $slug, $description,
            $price, $sale_price, $is_sale, $is_new, $is_bestseller, $is_active
        ]);
        $product_id = (int)$pdo->lastInsertId();
        $log[] = "Product INSERTED id=$product_id";

    } elseif ($action === 'edit') {
        if (!$product_id) throw new Exception("No product_id for edit");
        $pdo->prepare("
            UPDATE products SET
                category_id=?, name=?, slug=?, description=?,
                base_price=?, sale_price=?, is_sale=?,
                is_new=?, is_bestseller=?, is_active=?
            WHERE id=?
        ")->execute([
            $category_id, $name, $slug, $description,
            $price, $sale_price, $is_sale,
            $is_new, $is_bestseller, $is_active,
            $product_id
        ]);
        $log[] = "Product UPDATED id=$product_id";
    }

    // ── Images ────────────────────────────────────────────────
    // Step A: Delete images the admin removed (clicked × on)
    if (!empty($delete_image_ids)) {
        foreach ($delete_image_ids as $img_id) {
            // Get the file path so we can delete the physical file too
            $imgRow = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ? AND product_id = ?");
            $imgRow->execute([(int)$img_id, $product_id]);
            $imgData = $imgRow->fetch();
            if ($imgData && file_exists($imgData['image_url'])) {
                unlink($imgData['image_url']);  // remove physical file
            }
            $pdo->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?")
                ->execute([(int)$img_id, $product_id]);
            $log[] = "Deleted image id=$img_id";
        }
    }

    // Step B: Upload new images (files sent in this request)
    $newFilesUploaded = 0;
    if (!empty($_FILES['product_images']['name'][0])) {
        $subfolder  = ($gender === 'women') ? 'women/' : 'men/';
        $target_dir = 'images/' . $subfolder;
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $allowed   = ['jpg','jpeg','png','webp','avif','gif'];
        $fileCount = count($_FILES['product_images']['tmp_name']);

        // Determine next sort_order (after existing kept images)
        $maxOrder = $pdo->prepare("SELECT COALESCE(MAX(sort_order),- 1) FROM product_images WHERE product_id=?");
        $maxOrder->execute([$product_id]);
        $nextOrder = (int)$maxOrder->fetchColumn() + 1;

        for ($i = 0; $i < $fileCount; $i++) {
            $tmp = $_FILES['product_images']['tmp_name'][$i];
            $err = $_FILES['product_images']['error'][$i];
            if ($err !== UPLOAD_ERR_OK) { $log[] = "Image $i: upload error $err"; continue; }

            $ext = strtolower(pathinfo($_FILES['product_images']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) { $log[] = "Image $i: bad ext $ext"; continue; }

            $filename  = 'prod_' . $product_id . '_' . time() . '_' . $i . '.' . $ext;
            $full_path = $target_dir . $filename;
            $db_path   = 'images/' . $subfolder . $filename;

            if (move_uploaded_file($tmp, $full_path)) {
                // is_primary = 1 only if no other images exist for this product yet
                $existingCount = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id=?");
                $existingCount->execute([$product_id]);
                $isPrimary = ($existingCount->fetchColumn() == 0) ? 1 : 0;

                $pdo->prepare("
                    INSERT INTO product_images (product_id, image_url, is_primary, sort_order)
                    VALUES (?, ?, ?, ?)
                ")->execute([$product_id, $db_path, $isPrimary, $nextOrder++]);
                $newFilesUploaded++;
                $log[] = "New image saved: $db_path (primary=$isPrimary)";
            } else {
                $log[] = "Image $i: move_uploaded_file FAILED — check permissions on $target_dir";
            }
        }
    }
    $log[] = "New files uploaded: $newFilesUploaded";

    // Step C: For add mode with no images, that's fine — no images is allowed
    // Step D: Ensure exactly one is_primary per product
    $primCount = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id=? AND is_primary=1");
    $primCount->execute([$product_id]);
    if ((int)$primCount->fetchColumn() === 0) {
        // No primary set — make the first image primary
        $first = $pdo->prepare("SELECT id FROM product_images WHERE product_id=? ORDER BY sort_order, id LIMIT 1");
        $first->execute([$product_id]);
        $firstImg = $first->fetch();
        if ($firstImg) {
            $pdo->prepare("UPDATE product_images SET is_primary=1 WHERE id=?")->execute([$firstImg['id']]);
            $log[] = "Set image id={$firstImg['id']} as primary";
        }
    }

    // ── Variants ──────────────────────────────────────────────
    $variants_json = $_POST['variants'] ?? '[]';
    $variants      = json_decode($variants_json, true);
    $log[]         = "Variants count: " . count($variants ?? []);

    if (!empty($variants) && is_array($variants)) {
        // Always replace all variants on save (clean approach)
        $pdo->prepare("DELETE FROM product_variants WHERE product_id=?")->execute([$product_id]);

        $vstmt = $pdo->prepare("
            INSERT INTO product_variants (product_id, size, color, color_hex, price, stock_qty, sku)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($variants as $i => $v) {
            $size       = trim($v['size']      ?? '');
            // FIX: color name and hex come as separate fields — never guess one from the other
            $color_name = trim($v['color']     ?? '');
            $color_hex  = trim($v['color_hex'] ?? '');
            $qty        = (int)($v['quantity']  ?? 0);

            if (!$size || !$color_name) { $log[] = "Variant $i: skip (no size/color)"; continue; }

            // Validate hex: must be exactly #rrggbb, otherwise store NULL
            if ($color_hex && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color_hex)) {
                $color_hex = null;
            }
            $color_hex = $color_hex ?: null;

            $sku = 'PROD' . $product_id . '-' . strtoupper($size) . '-' . $i;
            $sc2 = $pdo->prepare("SELECT id FROM product_variants WHERE sku=?");
            $sc2->execute([$sku]);
            if ($sc2->fetch()) $sku .= '-' . time();

            $vstmt->execute([$product_id, $size, $color_name, $color_hex, $price, $qty, $sku]);
            $log[] = "Variant: $size / $color_name / hex=" . ($color_hex ?? 'NULL') . " / qty=$qty";
        }
    }

    // ── Commit ────────────────────────────────────────────────
    $pdo->commit();
    $log[] = "=== COMMITTED ===";
    $log[] = $action === 'add' ? "SUCCESS_PRODUCT_ADDED" : "SUCCESS_PRODUCT_UPDATED";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $log[] = "=== EXCEPTION: " . $e->getMessage() . " ===";
    $log[] = "Line: " . $e->getLine();
}

echo implode("\n", $log);