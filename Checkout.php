<?php
include 'dbconnect.php';
include 'auth.php';
/** @var PDO $pdo */

requireLogin();

$user_id = currentUserId();

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

if (empty($cart_items)) {
    header('Location: shopping%20bag.php');
    exit;
}

$settings = $pdo->query("SELECT * FROM store_settings LIMIT 1")->fetch();
$shipping_fee        = (float)($settings['shipping_fee']        ?? 20);
$free_shipping_above = (float)($settings['free_shipping_above'] ?? 300);

$subtotal = 0;
foreach ($cart_items as $item) {
    $price = $item['is_sale'] && $item['sale_price'] ? (float)$item['sale_price'] : (float)$item['variant_price'];
    $subtotal += $price * $item['quantity'];
}
$shipping = ($subtotal >= $free_shipping_above) ? 0 : $shipping_fee;

$coupon        = null;
$discount      = 0;
$coupon_error  = '';
$coupon_code   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $cs = $pdo->prepare("
        SELECT * FROM coupons 
        WHERE code = ? AND is_active = 1 
          AND (expires_at IS NULL OR expires_at >= CURDATE())
          AND (uses_limit IS NULL OR uses_count < uses_limit)
    ");
    $cs->execute([$coupon_code]);
    $coupon = $cs->fetch();

    if (!$coupon) {
        $coupon_error = 'Invalid or expired coupon code.';
        $coupon = null;
    } elseif ($subtotal < $coupon['min_order_amount']) {
        $coupon_error = 'Minimum order of ₪' . number_format($coupon['min_order_amount'], 2) . ' required for this coupon.';
        $coupon = null;
    } else {
        if ($coupon['discount_type'] === 'percentage') {
            $discount = round($subtotal * ($coupon['discount_value'] / 100), 2);
        } else {
            $discount = min((float)$coupon['discount_value'], $subtotal);
        }
    }
}

$addr_stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC");
$addr_stmt->execute([$user_id]);
$saved_addresses = $addr_stmt->fetchAll();

