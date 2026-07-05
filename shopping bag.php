<?php
include 'dbconnect.php';
include 'auth.php';
/** @var PDO $pdo */

requireLogin();

$user_id = currentUserId();
$isAdmin = isAdmin();
$isLoggedIn = isLoggedIn();

// Fetch cart items with product + variant details
$stmt = $pdo->prepare("
    SELECT ci.id as cart_item_id, ci.quantity,
           p.id as product_id, p.name, p.slug, p.base_price, p.sale_price, p.is_sale,
           pv.id as variant_id, pv.size, pv.color, pv.price as variant_price, pv.stock_qty,
           pi.image_url
    FROM cart_items ci
    JOIN products p  ON ci.product_id = p.id
    JOIN product_variants pv ON ci.variant_id = pv.id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    WHERE ci.user_id = ?
    ORDER BY ci.added_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Fetch store settings for shipping
$settings = $pdo->query("SELECT * FROM store_settings LIMIT 1")->fetch();
$shipping_fee       = (float)($settings['shipping_fee'] ?? 20);
$free_shipping_above= (float)($settings['free_shipping_above'] ?? 300);

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $price     = $item['is_sale'] && $item['sale_price'] ? $item['sale_price'] : $item['variant_price'];
    $subtotal += $price * $item['quantity'];
}
$shipping      = $subtotal >= $free_shipping_above ? 0 : $shipping_fee;
$total         = $subtotal + $shipping;
$cart_count    = array_sum(array_column($cart_items, 'quantity'));

// Wishlist items for modal
$wishlist_items = [];
$wish_stmt = $pdo->prepare("
    SELECT w.id as wish_id, p.id, p.name, p.base_price, p.sale_price, p.is_sale, p.slug,
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary=1 LIMIT 1) as image
    FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ?
");
$wish_stmt->execute([$user_id]);
$wishlist_items = $wish_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Bag | VELVET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="shopping%20bag.css">
    <link rel="stylesheet" href="about.css">
    <link rel="stylesheet" href="profile.css">
</head>
<body class="bg-white">

<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top py-3">
    <div class="container-fluid px-5">
        <a class="navbar-brand fw-bold" href="index.php">VELVET</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="shop.php">Shop</a></li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-4">
            <a href="#" data-bs-toggle="modal" data-bs-target="#wishlistModal" class="text-dark">
                <i class="fa-regular fa-heart fs-5"></i>
            </a>
            <div class="position-relative">
                <a href="shopping%20bag.php" class="text-decoration-none text-dark">
                    <i class="fas fa-shopping-bag"></i>
                </a>
                <span class="badge rounded-pill bg-dark position-absolute top-0 start-100 translate-middle" style="font-size:.6rem;"><?= $cart_count ?></span>
            </div>
            <?php if ($isAdmin): ?>
                <a href="Manager.php"  style="font-size:larger; color:#b30000;"><i class="fas fa-cog"></i></a>
            <?php endif; ?>
            <a href="javascript:void(0)" onclick="toggleProfileModal()" class="text-dark">
                <span class="profile-avatar-nav" style="font-size: x-large; font-weight: bold;"><?= htmlspecialchars($_SESSION['user_letter'] ?? 'U') ?></span>
            </a>
        </div>
    </div>
</nav>

