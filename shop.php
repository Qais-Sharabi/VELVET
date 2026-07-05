<?php
include 'dbconnect.php';
include 'auth.php';
/** @var PDO $pdo */

$isLoggedIn    = isLoggedIn();
$userId        = currentUserId();
$isAdmin       = isAdmin();
$userLetter    = $isLoggedIn ? htmlspecialchars($_SESSION['user_letter'] ?? 'U') : '';

$category_slug = $_GET['category'] ?? 'all';
$sort          = $_GET['sort']     ?? 'newest';
$search        = trim($_GET['q']   ?? '');

// Build query
$sql    = "SELECT p.*, i.image_url FROM products p LEFT JOIN product_images i ON p.id = i.product_id AND i.is_primary = 1 WHERE p.is_active = 1";
$params = [];

if ($search) {
    $sql .= " AND (p.name LIKE :search OR p.description LIKE :search2)";
    $params['search']  = '%' . $search . '%';
    $params['search2'] = '%' . $search . '%';
}
if ($category_slug !== 'all') {
    $sql .= " AND (p.category_id IN (SELECT id FROM categories WHERE slug = :slug1 UNION SELECT id FROM categories WHERE parent_id = (SELECT id FROM categories WHERE slug = :slug2)))";
    $params['slug1'] = $category_slug;
    $params['slug2'] = $category_slug;
}

$sql .= match($sort) {
    'price_low'  => " ORDER BY p.base_price ASC",
    'price_high' => " ORDER BY p.base_price DESC",
    'oldest'     => " ORDER BY p.created_at ASC",
    default      => " ORDER BY p.created_at DESC",
};

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Cart count
$cart_count = 0;
if ($isLoggedIn) {
    $cc = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id = ?");
    $cc->execute([$userId]);
    $cart_count = (int)$cc->fetchColumn();
}