$order_placed = false;
$new_order_id = 0;
$place_error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['place_order']) || ($_POST['place_order'] ?? '') === '1')) {
    $payment_method = in_array($_POST['payment_method'] ?? '', ['COD','VISA']) ? $_POST['payment_method'] : 'COD';
    $notes          = trim($_POST['notes'] ?? '');
    $coupon_id      = null;

    // Handle coupon
    $coupon_code_submit = strtoupper(trim($_POST['coupon_code_hidden'] ?? ''));
    $discount_amount    = 0;
    if ($coupon_code_submit) {
        $cs = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) AND (uses_limit IS NULL OR uses_count < uses_limit)");
        $cs->execute([$coupon_code_submit]);
        $coupon = $cs->fetch();
        if ($coupon && $subtotal >= $coupon['min_order_amount']) {
            $coupon_id = $coupon['id'];
            if ($coupon['discount_type'] === 'percentage') {
                $discount_amount = round($subtotal * ($coupon['discount_value'] / 100), 2);
            } else {
                $discount_amount = min((float)$coupon['discount_value'], $subtotal);
            }
        }
    }


    $address_id = null;
    if (!empty($_POST['use_saved_address']) && !empty($_POST['saved_address_id'])) {
        $aid = (int)$_POST['saved_address_id'];
        $av  = $pdo->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
        $av->execute([$aid, $user_id]);
        if ($av->fetch()) $address_id = $aid;
    }


    if (!$address_id) {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $city      = trim($_POST['city'] ?? '');
        $street    = trim($_POST['street'] ?? '');
        $save_addr = isset($_POST['save_address']);

        if (!$full_name || !$phone || !$city || !$street) {
            $place_error = 'Please fill in all shipping address fields.';
        } else {
            if ($save_addr) {
                $ins = $pdo->prepare("INSERT INTO addresses (user_id, full_name, phone, city, street, is_default) VALUES (?,?,?,?,?,0)");
                $ins->execute([$user_id, $full_name, $phone, $city, $street]);
                $address_id = (int)$pdo->lastInsertId();
            } else {
                // Save temp address
                $ins = $pdo->prepare("INSERT INTO addresses (user_id, full_name, phone, city, street, is_default) VALUES (?,?,?,?,?,0)");
                $ins->execute([$user_id, $full_name, $phone, $city, $street]);
                $address_id = (int)$pdo->lastInsertId();
            }
        }
    }

    if (!$place_error) {
        $total_amount = $subtotal - $discount_amount + $shipping;

        $pdo->beginTransaction();
        try {
            // Insert order
            $oi = $pdo->prepare("
                INSERT INTO orders (user_id, address_id, coupon_id, status, payment_method, payment_status, subtotal, discount_amount, shipping_fee, total_amount, notes)
                VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?)
            ");
            $pay_status = ($payment_method === 'VISA') ? 'paid' : 'pending';
            $oi->execute([$user_id, $address_id, $coupon_id, $payment_method, $pay_status,
                    $subtotal, $discount_amount, $shipping, $total_amount, $notes]);
            $new_order_id = (int)$pdo->lastInsertId();


            foreach ($cart_items as $item) {
                $unit_price = $item['is_sale'] && $item['sale_price'] ? (float)$item['sale_price'] : (float)$item['variant_price'];
                $color_val  = (strtolower($item['color'] ?? '') === 'custom') ? null : $item['color'];

                $ii = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, variant_id, product_name, size, color, quantity, unit_price)
                    VALUES (?,?,?,?,?,?,?,?)
                ");
                $ii->execute([$new_order_id, $item['product_id'], $item['variant_id'],
                        $item['name'], $item['size'], $color_val, $item['quantity'], $unit_price]);

                // Decrement stock
                $ds = $pdo->prepare("UPDATE product_variants SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id = ?");
                $ds->execute([$item['quantity'], $item['variant_id']]);
            }


            $ot = $pdo->prepare("INSERT INTO order_tracking (order_id, event_label, description) VALUES (?,?,?)");
            $ot->execute([$new_order_id, 'Order Placed', 'Your order has been placed and is awaiting confirmation.']);


            if ($coupon_id) {
                $pdo->prepare("UPDATE coupons SET uses_count = uses_count + 1 WHERE id = ?")->execute([$coupon_id]);
            }


            $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$user_id]);

            $pdo->commit();
            $order_placed = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            $place_error = 'An error occurred placing your order. Please try again.';
        }
    }
}

