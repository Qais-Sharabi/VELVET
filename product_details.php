<?php
include 'dbconnect.php';
include 'auth.php';
/** @var PDO $pdo */

$slug = $_GET['slug'] ?? '';
if (!$slug) { header("Location: shop.php"); exit; }

// 1. Fetch product
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.slug = ? AND p.is_active = 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch();
if (!$product) { header("Location: shop.php"); exit; }

// 2. Images
$img_stmt = $pdo->prepare("SELECT image_url, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
$img_stmt->execute([$product['id']]);
$images = $img_stmt->fetchAll();

// 3. Variants — all (including 0 stock, so we can show disabled options)
$var_stmt = $pdo->prepare("SELECT id, size, color, color_hex, price, stock_qty FROM product_variants WHERE product_id = ? ORDER BY size ASC");
$var_stmt->execute([$product['id']]);
$all_variants = $var_stmt->fetchAll();

// Group for UI
$has_variants  = !empty($all_variants);
$color_map     = []; // color => hex
$size_list     = [];
$variant_matrix= []; // [color][size] => variant row

foreach ($all_variants as $v) {
    $color_map[$v['color']] = $v['color_hex'] ?? '#cccccc';
    if (!in_array($v['size'], $size_list)) $size_list[] = $v['size'];
    $variant_matrix[$v['color']][$v['size']] = $v;
}

// 4. Related products
$related_stmt = $pdo->prepare("
    SELECT p.*, i.image_url FROM products p
    LEFT JOIN product_images i ON p.id = i.product_id AND i.is_primary = 1
    WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 LIMIT 4
");
$related_stmt->execute([$product['category_id'], $product['id']]);
$related = $related_stmt->fetchAll();

// 5. Cart count
$cart_count = 0;
if (isLoggedIn()) {
    $user_id   = currentUserId();
    $cnt       = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE user_id = ?");
    $cnt->execute([$user_id]);
    $cart_count = (int)$cnt->fetchColumn();

    // Wishlist
    $wl_stmt = $pdo->prepare("
        SELECT w.id as wish_id, p.id, p.name, p.base_price, p.sale_price, p.is_sale, p.slug,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary=1 LIMIT 1) as image
        FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ?
    ");
    $wl_stmt->execute([$user_id]);
    $wishlist_items = $wl_stmt->fetchAll();

    // Is this product already in wishlist?
    $inWish = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $inWish->execute([$user_id, $product['id']]);
    $alreadyInWishlist = (bool)$inWish->fetchColumn();
} else {
    $wishlist_items    = [];
    $alreadyInWishlist = false;
}

$isLoggedIn = isLoggedIn();
$isAdmin    = isAdmin();

// Price display
$display_price = $product['is_sale'] && $product['sale_price']
        ? (float)$product['sale_price']
        : (float)$product['base_price'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> — VELVET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="products_style.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="about.css">
    <link rel="stylesheet" href="search.css">
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="shopping%20bag.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        .swatch         { width:32px;height:32px;border-radius:50%;cursor:pointer;border:2px solid #ddd;transition:.2s; }
        .swatch.active  { border-color:#111;transform:scale(1.15);box-shadow:0 0 0 2px #fff,0 0 0 4px #111; }
        .swatch.out-of-stock { opacity:.35;cursor:not-allowed; }
        .size-box       { display:inline-block;min-width:46px;height:40px;line-height:38px;text-align:center;
            border:1px solid #ddd;cursor:pointer;font-size:.8rem;font-weight:600;margin:3px;transition:.2s;padding:0 8px; }
        .size-box.active{ background:#111;color:#fff;border-color:#111; }
        .size-box.out-of-stock { background:#f5f5f5;color:#bbb;cursor:not-allowed;text-decoration:line-through; }
        .add-bag-btn    { position:relative; }
        .add-bag-btn.loading::after { content:'';position:absolute;top:50%;left:50%;
            width:18px;height:18px;margin:-9px 0 0 -9px;
            border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin .6s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .variant-message { font-size:.83rem;color:#dc3545;margin-top:.5rem;min-height:1.2em; }
        .wishlist-btn-active { background:#111 !important; color:#fff !important; }
        .cart-toast { position:fixed;bottom:2rem;right:2rem;z-index:9999;min-width:260px; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container-fluid px-5">
        <a class="navbar-brand fw-bold" href="index.php">VELVET</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="shop.php">Shop</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal">About</a></li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-4">
            <button class="nav-icon-btn border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#searchModal">
                <i class="fas fa-search"></i>
            </button>
            <a href="#" class="text-dark" data-bs-toggle="modal" data-bs-target="#wishlistModal">
                <i class="fa-regular fa-heart fs-5"></i>
            </a>
            <div class="position-relative">
                <?php if ($isLoggedIn): ?>
                    <a href="shopping%20bag.php" class="text-decoration-none text-dark">
                        <i class="fas fa-shopping-bag"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="text-decoration-none text-dark"><i class="fas fa-shopping-bag"></i></a>
                <?php endif; ?>
                <span class="badge rounded-pill bg-dark position-absolute top-0 start-100 translate-middle" style="font-size:.6rem;"><?= $cart_count ?></span>
            </div>

            <?php if ($isAdmin): ?>
                <a href="Manager.php"  style="font-size:larger; color:#b30000;"><i class="fas fa-cog"></i></a>
            <?php endif; ?>

            <?php if ($isLoggedIn): ?>
                <a href="javascript:void(0)" onclick="toggleProfileModal()" class="text-dark">
                    <span class="profile-avatar-nav" style="font-size: x-large; font-weight: bold;"><?= htmlspecialchars($_SESSION['user_letter'] ?? 'U') ?></span>
                </a>
            <?php else: ?>
                <a href="javascript:void(0)" onclick="toggleProfileModal()" class="text-dark"><i class="fa-regular fa-user fs-5"></i></a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Toast -->
<div class="cart-toast" id="cartToast" style="display:none;">
    <div class="alert alert-dark alert-dismissible rounded-0 shadow" style="font-size:.88rem;">
        <i class="fas fa-check-circle me-2"></i> <span id="toastMsg">Added to bag!</span>
        <button type="button" class="btn-close btn-close-white" onclick="document.getElementById('cartToast').style.display='none'"></button>
    </div>
</div>

<div class="container mt-3">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb custom-breadcrumb">
            <li class="breadcrumb-item"><a href="shop.php" class="text-dark text-decoration-none">Shop</a></li>
            <li class="breadcrumb-item text-muted"><?= htmlspecialchars($product['category_name']) ?></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Images -->
        <div class="col-md-7">
            <div id="productCarousel" class="carousel slide">
                <div class="carousel-inner">
                    <?php if (empty($images)): ?>
                        <div class="carousel-item active">
                            <div class="detail-placeholder bg-light d-flex align-items-center justify-content-center" style="height:600px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($images as $i => $img): ?>
                            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                <div class="detail-placeholder">
                                    <img src="<?= htmlspecialchars($img['image_url']) ?>" class="img-fluid h-100 w-100 object-fit-cover">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (count($images) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                        <span class="carousel-arrow"><i class="fas fa-chevron-left"></i></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                        <span class="carousel-arrow"><i class="fas fa-chevron-right"></i></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Details -->
        <div class="col-md-5">
            <div class="sticky-top" style="top:100px;">
                <p class="text-muted small text-uppercase mb-1" style="letter-spacing:2px;"><?= htmlspecialchars($product['category_name']) ?></p>
                <h2 class="fw-bold"><?= htmlspecialchars($product['name']) ?></h2>

                <?php if ($product['is_sale'] && $product['sale_price']): ?>
                    <h3 class="my-3">
                        <span class="text-decoration-line-through text-muted fs-5">₪<?= number_format($product['base_price'], 2) ?></span>
                        <span class="text-danger ms-2">₪<?= number_format($product['sale_price'], 2) ?></span>
                        <span class="badge bg-danger ms-2" style="font-size:.65rem;vertical-align:middle;">SALE</span>
                    </h3>
                <?php else: ?>
                    <h3 class="my-3" id="currentPrice">₪<?= number_format($product['base_price'], 2) ?></h3>
                <?php endif; ?>

                <?php if (!$has_variants): ?>
                    <!-- No variants defined -->
                    <div class="alert alert-warning rounded-0 py-2 px-3 small mt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        This product currently has no sizes or colors available. You can still add it to your wishlist.
                    </div>
                <?php else: ?>

                    <!-- Color Selection -->
                    <?php if (!empty($color_map)): ?>
                        <div class="mt-4">
                            <label class="fw-bold small text-uppercase mb-2">Color: <span id="selectedColorLabel" class="text-muted fw-normal">—</span></label>
                            <div class="color-swatches d-flex gap-3 flex-wrap">
                                <?php foreach ($color_map as $cname => $chex):
                                    // Check if any variant with this color has stock
                                    $hasStock = false;
                                    foreach ($all_variants as $v) {
                                        if ($v['color'] === $cname && $v['stock_qty'] > 0) { $hasStock = true; break; }
                                    }
                                    ?>
                                    <div class="swatch <?= !$hasStock ? 'out-of-stock' : '' ?>"
                                         style="background-color:<?= htmlspecialchars($chex) ?>;"
                                         title="<?= htmlspecialchars($cname) ?>"
                                         data-color="<?= htmlspecialchars($cname) ?>"
                                         data-has-stock="<?= $hasStock ? '1' : '0' ?>"
                                         onclick="<?= $hasStock ? "selectColor(this)" : "showToast('This color is out of stock.')" ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Size Selection -->
                    <?php if (!empty($size_list)): ?>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between">
                                <label class="fw-bold small text-uppercase">Size: <span id="selectedSizeLabel" class="text-muted fw-normal">—</span></label>
                            </div>
                            <div class="size-options mt-2" id="sizeOptions">
                                <?php foreach ($size_list as $size): ?>
                                    <span class="size-box"
                                          data-size="<?= htmlspecialchars($size) ?>"
                                          onclick="selectSize(this)">
                            <?= htmlspecialchars($size) ?>
                        </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <a href="#" class="text-decoration-none text-dark small muted" data-bs-toggle="modal" data-bs-target="#sizeGuideModal">
                        Size Guide
                    </a>

                    <!-- Quantity -->
                    <div class="mt-4">
                        <label class="fw-bold small text-uppercase mb-2">Quantity</label>
                        <div class="quantity-selector d-flex align-items-center">
                            <button class="qty-btn" type="button" onclick="updateQty(-1)">−</button>
                            <input type="number" id="qtyInput" value="1" min="1" class="qty-input" readonly>
                            <button class="qty-btn" type="button" onclick="updateQty(1)">+</button>
                        </div>
                    </div>

                    <div class="variant-message" id="variantMsg"></div>
                <?php endif; ?>

                <!-- Description -->
                <div class="accordion accordion-flush mt-4 border-top" id="productSpecs">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button px-0 fw-bold small text-uppercase collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#spec1">
                                Description & Details
                            </button>
                        </h2>
                        <div id="spec1" class="accordion-collapse collapse show">
                            <div class="accordion-body px-0 text-muted small lh-lg">
                                <?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="d-grid gap-2 mt-4 pb-5">
                    <?php if (!$isLoggedIn): ?>
                        <a href="login.php?next=<?= urlencode('product_details.php?slug='.$slug) ?>" class="btn btn-dark rounded-0 py-3 fw-bold">
                            <i class="fas fa-lock me-2"></i> SIGN IN TO ADD TO BAG
                        </a>
                        <a href="login.php?next=<?= urlencode('product_details.php?slug='.$slug) ?>" class="btn btn-outline-dark rounded-0 py-2">
                            <i class="far fa-heart me-2"></i> SIGN IN TO WISHLIST
                        </a>
                    <?php elseif (!$has_variants): ?>
                        <!-- No variants — only wishlist allowed -->
                        <button type="button" class="btn btn-dark rounded-0 py-3 fw-bold" disabled title="No sizes/colors available">
                            ADD TO BAG — UNAVAILABLE
                        </button>
                        <form action="wishlist_process.php" method="POST">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <button type="submit" name="add_to_wishlist"
                                    class="btn rounded-0 py-2 w-100 <?= $alreadyInWishlist ? 'btn-dark wishlist-btn-active' : 'btn-outline-dark' ?>">
                                <i class="<?= $alreadyInWishlist ? 'fas' : 'far' ?> fa-heart me-2"></i>
                                <?= $alreadyInWishlist ? 'IN WISHLIST' : 'ADD TO WISHLIST' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Full add-to-bag form -->
                        <button type="button" id="addToBagBtn" class="btn btn-dark rounded-0 py-3 fw-bold add-bag-btn"
                                onclick="handleAddToBag()">
                            ADD TO BAG
                        </button>
                        <form action="wishlist_process.php" method="POST" style="display:contents;">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <button type="submit" name="add_to_wishlist"
                                    class="btn rounded-0 py-2 <?= $alreadyInWishlist ? 'btn-dark wishlist-btn-active' : 'btn-outline-dark' ?>">
                                <i class="<?= $alreadyInWishlist ? 'fas' : 'far' ?> fa-heart me-2"></i>
                                <?= $alreadyInWishlist ? 'IN WISHLIST' : 'ADD TO WISHLIST' ?>
                            </button>
                        </form>
                        <!-- Hidden form submitted by JS -->
                        <form action="cart_process.php" method="POST" id="addBagForm" style="display:none;">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="variant_id" id="formVariantId" value="">
                            <input type="hidden" name="qty" id="formQty" value="1">
                            <input type="submit" name="add_to_bag">
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related)): ?>
        <div class="related-products mt-3 pt-3 border-top mb-5">
            <h4 class="fw-bold mb-4 text-uppercase small" style="letter-spacing:2px;">You may also like</h4>
            <div class="row g-4">
                <?php foreach ($related as $rel): ?>
                    <div class="col-6 col-md-3">
                        <a href="product_details.php?slug=<?= urlencode($rel['slug']) ?>" class="text-decoration-none product-card-link">
                            <div class="product-card text-center">
                                <div class="related-image-holder mb-3 bg-light border overflow-hidden">
                                    <?php if ($rel['image_url']): ?>
                                        <img src="<?= htmlspecialchars($rel['image_url']) ?>" class="img-fluid h-100 w-100 object-fit-cover">
                                    <?php else: ?>
                                        <div class="d-flex h-100 align-items-center justify-content-center text-muted small">No Image</div>
                                    <?php endif; ?>
                                </div>
                                <h6 class="fw-bold mb-1 small text-dark"><?= htmlspecialchars($rel['name']) ?></h6>
                                <p class="text-muted small mb-0">
                                    <?php if ($rel['is_sale'] && $rel['sale_price']): ?>
                                        <span class="text-decoration-line-through me-1">₪<?= number_format($rel['base_price'],2) ?></span>
                                        <span class="text-danger">₪<?= number_format($rel['sale_price'],2) ?></span>
                                    <?php else: ?>
                                        ₪<?= number_format($rel['base_price'],2) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Wishlist Modal -->
<div class="modal fade" id="wishlistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="fw-bold mb-0 text-uppercase" style="letter-spacing:2px;">MY WISHLIST</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!$isLoggedIn): ?>
                    <div class="text-center py-5">
                        <i class="fa-regular fa-heart mb-3 text-muted" style="font-size:2rem;"></i>
                        <p class="text-muted mb-3">Sign in to view your wishlist.</p>
                        <a href="login.php" class="btn btn-dark rounded-0 px-4">Sign In</a>
                    </div>
                <?php elseif (empty($wishlist_items)): ?>
                    <div class="text-center py-4">
                        <i class="fa-regular fa-heart mb-3 text-muted" style="font-size:2rem;"></i>
                        <p class="text-muted">Your wishlist is empty.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div style="width:130px;height:150px;flex-shrink:0; border-radius:2px; overflow:hidden; margin-right: 15px; margin-left: 25px;">
                                <img src="<?= htmlspecialchars($item['image'] ?? '') ?>" class="w-100 h-100 object-fit-cover border">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1 fw-bold" style="font-size:.85rem;"><?= htmlspecialchars($item['name']) ?></h6>
                                <p class="mb-2 small fw-bold">₪<?= number_format($item['is_sale'] ? $item['sale_price'] : $item['base_price'], 2) ?></p>
                                <a href="product_details.php?slug=<?= urlencode($item['slug']) ?>" class="btn btn-sm rounded-2" id="view_btn">View Item</a>
                            </div>
                            <a href="wishlist_process.php?action=remove&id=<?= $item['wish_id'] ?>" class="btn btn-outline-danger btn-sm border-0 ms-2" title="Remove">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-dark btn-sm rounded-3 w-100" data-bs-dismiss="modal">CONTINUE SHOPPING</button>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'session_data.php'; ?>
<script src="modals.js"></script>
<?php include 'footer.php'; ?>
<script>
    if (typeof loadSearchModal  === 'function') loadSearchModal();
    if (typeof loadAboutModal   === 'function') loadAboutModal();
    if (typeof loadProfileModal === 'function') loadProfileModal();
</script>

<script>
    // ── Variant matrix from PHP ────────────────────────────────────
    const VARIANT_MATRIX = <?= json_encode($variant_matrix) ?>;
    const HAS_VARIANTS   = <?= $has_variants ? 'true' : 'false' ?>;

    let selectedColor = null;
    let selectedSize  = null;
    let selectedVariantId = null;

    function selectColor(el) {
        if (el.dataset.hasStock === '0') return;
        document.querySelectorAll('.swatch').forEach(s => s.classList.remove('active'));
        el.classList.add('active');
        selectedColor = el.dataset.color;
        document.getElementById('selectedColorLabel').textContent = selectedColor;

        // Update available sizes based on this color
        updateSizeAvailability();
        selectedSize = null;
        document.getElementById('selectedSizeLabel').textContent = '—';
        selectedVariantId = null;
        clearMsg();
    }

    function updateSizeAvailability() {
        if (!selectedColor || !VARIANT_MATRIX[selectedColor]) return;
        const colorVariants = VARIANT_MATRIX[selectedColor];
        document.querySelectorAll('.size-box').forEach(box => {
            const sz = box.dataset.size;
            box.classList.remove('active','out-of-stock');
            if (!colorVariants[sz] || colorVariants[sz].stock_qty < 1) {
                box.classList.add('out-of-stock');
            }
        });
    }

    function selectSize(el) {
        if (el.classList.contains('out-of-stock')) return;
        document.querySelectorAll('.size-box').forEach(s => s.classList.remove('active'));
        el.classList.add('active');
        selectedSize = el.dataset.size;
        document.getElementById('selectedSizeLabel').textContent = selectedSize;

        // Find variant
        if (selectedColor && selectedSize && VARIANT_MATRIX[selectedColor] && VARIANT_MATRIX[selectedColor][selectedSize]) {
            const v = VARIANT_MATRIX[selectedColor][selectedSize];
            selectedVariantId = v.id;
            // Update stock limit on qty
            const maxQty = v.stock_qty;
            const qtyEl  = document.getElementById('qtyInput');
            if (parseInt(qtyEl.value) > maxQty) qtyEl.value = maxQty;
            // Show stock warning if low
            if (maxQty <= 3) {
                showMsg('Only ' + maxQty + ' left in stock!', '#856404');
            } else {
                clearMsg();
            }
        } else {
            selectedVariantId = null;
        }
    }

    // If only 1 color, auto-select it
    window.addEventListener('DOMContentLoaded', function() {
        const swatches = document.querySelectorAll('.swatch:not(.out-of-stock)');
        if (swatches.length === 1) selectColor(swatches[0]);

        // If no colors (only sizes), auto-resolve variants differently
        // If color is 'Custom', hide color section and auto-select
        const customSwatch = document.querySelector('.swatch[data-color="Custom"]');
        if (customSwatch && swatches.length === 1) {
            selectColor(customSwatch);
            // Hide the color row
            const colorSection = customSwatch.closest('div').parentElement;
            if (colorSection) colorSection.style.display = 'none';
        }
    });

    function updateQty(change) {
        const input = document.getElementById('qtyInput');
        if (!input) return;
        let val = parseInt(input.value) + change;
        if (val < 1) val = 1;

        // Cap at stock
        if (selectedVariantId) {
            const color = selectedColor;
            const size  = selectedSize;
            if (VARIANT_MATRIX[color] && VARIANT_MATRIX[color][size]) {
                const stock = VARIANT_MATRIX[color][size].stock_qty;
                if (val > stock) val = stock;
            }
        }
        input.value = val;
        document.getElementById('formQty').value = val;
    }

    function handleAddToBag() {
        if (!HAS_VARIANTS) return;

        // Check color selected (if colors exist)
        const swatches = document.querySelectorAll('.swatch');
        const needColor = swatches.length > 0;
        if (needColor && !selectedColor) {
            showMsg('Please select a color.'); return;
        }

        // Check size selected (if sizes exist)
        const sizes = document.querySelectorAll('.size-box');
        const needSize = sizes.length > 0;
        if (needSize && !selectedSize) {
            showMsg('Please select a size.'); return;
        }

        if (!selectedVariantId) {
            showMsg('Selected combination is unavailable.'); return;
        }

        // Submit form
        document.getElementById('formVariantId').value = selectedVariantId;
        document.getElementById('formQty').value = document.getElementById('qtyInput').value;
        document.getElementById('addBagForm').submit();
    }

    function showMsg(msg, color='#dc3545') {
        const el = document.getElementById('variantMsg');
        if (el) { el.textContent = msg; el.style.color = color; }
    }
    function clearMsg() {
        const el = document.getElementById('variantMsg');
        if (el) el.textContent = '';
    }

    function showToast(msg) {
        document.getElementById('toastMsg').textContent = msg;
        const t = document.getElementById('cartToast');
        t.style.display = 'block';
        setTimeout(() => t.style.display = 'none', 3000);
    }

    // Show success toast if redirected back
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('cart_success')) {
        showToast('Added to bag successfully!');
        window.history.replaceState({}, '', window.location.pathname + '?slug=<?= urlencode($slug) ?>');
    }
    if (urlParams.get('cart_error')) {
        const errs = {
            'no_variants':'Please select size and color first.',
            'out_of_stock':'Sorry, this item is out of stock.',
            'invalid_product':'Invalid product.'
        };
        showToast(errs[urlParams.get('cart_error')] || 'Could not add to bag.');
    }
</script>
<div class="modal fade" id="sizeGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content size-guide-content rounded-4">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-bold text-uppercase tracking-widest">Size Guide</h4>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="nav nav-tabs size-tabs mb-4" id="sizeTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="women-tab" data-bs-toggle="tab" data-bs-target="#women-size" type="button" role="tab">Women</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="men-tab" data-bs-toggle="tab" data-bs-target="#men-size" type="button" role="tab">Men</button>
                    </li>
                </ul>

                <div class="tab-content" id="sizeTabContent">
                    <div class="tab-pane fade show active" id="women-size" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table size-table">
                                <thead>
                                <tr>
                                    <th>Size</th>
                                    <th>Bust (cm)</th>
                                    <th>Waist (cm)</th>
                                    <th>Hips (cm)</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr><td>XS</td><td>80-83</td><td>62-65</td><td>88-91</td></tr>
                                <tr><td>Small</td><td>84-88</td><td>66-70</td><td>92-96</td></tr>
                                <tr><td>Medium</td><td>89-93</td><td>71-75</td><td>97-101</td></tr>
                                <tr><td>Large</td><td>94-98</td><td>76-80</td><td>102-106</td></tr>
                                <tr><td>X-Large</td><td>99-103</td><td>81-85</td><td>107-111</td></tr>
                                <tr><td>XXL</td><td>104-108</td><td>86-90</td><td>112-116</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="men-size" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table size-table">
                                <thead>
                                <tr>
                                    <th>Size</th>
                                    <th>Chest (cm)</th>
                                    <th>Waist (cm)</th>
                                    <th>Shoulders (cm)</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr><td>Small</td><td>92-96</td><td>78-82</td><td>44-45</td></tr>
                                <tr><td>Medium</td><td>97-101</td><td>83-87</td><td>46-47</td></tr>
                                <tr><td>Large</td><td>102-106</td><td>88-92</td><td>48-49</td></tr>
                                <tr><td>X-Large</td><td>107-111</td><td>93-97</td><td>50-51</td></tr>
                                <tr><td>XXL</td><td>112-116</td><td>98-102</td><td>52-53</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mt-3"><i class="fas fa-info-circle me-1"></i> Measurements are in centimeters. If you are between sizes, we recommend sizing up for a more relaxed fit.</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