// Wishlist
$wishlist_items = [];
if ($isLoggedIn) {
    $ws = $pdo->prepare("
        SELECT w.id as wish_id, p.id, p.name, p.base_price, p.sale_price, p.is_sale, p.slug,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary=1 LIMIT 1) as image
        FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ?
    ");
    $ws->execute([$userId]);
    $wishlist_items = $ws->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Collection | VELVET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="products_style.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="search.css">
    <link rel="stylesheet" href="about.css">
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="shopping%20bag.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

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
            <!-- Wishlist -->
            <?php if ($isLoggedIn): ?>
            <a href="#" class="text-dark" data-bs-toggle="modal" data-bs-target="#wishlistModal">
                <i class="fa-regular fa-heart fs-5"></i>
            </a>
            <?php else: ?>
            <a href="login.php" class="text-dark"><i class="fa-regular fa-heart fs-5"></i></a>
            <?php endif; ?>
            <!-- Cart -->
            <div class="position-relative">
                <?php if ($isLoggedIn): ?>
                <a href="shopping%20bag.php" class="text-decoration-none text-dark"><i class="fas fa-shopping-bag"></i></a>
                <?php else: ?>
                <a href="login.php" class="text-decoration-none text-dark"><i class="fas fa-shopping-bag"></i></a>
                <?php endif; ?>
                <span class="badge rounded-pill bg-dark position-absolute top-0 start-100 translate-middle" style="font-size:.6rem;"><?= $cart_count ?></span>
            </div>
            <?php if ($isAdmin): ?>
                <a href="Manager.php"  style="font-size:larger; color:#b30000;"><i class="fas fa-cog"></i></a>
            <?php endif; ?>
            <!-- Profile -->
            <a href="javascript:void(0)" onclick="toggleProfileModal()" class="text-dark">
                <?php if ($isLoggedIn): ?>
                <span class="profile-avatar-nav" style="font-size: x-large; font-weight: bold;"><?= $userLetter ?></span>
                <?php else: ?>
                <i class="fa-regular fa-user fs-5"></i>
                <?php endif; ?>
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4 px-5">
    <div class="row">
        <aside class="col-md-2">
            <div class="fixed-sidebar">
                <?php if ($search): ?>
                <div class="mb-3 p-2 bg-light border" style="font-size:.85rem;">
                    Search: "<strong><?= htmlspecialchars($search) ?></strong>"
                    <a href="shop.php" class="ms-2 text-danger text-decoration-none" title="Clear"><i class="fas fa-times"></i></a>
                </div>
                <?php endif; ?>
                <h6 class="fw-bold text-uppercase mb-3" style="font-size:.8rem;letter-spacing:1px;">Categories</h6>
                <ul class="list-unstyled">
                    <li><a href="shop.php" class="filter-link <?= $category_slug === 'all' ? 'active' : '' ?>">All Collection</a></li>
                    <li><a href="shop.php?category=men" class="filter-link <?= $category_slug === 'men' ? 'active' : '' ?>">Men</a></li>
                    <li><a href="shop.php?category=women" class="filter-link <?= $category_slug === 'women' ? 'active' : '' ?>">Women</a></li>
                </ul>
                <hr>
                <h6 class="fw-bold text-uppercase mb-3" style="font-size:.8rem;letter-spacing:1px;">Shop by Style</h6>
                <ul class="list-unstyled mb-4" style="font-size:.9rem;">
                    <li><a href="shop.php?category=women-one-piece" class="filter-link <?= $category_slug === 'women-one-piece' ? 'active' : '' ?>">Women Dresses</a></li>
                    <li><a href="shop.php?category=women-top" class="filter-link <?= $category_slug === 'women-top' ? 'active' : '' ?>">Women Tops</a></li>
                    <li><a href="shop.php?category=women-bottom" class="filter-link <?= $category_slug === 'women-bottom' ? 'active' : '' ?>">Women Skirts & Pants</a></li>
                    <li><a href="shop.php?category=men-top" class="filter-link <?= $category_slug === 'men-top' ? 'active' : '' ?>">Men Shirts</a></li>
                    <li><a href="shop.php?category=men-bottom" class="filter-link <?= $category_slug === 'men-bottom' ? 'active' : '' ?>">Men Pants</a></li>
                </ul>
                <hr>
                <div class="sort-container">
                    <h6 class="fw-bold text-uppercase mb-1" style="font-size:.8rem;letter-spacing:1px;">Sort By</h6>
                    <select class="custom-sort-select" onchange="location=this.value;">
                        <option value="shop.php?category=<?= $category_slug ?>&sort=newest" <?= $sort==='newest' ? 'selected':'' ?>>Newest</option>
                        <option value="shop.php?category=<?= $category_slug ?>&sort=price_low" <?= $sort==='price_low' ? 'selected':'' ?>>Price: Low to High</option>
                        <option value="shop.php?category=<?= $category_slug ?>&sort=price_high" <?= $sort==='price_high' ? 'selected':'' ?>>Price: High to Low</option>
                        <option value="shop.php?category=<?= $category_slug ?>&sort=oldest" <?= $sort==='oldest' ? 'selected':'' ?>>Oldest</option>
                    </select>
                </div>
            </div>
        </aside>

        <main class="col-md-10">
            <div class="row g-4">
                <?php if (empty($products)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-4" style="opacity:.3;"></i>
                    <p class="text-muted">No products found<?= $search ? ' for "'.htmlspecialchars($search).'"' : '' ?>.</p>
                    <a href="shop.php" class="btn btn-outline-dark rounded-0 mt-2">View All Products</a>
                </div>
                <?php else: ?>
                <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="product-wrapper">
                        <a href="product_details.php?slug=<?= urlencode($product['slug']) ?>" class="product-link text-decoration-none">
                            <div class="image-placeholder border-0">
                                <?php if ($product['is_sale']): ?>
                                <span class="sale-badge">Sale</span>
                                <?php endif; ?>
                                <img src="<?= htmlspecialchars($product['image_url'] ?? 'images/general/placeholder.jpg') ?>"
                                     class="img-fluid h-100 w-100 object-fit-cover"
                                     alt="<?= htmlspecialchars($product['name']) ?>">
                                <div class="hover-overlay">View Details</div>
                            </div>
                        </a>
                        <div class="text-center mt-3">
                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($product['name']) ?></h6>
                            <p class="text-muted">
                                <?php if ($product['is_sale'] && $product['sale_price']): ?>
                                <span class="text-decoration-line-through me-2 small">₪<?= number_format($product['base_price'],2) ?></span>
                                <span class="text-danger fw-bold">₪<?= number_format($product['sale_price'],2) ?></span>
                                <?php else: ?>
                                ₪<?= number_format($product['base_price'],2) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>


<?php include 'session_data.php'; ?>
<script src="modals.js"></script>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    if (typeof loadSearchModal  === 'function') loadSearchModal();
    if (typeof loadAboutModal   === 'function') loadAboutModal();
    if (typeof loadProfileModal === 'function') loadProfileModal();
</script>

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


</body>
</html>