<div class="container mt-5 pt-4">
    <div class="row g-5">
        <div class="col-lg-8">
            <h4 class="fw-bold mb-5 text-uppercase small tracking-widest">Shopping Bag
                <span class="text-muted fw-normal">(<?= $cart_count ?> item<?= $cart_count != 1 ? 's' : '' ?>)</span>
            </h4>

            <?php if (empty($cart_items)): ?>
            <div class="text-center py-5">
                <i class="fas fa-bag-shopping fa-3x text-muted mb-4" style="opacity:.3;"></i>
                <p class="text-muted mb-3">Your bag is empty.</p>
                <a href="shop.php" class="btn btn-dark rounded-0 px-4">Start Shopping</a>
            </div>
            <?php else: ?>

            <?php foreach ($cart_items as $item):
                $price = $item['is_sale'] && $item['sale_price'] ? (float)$item['sale_price'] : (float)$item['variant_price'];
                $line  = $price * $item['quantity'];
            ?>
            <div class="cart-item d-flex border-bottom pb-4 mb-4" id="item-<?= $item['cart_item_id'] ?>">
                <a href="product_details.php?slug=<?= urlencode($item['slug']) ?>">
                    <div class="product-img-bg rounded overflow-hidden" style="width:140px;height:180px;background:#f7f7f7;flex-shrink:0;">
                        <?php if ($item['image_url']): ?>
                        <img src="<?= htmlspecialchars($item['image_url']) ?>" class="w-100 h-100 object-fit-cover">
                        <?php else: ?>
                        <div class="d-flex h-100 align-items-center justify-content-center text-muted small">No Image</div>
                        <?php endif; ?>
                    </div>
                </a>

                <div class="ms-4 flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <a href="product_details.php?slug=<?= urlencode($item['slug']) ?>" class="text-decoration-none text-dark">
                            <h6 class="fw-bold mb-1 text-uppercase"><?= htmlspecialchars($item['name']) ?></h6>
                        </a>
                        <span class="fw-bold item-line-total">₪<?= number_format($line, 2) ?></span>
                    </div>
                    <?php if ($item['color'] && strtolower($item['color']) !== 'custom'): ?>
                    <p class="text-muted small mb-1">Color: <?= htmlspecialchars($item['color']) ?></p>
                    <?php endif; ?>
                    <p class="text-muted small mb-1">Size: <?= htmlspecialchars($item['size']) ?></p>
                    <?php if ($item['is_sale'] && $item['sale_price']): ?>
                    <p class="small mb-1">
                        <span class="text-decoration-line-through text-muted me-1">₪<?= number_format($item['base_price'], 2) ?></span>
                        <span class="text-danger fw-bold">₪<?= number_format($price, 2) ?></span>
                    </p>
                    <?php else: ?>
                    <p class="small mb-1">₪<?= number_format($price, 2) ?> each</p>
                    <?php endif; ?>

                    <div class="d-flex align-items-center justify-content-between mt-3">
                        <div class="qty-control d-flex align-items-center border">
                            <a href="cart_process.php?action=decrease&id=<?= $item['cart_item_id'] ?>"
                               class="btn btn-sm px-3 border-0 text-dark text-decoration-none">−</a>
                            <span class="border-0 text-center px-2" style="min-width:40px;"><?= $item['quantity'] ?></span>
                            <a href="cart_process.php?action=increase&id=<?= $item['cart_item_id'] ?>"
                               class="btn btn-sm px-3 border-0 text-dark text-decoration-none">+</a>
                        </div>
                        <a href="cart_process.php?action=remove&id=<?= $item['cart_item_id'] ?>"
                           class="btn btn-remove shadow-none text-decoration-none">
                            <i class="far fa-trash-alt me-2"></i> Remove
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <a href="shop.php" class="text-dark small text-decoration-none border-bottom border-dark pb-1">
                <i class="fas fa-arrow-left me-2"></i> Continue Shopping
            </a>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="summary-box p-4 bg-light border sticky-top" style="top:100px;">
                <h6 class="fw-bold mb-4 text-uppercase small tracking-widest">Order Summary</h6>

                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted small">Subtotal</span>
                    <span class="small">₪<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted small">Shipping</span>
                    <?php if ($shipping == 0): ?>
                    <span class="text-success small">Free</span>
                    <?php else: ?>
                    <span class="small">₪<?= number_format($shipping, 2) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($subtotal > 0 && $shipping > 0): ?>
                <div class="small text-muted mb-3">
                    <i class="fas fa-truck-fast me-1"></i>
                    Add ₪<?= number_format($free_shipping_above - $subtotal, 2) ?> more for free shipping.
                </div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                    <span class="fw-bold">Total</span>
                    <span class="fw-bold fs-5">₪<?= number_format($total, 2) ?></span>
                </div>

                <?php if (!empty($cart_items)): ?>
                <a href="Checkout.php">
                    <button class="btn btn-dark w-100 rounded-0 py-3 fw-bold mb-4">CHECKOUT NOW</button>
                </a>
                <?php else: ?>
                <button class="btn btn-secondary w-100 rounded-0 py-3 fw-bold mb-4" disabled>CHECKOUT NOW</button>
                <?php endif; ?>

                <div class="text-center mt-2">
                    <p class="text-muted small mb-1">Thank you for choosing us,</p>
                    <span class="signature-font">VELVET Team</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Wishlist Modal -->
<div class="modal fade" id="wishlistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="fw-bold mb-0 text-uppercase" style="letter-spacing:2px;">MY WISHLIST</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height:400px;overflow-y:auto;">
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
                            <div style="width:65px;height:85px;flex-shrink:0;">
                                <img src="<?= htmlspecialchars($item['image'] ?? '') ?>" class="w-100 h-100 object-fit-cover border">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1 fw-bold" style="font-size:.85rem;"><?= htmlspecialchars($item['name']) ?></h6>
                                <p class="mb-2 small fw-bold">₪<?= number_format($item['is_sale'] ? $item['sale_price'] : $item['base_price'], 2) ?></p>
                                <a href="product_details.php?slug=<?= urlencode($item['slug']) ?>" class="btn btn-dark btn-sm rounded-0">View Item</a>
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


<?php include 'session_data.php'; ?>
<script src="modals.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    if (typeof loadProfileModal === 'function') loadProfileModal();
    if (typeof loadAboutModal === 'function') loadAboutModal();
</script>
</body>
</html>
