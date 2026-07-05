<?php
include 'dbconnect.php';
include 'auth.php';
/** @var PDO $pdo */

// Order tracking is public — can be searched by order ID + email
// But if logged in, we also show their own orders in quick-select

$isLoggedIn = isLoggedIn();
$user_id    = currentUserId();
$isAdmin    = isAdmin();
$userLetter = $isLoggedIn ? htmlspecialchars($_SESSION['user_letter'] ?? '') : '';

// Fetch current user's orders for quick select (if logged in)
$my_orders = [];
if ($isLoggedIn) {
    $mos = $pdo->prepare("
        SELECT id, status, ordered_at, total_amount 
        FROM orders WHERE user_id = ? ORDER BY ordered_at DESC LIMIT 10
    ");
    $mos->execute([$user_id]);
    $my_orders = $mos->fetchAll();
}

// AJAX endpoint — return order JSON
if (isset($_GET['fetch_order'])) {
    header('Content-Type: application/json');
    $order_id = (int)($_GET['order_id'] ?? 0);
    $email    = trim($_GET['email'] ?? '');

    // Look up order + user email
    $os = $pdo->prepare("
        SELECT o.*, u.email, u.full_name as user_name,
               a.full_name as addr_name, a.phone, a.city, a.street
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN addresses a ON o.address_id = a.id
        WHERE o.id = ?
    ");
    $os->execute([$order_id]);
    $order = $os->fetch();

    if (!$order || strtolower($order['email']) !== strtolower($email)) {
        echo json_encode(['error' => 'Order not found. Please check your order number and email.']);
        exit;
    }

    // Items
    $is = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $is->execute([$order_id]);
    $items = $is->fetchAll();

    // Tracking events
    $ts = $pdo->prepare("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY happened_at ASC");
    $ts->execute([$order_id]);
    $tracking = $ts->fetchAll();

    // Map status to step index
    $statusSteps = ['pending'=>0,'confirmed'=>1,'processing'=>1,'shipped'=>2,'delivered'=>4,'cancelled'=>-1];
    $activeStep  = $statusSteps[$order['status']] ?? 0;

    echo json_encode([
        'order'    => $order,
        'items'    => $items,
        'tracking' => $tracking,
        'activeStep'=> $activeStep,
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order — VELVET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="order-tracking.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3" href="index.php">VELVET</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="shop.php">Shop</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <?php if ($isAdmin): ?>
                    <a href="Manager.php"  style="font-size:larger; color:#b30000;"><i class="fas fa-cog"></i></a>
                <?php endif; ?>
                <?php if ($isLoggedIn): ?>
                <a href="shopping%20bag.php" class="text-dark"><i class="fa-solid fa-cart-shopping fs-5"></i></a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</nav>

<div class="page-banner">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Track Order</li>
            </ol>
        </nav>
        <h1 class="mt-2">Track Your Order</h1>
        <p class="mb-0" style="color:#aaa;font-size:.9rem;">Real-time updates on your VELVET delivery</p>
    </div>
</div>

<main class="container py-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-4 col-md-5">
            <div class="lookup-card">
                <h5><i class="fa-regular fa-magnifying-glass me-2" style="color:var(--red)"></i>Find Your Order</h5>
                <p class="sub">Enter your order number and email to get live updates.</p>

                <div class="mb-3">
                    <label class="form-label">Order Number</label>
                    <input type="number" class="form-control" id="orderInput" placeholder="e.g. 12" min="1">
                </div>
                <div class="mb-4">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="emailInput" placeholder="your@email.com">
                </div>
                <button class="btn-track" onclick="trackOrder()">
                    <i class="fas fa-search me-2"></i>Track Order
                </button>

                <?php if ($isLoggedIn && !empty($my_orders)): ?>
                <div class="divider-or"><span>your recent orders</span></div>
                <div class="sample-chips">
                    <?php foreach ($my_orders as $mo):
                        $statusColors = ['pending'=>'#856404','confirmed'=>'#055160','processing'=>'#856404','shipped'=>'#0c5460','delivered'=>'#155724','cancelled'=>'#721c24'];
                        $col = $statusColors[$mo['status']] ?? '#333';
                    ?>
                    <span class="order-chip"
                          onclick="quickLoad(<?= $mo['id'] ?>)"
                          title="₪<?= number_format($mo['total_amount'],2) ?> — <?= date('M j, Y', strtotime($mo['ordered_at'])) ?>">
                        <span class="chip-dot" style="background:<?= $col ?>"></span>
                        Order #<?= str_pad($mo['id'],5,'0',STR_PAD_LEFT) ?>
                        <span class="text-muted" style="font-size:.72rem;"><?= ucfirst($mo['status']) ?></span>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="help-card mt-3">
                <i class="fas fa-headset d-block"></i>
                <h6>Need Help?</h6>
                <p>Our support team is available during business hours.</p>
                <?php
                $support_phone = $settings['support_phone'] ?? '';
                $support_email = $settings['support_email'] ?? '';
                ?>
                <?php if ($support_email): ?>
                <a href="mailto:<?= htmlspecialchars($support_email) ?>" class="btn-help">
                    <i class="fas fa-envelope me-1"></i> Email Us
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-8 col-md-7">
            <div class="spinner-wrap" id="spinner">
                <div class="spinner-ring"></div>
                <p>Fetching your order…</p>
            </div>
            <div id="error-panel">
                <i class="fas fa-circle-exclamation fa-lg"></i>
                <div>
                    <strong>Order not found.</strong><br>
                    <span style="font-weight:400;color:#555">Please double-check your order number and email address.</span>
                </div>
            </div>
            <div id="result-panel">
                <div class="order-meta-card mb-4">
                    <div class="order-meta-header">
                        <span class="order-number" id="res-order-number"></span>
                        <span class="status-badge" id="res-status-badge">
                            <span class="pulse"></span>
                            <span id="res-status-text"></span>
                        </span>
                    </div>
                    <div class="order-meta-body">
                        <div class="meta-item"><label>Order Date</label><span id="res-order-date"></span></div>
                        <div class="meta-item"><label>Payment</label><span id="res-payment"></span></div>
                        <div class="meta-item"><label>Total</label><span id="res-total"></span></div>
                        <div class="meta-item"><label>Payment Status</label><span id="res-pay-status"></span></div>
                    </div>
                </div>

                <div class="tracking-card mb-4">
                    <h6>Shipment Progress</h6>
                    <div class="stepper" id="stepper"></div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="tracking-card h-100">
                            <h6>Activity Log</h6>
                            <div class="timeline" id="timeline"></div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="delivery-card mb-4">
                            <h6>Delivery Details</h6>
                            <div class="info-row">
                                <i class="fas fa-location-dot"></i>
                                <div class="info-text">
                                    <div class="label">Shipping Address</div>
                                    <div class="value" id="res-address"></div>
                                </div>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-user"></i>
                                <div class="info-text">
                                    <div class="label">Recipient</div>
                                    <div class="value" id="res-recipient"></div>
                                </div>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-phone"></i>
                                <div class="info-text">
                                    <div class="label">Phone</div>
                                    <div class="value" id="res-phone"></div>
                                </div>
                            </div>
                        </div>
                        <div class="order-items-card">
                            <div class="order-items-header">Items in this order</div>
                            <div id="res-items"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ALL_STEPS = [
    { label:'Order Placed',     icon:'fas fa-check' },
    { label:'Confirmed',        icon:'fas fa-thumbs-up' },
    { label:'Shipped',          icon:'fas fa-box' },
    { label:'Out for Delivery', icon:'fas fa-truck' },
    { label:'Delivered',        icon:'fas fa-house-circle-check' },
];

const STATUS_BADGE_MAP = {
    pending:    { cls:'processing', label:'Pending' },
    confirmed:  { cls:'shipped',    label:'Confirmed' },
    processing: { cls:'processing', label:'Processing' },
    shipped:    { cls:'shipped',    label:'Shipped' },
    delivered:  { cls:'delivered',  label:'Delivered' },
    cancelled:  { cls:'cancelled',  label:'Cancelled' },
};

function showPanel(id) {
    ['result-panel','error-panel','spinner'].forEach(p => {
        document.getElementById(p).classList.remove('show');
    });
    if (id) document.getElementById(id).classList.add('show');
    if (id === 'result-panel')
        setTimeout(() => document.getElementById('result-panel').scrollIntoView({behavior:'smooth',block:'start'}), 100);
}

function trackOrder() {
    const num   = document.getElementById('orderInput').value.trim();
    const email = document.getElementById('emailInput').value.trim();

    if (!num || !email) {
        document.getElementById('orderInput').style.borderColor = 'var(--red)';
        setTimeout(() => document.getElementById('orderInput').style.borderColor = '', 1500);
        return;
    }
    doFetch(num, email);
}

function quickLoad(orderId) {
    // Pre-fill and fetch with the logged-in user's email
    document.getElementById('orderInput').value = orderId;
    const emailEl = document.getElementById('emailInput');
    // Try to get email from PHP-injected variable
    if (window.CURRENT_USER_EMAIL) {
        emailEl.value = window.CURRENT_USER_EMAIL;
        doFetch(orderId, window.CURRENT_USER_EMAIL);
    } else {
        emailEl.focus();
        alert('Please enter your email address to verify the order.');
    }
}

function doFetch(orderId, email) {
    showPanel('spinner');
    fetch(`order-tracking.php?fetch_order=1&order_id=${encodeURIComponent(orderId)}&email=${encodeURIComponent(email)}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                showPanel('error-panel');
                document.getElementById('error-panel').querySelector('strong').textContent = data.error;
                return;
            }
            renderOrder(data);
            showPanel('result-panel');
        })
        .catch(() => showPanel('error-panel'));
}

function renderOrder(data) {
    const o = data.order;
    const orderNum = '#' + String(o.id).padStart(5, '0');

    document.getElementById('res-order-number').textContent = orderNum;
    document.getElementById('res-order-date').textContent   = formatDate(o.ordered_at);
    document.getElementById('res-payment').textContent      = o.payment_method;
    document.getElementById('res-total').textContent        = '₪ ' + parseFloat(o.total_amount).toFixed(2);

    const payStatus = o.payment_status;
    document.getElementById('res-pay-status').innerHTML = payStatus === 'paid'
        ? '<span style="color:#198754;font-weight:600;">✓ Paid</span>'
        : '<span style="color:#856404;">Pending</span>';

    // Status badge
    const bmap  = STATUS_BADGE_MAP[o.status] || { cls:'', label: o.status };
    const badge = document.getElementById('res-status-badge');
    badge.className = 'status-badge ' + bmap.cls;
    document.getElementById('res-status-text').textContent = bmap.label;

    // Delivery details
    document.getElementById('res-address').textContent   = o.city ? (o.street + ', ' + o.city) : 'N/A';
    document.getElementById('res-recipient').textContent = o.addr_name || o.user_name || '—';
    document.getElementById('res-phone').textContent     = o.phone || '—';

    // Stepper
    const active = data.activeStep;
    const cancelled = o.status === 'cancelled';
    const stepperEl = document.getElementById('stepper');
    if (cancelled) {
        stepperEl.innerHTML = `<div class="step" style="color:#dc3545;font-weight:600;">
            <div class="step-icon" style="background:#dc3545;"><i class="fas fa-times"></i></div>
            <div><div class="step-label">Order Cancelled</div></div>
        </div>`;
    } else {
        stepperEl.innerHTML = ALL_STEPS.map((s, i) => {
            const state = i < active ? 'done' : i === active ? 'active' : 'pending';
            return `<div class="step ${state}">
                <div class="step-icon"><i class="${s.icon}"></i></div>
                <div><div class="step-label">${s.label}</div></div>
            </div>`;
        }).join('');
    }

    // Timeline from DB tracking events
    const tlEl = document.getElementById('timeline');
    if (data.tracking && data.tracking.length) {
        tlEl.innerHTML = data.tracking.map((t, i) => {
            const isLast = i === data.tracking.length - 1;
            return `<div class="timeline-item ${isLast ? 'active' : 'done'}">
                <div class="tl-time">${formatDate(t.happened_at)}</div>
                <div class="tl-title">${escHtml(t.event_label)}</div>
                ${t.description ? `<div class="tl-desc">${escHtml(t.description)}</div>` : ''}
            </div>`;
        }).join('');
    } else {
        tlEl.innerHTML = '<div class="tl-desc text-muted">No tracking events yet.</div>';
    }

    // Order items
    const itemsEl = document.getElementById('res-items');
    itemsEl.innerHTML = data.items.map(it => `
        <div class="order-item-row">
            <div class="item-thumb"><i class="fas fa-shirt"></i></div>
            <div class="item-info">
                <div class="item-name">${escHtml(it.product_name)}</div>
                <div class="item-meta">${it.size ? 'Size: ' + it.size : ''} ${it.color ? '· ' + it.color : ''} · Qty: ${it.quantity}</div>
            </div>
            <div class="item-price">₪${parseFloat(it.unit_price * it.quantity).toFixed(2)}</div>
        </div>
    `).join('');
}

function formatDate(ds) {
    if (!ds) return '—';
    const d = new Date(ds);
    return d.toLocaleDateString('en-GB', {day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
}

function escHtml(s) {
    if (!s) return '';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

document.addEventListener('DOMContentLoaded', () => {
    ['orderInput','emailInput'].forEach(id => {
        document.getElementById(id).addEventListener('keydown', e => {
            if (e.key === 'Enter') trackOrder();
        });
    });
});
</script>

<?php if ($isLoggedIn):
    $emailStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $emailStmt->execute([$user_id]);
    $currentEmail = $emailStmt->fetchColumn();
?>
<script>
window.CURRENT_USER_EMAIL = <?= json_encode($currentEmail) ?>;
</script>
<?php endif; ?>


</body>
</html>
