<?php
require_once 'dbconnect.php';
require_once 'auth.php';
/** @var PDO $pdo */

// Maintenance mode check
$isAdmin = isAdmin();
if (!$isAdmin) {
    try {
        $maint = $pdo->query("SELECT maintenance_mode FROM store_settings WHERE id = 1")->fetchColumn();
        if ($maint == 1) { header('Location: maintenance.php'); exit; }
    } catch (Exception $e) {}
}

// Session data
$isLoggedIn = isLoggedIn();
$userId     = currentUserId();
$userName   = $isLoggedIn ? htmlspecialchars($_SESSION['user_name']   ?? '') : '';
$userLetter = $isLoggedIn ? htmlspecialchars($_SESSION['user_letter'] ?? '') : '';

// Categories for navbar
$categories = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL")->fetchAll();

// New Arrivals
$newArrivals = $pdo->query("
    SELECT p.id, p.name, p.slug, p.base_price, p.sale_price, p.is_sale, pi.image_url
    FROM products p
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    WHERE p.is_new = 1 AND p.is_active = 1
    ORDER BY p.created_at DESC LIMIT 3
")->fetchAll();

// Best Sellers
$bestSellers = $pdo->query("
    SELECT p.id, p.name, p.slug, p.base_price, p.sale_price, p.is_sale, pi.image_url
    FROM products p
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    WHERE p.is_bestseller = 1 AND p.is_active = 1
    ORDER BY p.created_at DESC LIMIT 3
")->fetchAll();

// Store settings
$settings = $pdo->query("SELECT * FROM store_settings LIMIT 1")->fetch() ?: [
    'store_name' => 'VELVET', 'support_phone' => '', 'support_email' => '',
    'facebook_url' => '#', 'instagram_url' => '#'
];

// Cart count
$cart_count = 0;
if ($isLoggedIn) {
    $cc = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id = ?");
    $cc->execute([$userId]);
    $cart_count = (int)$cc->fetchColumn();
}

// Wishlist items
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

function displayPrice(array $p): string {
    if ($p['is_sale'] && $p['sale_price']) {
        return '<span class="text-danger fw-bold">₪'.number_format($p['sale_price'],2).'</span>
                <span class="text-muted text-decoration-line-through ms-1 small">₪'.number_format($p['base_price'],2).'</span>';
    }
    return '<span class="fw-bold">₪'.number_format($p['base_price'],2).'</span>';
}

// Welcome message after signup
$welcome = isset($_GET['welcome']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['store_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="about.css">
    <link rel="stylesheet" href="search.css">
    <link rel="stylesheet" href="contact%20us.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="shopping%20bag.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="profile.css">
    <script src="general.js" type="text/javascript"></script>
</head>
<body>

<?php if ($welcome): ?>
<div class="alert alert-success alert-dismissible fade show rounded-0 mb-0 text-center" role="alert" style="font-size:.9rem;">
    <i class="fas fa-check-circle me-2"></i> Welcome to VELVET, <?= htmlspecialchars($userName) ?>! Your account was created successfully.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3" href="index.php"><?= htmlspecialchars($settings['store_name']) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <?php foreach($categories as $cat): ?>
                <li class="nav-item">
                    <a class="nav-link" href="shop.php?category=<?= $cat['slug'] ?>"><?= htmlspecialchars($cat['name']) ?></a>
                </li>
                <?php endforeach; ?>
                <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal">About</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Contact Us</a></li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <!-- Wishlist -->
                <?php if ($isLoggedIn): ?>
                <a href="#" class="text-dark" data-bs-toggle="modal" data-bs-target="#wishlistModal">
                    <i class="fa-regular fa-heart fs-5"></i>
                </a>
                <?php else: ?>
                <a href="login.php" class="text-dark"><i class="fa-regular fa-heart fs-5"></i></a>
                <?php endif; ?>

                <!-- Cart -->
                <?php if ($isLoggedIn): ?>
                <a href="shopping%20bag.php" class="text-dark position-relative">
                    <i class="fa-solid fa-shopping-bag fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem;">
                        <?= $cart_count ?>
                    </span>
                </a>
                <?php else: ?>
                <a href="login.php" class="text-dark position-relative">
                    <i class="fa-solid fa-shopping-bag fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem;">0</span>
                </a>
                <?php endif; ?>

                <!-- Profile -->
                <a href="javascript:void(0)" onclick="toggleProfileModal()" class="text-dark">
                    <?php if ($isLoggedIn): ?>
                    <span class="profile-avatar-nav" style="font-size: x-large; font-weight: bold;" ><?= $userLetter ?></span>
                    <?php else: ?>
                    <i class="fa-regular fa-user fs-5"></i>
                    <?php endif; ?>
                </a>

            </div>
        </div>
    </div>
</nav>

<header class="hero-section position-relative overflow-hidden">
    <div id="heroCarousel" class="carousel slide carousel-fade position-absolute top-0 start-0 w-100 h-100" data-bs-ride="carousel">
        <div class="carousel-inner h-100">
            <div class="carousel-item active h-100 slide-1"></div>
            <div class="carousel-item h-100 slide-2"></div>
            <div class="carousel-item h-100 slide-3"></div>
            <div class="carousel-item h-100 slide-4"></div>
            <div class="carousel-item h-100 slide-5"></div>
            <div class="carousel-item h-100 slide-6"></div>
            <div class="carousel-item h-100 slide-7"></div>
            <div class="carousel-item h-100 slide-8"></div>
        </div>
    </div>
    <div class="container position-relative text-white h-100 d-flex align-items-center" style="z-index:2;">
        <div class="col-md-6">
            <h1 class="display-3 fw-bold">Elevate Your Style</h1>
            <p class="lead">Discover the latest trends in Men's and Women's fashion. Quality meets comfort.</p>
            <a onclick="gotoshoppage()" class="btn btn-light btn-lg px-5 mt-3">Shop Now</a>
        </div>
    </div>
</header>

<section class="mt-5 pt-5 bg-white">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-md-3"><div class="advantage-box">
                <i class="fa-solid fa-truck-fast mb-3"></i><h6>Fast Shipping</h6>
                <p class="text-muted small">Delivery within 2-3 business days</p>
            </div></div>
            <div class="col-md-3"><div class="advantage-box">
                <i class="fa-solid fa-arrow-rotate-left mb-3"></i><h6>Easy Returns</h6>
                <p class="text-muted small">30-day hassle-free return policy</p>
            </div></div>
            <div class="col-md-3"><div class="advantage-box">
                <i class="fa-solid fa-lock mb-3"></i><h6>Secure Payment</h6>
                <p class="text-muted small">100% protected checkout process</p>
            </div></div>
            <div class="col-md-3"><div class="advantage-box">
                <i class="fa-solid fa-headset mb-3"></i><h6>24/7 Support</h6>
                <p class="text-muted small">Dedicated team to help you anytime</p>
            </div></div>
        </div>
    </div>
</section>

<section class="container my-5 py-5">
    <div class="row g-4">
        <div class="col-md-6" id="men-section">
            <div class="category-card position-relative overflow-hidden rounded shadow">
                <img src="images/general/men.jpg" alt="Men's Collection" class="img-fluid w-100 h-100">
                <div class="category-overlay d-flex flex-column align-items-center justify-content-center">
                    <h2 class="text-white fw-bold">MEN</h2>
                    <a href="shop.php?category=men" class="btn btn-outline-light px-4 mt-2">Shop Now</a>
                </div>
            </div>
        </div>
        <div class="col-md-6" id="women-section">
            <div class="category-card position-relative overflow-hidden rounded shadow">
                <img src="images/general/women6.jpg" alt="Women's Collection" class="img-fluid w-100 h-100">
                <div class="category-overlay d-flex flex-column align-items-center justify-content-center">
                    <h2 class="text-white fw-bold">WOMEN</h2>
                    <a href="shop.php?category=women" class="btn btn-outline-light px-4 mt-2">Shop Now</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- NEW ARRIVALS -->
<section class="container my-5 py-5">
    <h2 class="text-center mb-4 fw-bold">New Arrivals</h2>
    <?php if (empty($newArrivals)): ?>
    <p class="text-center text-muted">No new arrivals at the moment. Check back soon!</p>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($newArrivals as $product): ?>
        <div class="col-md-4">
            <div class="product-card border-0 shadow-sm text-center p-3">
                <a href="product_details.php?slug=<?= urlencode($product['slug']) ?>">
                    <?php if ($product['image_url']): ?>
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>"
                         class="img-fluid mb-3" style="height:350px;width:100%;object-fit:cover;">
                    <?php else: ?>
                    <div class="bg-light mb-3 d-flex align-items-center justify-content-center" style="height:350px;">
                        <span class="text-muted small">No Image</span>
                    </div>
                    <?php endif; ?>
                </a>
                <h6 class="fw-bold text-uppercase small mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                <p class="mb-2"><?= displayPrice($product) ?></p>
                <div class="d-flex gap-2 justify-content-center mt-2">
                    <a href="product_details.php?slug=<?= urlencode($product['slug']) ?>" class="btn btn-outline-dark btn-sm">View Item</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- BEST SELLERS -->
<section class="container my-5 py-5 rounded">
    <h2 class="text-center mb-4 fw-bold">Best Sellers</h2>
    <?php if (empty($bestSellers)): ?>
    <p class="text-center text-muted">No best sellers listed yet.</p>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($bestSellers as $product): ?>
        <div class="col-md-4">
            <div class="product-card border-0 shadow-sm text-center p-3">
                <a href="product_details.php?slug=<?= urlencode($product['slug']) ?>">
                    <?php if ($product['image_url']): ?>
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>"
                         class="img-fluid mb-3" style="height:350px;width:100%;object-fit:cover;">
                    <?php else: ?>
                    <div class="bg-light mb-3 d-flex align-items-center justify-content-center" style="height:350px;">
                        <span class="text-muted small">No Image</span>
                    </div>
                    <?php endif; ?>
                </a>
                <h6 class="fw-bold text-uppercase small mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                <p class="mb-2"><?= displayPrice($product) ?></p>
                <div class="d-flex gap-2 justify-content-center mt-2">
                    <a href="product_details.php?slug=<?= urlencode($product['slug']) ?>" class="btn btn-outline-dark btn-sm">View Item</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<?php include 'footer.php'; ?>

<?php include 'session_data.php'; ?>
<script src="modals.js"></script>
<script src="feature.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    if (typeof loadAboutModal   === 'function') loadAboutModal();
    if (typeof loadContactModal === 'function') loadContactModal();
    if (typeof loadProfileModal === 'function') loadProfileModal();
</script>

<!-- Floating Outfit Builder -->
<div id="floating-stylist-btn" class="stylist-fab" onclick="toggleStylistModal()">
    <i class="fas fa-wand-magic-sparkles"></i>
</div>
<div id="stylist-modal" class="modal-overlay">
    <div class="modal-card split-modal">
        <button class="close-btn" onclick="toggleStylistModal()">&times;</button>
        <div class="modal-left">
            <div class="modal-header">
                <h2 class="builder-title">Outfit Builder </h2>
                <p class="builder-description">Select your colors and we'll recommend perfect outfits for you!</p>
            </div>
            <div class="selection-step">
                <p class="step-title">Who are we styling?</p>
                <div class="gender-options">
                    <input type="radio" name="gender" value="women" id="fem" checked><label for="fem">Woman</label>
                    <input type="radio" name="gender" value="men" id="mal"><label for="mal">Man</label>
                </div>
            </div>
            <div class="selection-step">
                <p class="step-title">Select up to 3 colors</p>
                <div class="color-grid" id="stylist-color-grid"></div>
            </div>
            <div class="selection-preview"><div id="selected-colors-row" class="selected-row"></div></div>
            <button id="build-outfit-btn" class="main-action-btn" disabled onclick="buildOutfits()">Build My Outfits</button>
        </div>
        <div class="modal-right" id="results-panel">
            <h5 class="results-title">Suggested Outfits</h5>
            <div class="results-scroll-area" id="outfit-display-container"></div>
            <button id="load-more-btn" class="secondary-action-btn" onclick="addNewOutfit()">
                <i class="fas fa-plus"></i> Generate More
            </button>
        </div>
    </div>
</div>

<!-- FAQ Chat -->
<div id="faq-fab" class="faq-fab" onclick="toggleFAQModal()"><i class="fas fa-comment-dots"></i></div>
<div id="faq-modal" class="chat-overlay" style="display:none;">
    <div class="chat-card">
        <div class="chat-header">
            <div class="d-flex align-items-center gap-2">
                <div class="bot-avatar"><i class="fas fa-sparkles"></i></div>
                <div>
                    <h6 class="mb-0 fw-bold" style="font-size:14px;">STORE Assistant</h6>
                    <small class="text-success" style="font-size:10px;">● Online</small>
                </div>
            </div>
            <button class="close-chat" onclick="toggleFAQModal()">&times;</button>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="message bot-msg">Hello! I'm your assistant. How can I help you today?</div>
            <div class="options-container" id="chatOptions">
                <button class="option-chip" onclick="handleChatSelection('Shipping','How long does shipping take?')">Shipping & Delivery</button>
                <button class="option-chip" onclick="handleChatSelection('Payment','What payment methods do you accept?')">Payment</button>
                <button class="option-chip" onclick="handleChatSelection('Size','How do I find my size?')">Size Guide</button>
                <button class="option-chip" onclick="handleChatSelection('Return','What is your return policy?')">Returns</button>
                <button class="option-chip" onclick="handleChatSelection('Contact','How can I contact you?')">Contact Us</button>
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