$total = $subtotal - $discount + $shipping;
$cart_count = array_sum(array_column($cart_items, 'quantity'));
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VELVET — Checkout</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Checkout.css">
    <style>
        .addr-card { border:2px solid #eee; border-radius:8px; padding:14px 16px; cursor:pointer; margin-bottom:10px; transition:.2s; }
        .addr-card.selected { border-color:#111; background:#fafafa; }
        .addr-card label { cursor:pointer; width:100%; }
        .coupon-success { color:#198754; font-size:.87rem; }
        .coupon-error   { color:#dc3545; font-size:.87rem; }
        .order-success-overlay { position:fixed; inset:0; background:rgba(255,255,255,.97); z-index:9999;
            display:flex; flex-direction:column; align-items:center; justify-content:center; gap:20px; }
        .check-circle { width:90px; height:90px; border-radius:50%; background:#111; color:#fff;
            display:flex; align-items:center; justify-content:center; font-size:2.5rem; }
    </style>
</head>
<body>

<?php if ($order_placed): ?>
    <div class="order-success-overlay" id="successOverlay">
        <div class="check-circle"><i class="fas fa-check"></i></div>
        <h2 class="fw-bold">Order Confirmed!</h2>
        <p class="text-muted text-center">
            Thank you for your purchase.<br>
            Your order <strong>#<?= str_pad($new_order_id, 5, '0', STR_PAD_LEFT) ?></strong> has been placed successfully.
        </p>
        <div class="d-flex gap-3 mt-2">
            <a href="order-tracking.php" class="btn btn-dark rounded-0 px-4 py-2">Track My Order</a>
            <a href="index.php" class="btn btn-outline-dark rounded-0 px-4 py-2">Continue Shopping</a>
        </div>
    </div>
<?php endif; ?>

<div class="main-wrapper">
    <header class="checkout-header">
        <h1 class="logo"><a href="index.php" style="text-decoration:none;color:inherit;">VELVET</a></h1>
        <span class="secure-text">🔒100% Secure Checkout</span>
    </header>

    <?php if ($place_error): ?>
        <div class="container mt-3">
            <div class="alert alert-danger" style="font-size:.9rem;">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($place_error) ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" id="checkoutForm">
        <input type="hidden" name="coupon_code_hidden" id="couponCodeHidden" value="<?= htmlspecialchars($coupon['code'] ?? '') ?>">
        <input type="hidden" name="place_order" id="placeOrderHidden" value="0">

        <div class="checkout-container">
            <div class="checkout-main">

                <div class="checkout-card">
                    <div class="card-header">
                        <span class="step-number">1</span>
                        <h3>Shipping Address</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($saved_addresses)): ?>
                        <div class="mb-4">
                            <p class="fw-bold small text-uppercase mb-3" style="letter-spacing:1px;">Saved Addresses</p>
                            <?php foreach ($saved_addresses as $addr): ?>
                                <div class="addr-card" onclick="selectAddr(this, <?= $addr['id'] ?>)">
                                    <label class="d-flex align-items-start gap-3 m-0">
                                        <input type="radio" name="saved_addr_radio" value="<?= $addr['id'] ?>"
                                                <?= $addr['is_default'] ? 'checked' : '' ?>
                                               class="mt-1" style="accent-color:#111;">
                                        <div>
                                            <strong><?= htmlspecialchars($addr['full_name']) ?></strong>
                                            <span class="text-muted ms-2 small"><?= htmlspecialchars($addr['phone']) ?></span>
                                            <div class="small text-muted"><?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['street']) ?></div>
                                            <?php if ($addr['is_default']): ?>
                                                <span class="badge bg-dark text-white mt-1" style="font-size:.65rem;letter-spacing:.5px;">DEFAULT</span>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <button type="button" class="btn btn-outline-dark btn-sm rounded-0 mt-2" onclick="toggleNewAddr()">
                                <i class="fas fa-plus me-1"></i> Use a different address
                            </button>
                        </div>

                        <div id="newAddrSection" style="<?= empty($saved_addresses) ? '' : 'display:none;' ?>">
                            <?php else: ?>
                            <div id="newAddrSection">
                                <?php endif; ?>
                                <div class="row">
                                    <div class="input-group">
                                        <label>Full Name *</label>
                                        <input type="text" name="full_name" placeholder="Your full name"
                                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                                    </div>
                                    <div class="input-group">
                                        <label>Phone Number *</label>
                                        <input type="tel" name="phone" placeholder="+970 5x-xxx-xxxx"
                                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label>City *</label>
                                    <input type="text" name="city" placeholder="e.g. Nablus, Ramallah…"
                                           value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                                </div>
                                <div class="input-group">
                                    <label>Detailed Address *</label>
                                    <textarea name="street" rows="3" placeholder="Neighborhood, Street, Building No."><?= htmlspecialchars($_POST['street'] ?? '') ?></textarea>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="save_address" id="saveAddr">
                                    <label class="form-check-label small" for="saveAddr">Save this address for future orders</label>
                                </div>
                            </div>

                            <input type="hidden" name="use_saved_address" id="useSavedAddr" value="<?= !empty($saved_addresses) ? '1' : '0' ?>">
                            <input type="hidden" name="saved_address_id" id="savedAddrId" value="<?= $saved_addresses[0]['id'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="checkout-card">
                        <div class="card-header">
                            <span class="step-number">2</span>
                            <h3>Payment Method</h3>
                        </div>
                        <div class="card-body">
                            <div class="payment-options">
                                <div class="payment-method active" onclick="selectPayment(this,'COD')">
                                    <div class="method-info">
                                        <i class="fas fa-money-bill-wave" style="color:#28a745;font-size:20px;"></i>
                                        <div>
                                            <strong>Cash on Delivery (COD)</strong>
                                            <p>Pay in cash when you receive your order.</p>
                                        </div>
                                    </div>
                                    <span class="check-icon">✓</span>
                                </div>
                                <div class="payment-method" onclick="selectPayment(this,'VISA')">
                                    <div class="method-info">
                                        <div class="method-icons">
                                            <i class="fab fa-cc-visa" style="color:#1a1f71;"></i>
                                            <i class="fab fa-cc-mastercard" style="color:#eb001b;"></i>
                                        </div>
                                        <div>
                                            <strong>Credit / Debit Card</strong>
                                            <p>Secure SSL-encrypted payment.</p>
                                        </div>
                                    </div>
                                    <span class="check-icon">✓</span>
                                </div>
                            </div>
                            <input type="hidden" name="payment_method" id="paymentMethod" value="COD">

                            <div class="mt-4 pt-3 border-top">
                                <label class="fw-bold small text-uppercase mb-2" style="letter-spacing:1px;">Promo Code</label>
                                <div class="d-flex gap-2">
                                    <input type="text" id="couponInput" name="coupon_code"
                                           class="form-control rounded-0"
                                           placeholder="Enter coupon code"
                                           value="<?= htmlspecialchars($_POST['coupon_code'] ?? '') ?>"
                                           style="font-size:.9rem;">
                                    <button type="submit" name="apply_coupon" class="btn btn-outline-dark rounded-0 px-4 fw-bold" style="font-size:.8rem;letter-spacing:1px;">
                                        APPLY
                                    </button>
                                </div>
                                <?php if ($coupon_error): ?>
                                    <div class="coupon-error mt-2"><i class="fas fa-times-circle me-1"></i><?= htmlspecialchars($coupon_error) ?></div>
                                <?php elseif ($coupon): ?>
                                    <div class="coupon-success mt-2">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Coupon <strong><?= htmlspecialchars($coupon['code']) ?></strong> applied — saving ₪<?= number_format($discount, 2) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-3">
                                <label class="fw-bold small text-uppercase mb-2" style="letter-spacing:1px;">Order Notes <span class="text-muted fw-normal">(optional)</span></label>
                                <textarea name="notes" class="form-control rounded-0" rows="2" placeholder="Any special instructions…" style="font-size:.9rem;"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="place_order" class="btn-confirm-order" id="placeBtn">
                        <i class="fas fa-lock me-2"></i> Confirm Order
                    </button>
                </div>

                <aside class="checkout-sidebar">
                    <div class="summary-card">
                        <h3>Order Summary</h3>
                        <div class="summary-items">
                            <?php foreach ($cart_items as $item):
                                $price = $item['is_sale'] && $item['sale_price'] ? (float)$item['sale_price'] : (float)$item['variant_price'];
                                ?>
                                <div class="summary-item">
                                    <?php if ($item['image_url']): ?>
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                    <?php else: ?>
                                        <div class="no-img-placeholder">IMG</div>
                                    <?php endif; ?>

                                    <div class="item-info">
                                        <p class="item-name"><?= htmlspecialchars($item['name']) ?></p>
                                        <p class="item-meta">Size: <?= htmlspecialchars($item['size']) ?> | Qty: <?= $item['quantity'] ?></p>
                                        <?php if ($item['color'] && strtolower($item['color']) !== 'custom'): ?>
                                            <p class="item-meta">Color: <?= htmlspecialchars($item['color']) ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <span class="item-price">₪<?= number_format($price * $item['quantity'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="total-row" style="font-weight:400;font-size:.9rem;border-top:0;padding-top:0;">
                            <span>Subtotal</span><span>₪<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <?php if ($discount > 0): ?>
                            <div class="total-row" style="font-weight:400;font-size:.9rem;color:#198754;">
                                <span>Discount</span><span>−₪<?= number_format($discount, 2) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="total-row" style="font-weight:400;font-size:.9rem;">
                            <span>Shipping</span>
                            <span><?= $shipping == 0 ? 'Free' : '₪'.number_format($shipping, 2) ?></span>
                        </div>
                        <div class="total-row">
                            <strong>Total</strong>
                            <strong>₪<?= number_format($subtotal - $discount + $shipping, 2) ?></strong>
                        </div>
                    </div>
                </aside>
            </div>
    </form>
</div>

<div id="visaModal" class="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9998;display:none;align-items:center;justify-content:center;">
    <div class="modal-content" style="background:#fff;padding:2rem;max-width:420px;width:90%;border-radius:8px;">
        <span style="float:right;cursor:pointer;font-size:1.5rem;" onclick="document.getElementById('visaModal').style.display='none'">&times;</span>
        <h3 class="mb-4"><i class="fas fa-credit-card me-2"></i> Card Details</h3>
        <div class="input-group mb-3">
            <label>Card Number</label>
            <input type="text" id="cardNum" placeholder="0000 0000 0000 0000" maxlength="19">
        </div>
        <div class="row">
            <div class="input-group">
                <label>Expiry</label>
                <input type="text" id="expiry" placeholder="MM/YY" maxlength="5">
            </div>
            <div class="input-group">
                <label>CVV</label>
                <input type="password" id="cvv" placeholder="•••" maxlength="4">
            </div>
        </div>
        <button class="btn-confirm-order mt-3" onclick="submitWithVisa()">
            <i class="fas fa-lock me-2"></i> Pay Now
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let selectedMethod = 'COD';

    function selectPayment(el, method) {
        document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('active'));
        el.classList.add('active');
        selectedMethod = method;
        document.getElementById('paymentMethod').value = method;
    }

    function selectAddr(card, id) {
        document.querySelectorAll('.addr-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        document.getElementById('savedAddrId').value  = id;
        document.getElementById('useSavedAddr').value = '1';
    }

    function toggleNewAddr() {
        const sec = document.getElementById('newAddrSection');
        const isHidden = sec.style.display === 'none';
        sec.style.display = isHidden ? 'block' : 'none';
        document.getElementById('useSavedAddr').value = isHidden ? '0' : '1';
    }

    // On init, mark default address card
    document.addEventListener('DOMContentLoaded', function() {
        const defRadio = document.querySelector('input[name="saved_addr_radio"]:checked');
        if (defRadio) {
            defRadio.closest('.addr-card').classList.add('selected');
        }
        // Sync coupon hidden
        const ci = document.getElementById('couponInput');
        if (ci) {
            ci.addEventListener('input', function() {
                document.getElementById('couponCodeHidden').value = this.value;
            });
        }
    });

    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        if (this.querySelector('[name="place_order"]') && e.submitter && e.submitter.name === 'place_order') {
            if (selectedMethod === 'VISA') {
                e.preventDefault();
                document.getElementById('visaModal').style.display = 'flex';
            }
        }
    });

    function submitWithVisa() {
        const cn = document.getElementById('cardNum').value.trim().replace(/\s/g,'');
        const ex = document.getElementById('expiry').value.trim();
        const cv = document.getElementById('cvv').value.trim();
        if (cn.length < 13 || ex.length < 4 || cv.length < 3) {
            alert('Please fill in all card details correctly.');
            return;
        }

        document.getElementById('placeOrderHidden').value = "1";

        document.getElementById('visaModal').style.display = 'none';
        document.getElementById('checkoutForm').submit();
    }

    const cardNumEl = document.getElementById('cardNum');
    if (cardNumEl) {
        cardNumEl.addEventListener('input', function() {
            let v = this.value.replace(/\D/g,'').substring(0,16);
            this.value = v.replace(/(.{4})/g,'$1 ').trim();
        });
    }
    const expiryEl = document.getElementById('expiry');
    if (expiryEl) {
        expiryEl.addEventListener('input', function() {
            let v = this.value.replace(/\D/g,'');
            if (v.length >= 2) v = v.substring(0,2)+'/'+v.substring(2,4);
            this.value = v;
        });
    }
</script>
</body>
</html>