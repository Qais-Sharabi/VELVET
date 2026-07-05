<?php

session_start();
include 'dbconnect.php';
/** @var PDO $pdo */

// ════════════════════════════════════════════════════════════
//  AJAX HANDLERS — must be before any HTML output
// ════════════════════════════════════════════════════════════

// ── Update order status ──────────────────────────────────────
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'update_order_status') {
    header('Content-Type: application/json');
    $order_id  = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status']    ?? '');
    $allowed   = ['pending','confirmed','processing','shipped','delivered','cancelled'];
    if (!$order_id || !in_array($newStatus, $allowed)) {
        echo json_encode(['success'=>false,'message'=>'Invalid data']); exit;
    }
    try {
        $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$newStatus,$order_id]);
        $pdo->prepare("INSERT INTO order_tracking (order_id,event_label,description) VALUES(?,?,?)")
                ->execute([$order_id, ucfirst($newStatus), 'Status updated by admin.']);
        echo json_encode(['success'=>true]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

// ── Toggle customer active ───────────────────────────────────
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle_customer') {
    header('Content-Type: application/json');
    $user_id = (int)($_POST['user_id'] ?? 0);
    if (!$user_id) { echo json_encode(['success'=>false]); exit; }
    try {
        $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id=?")->execute([$user_id]);
        $s = $pdo->prepare("SELECT is_active FROM users WHERE id=?");
        $s->execute([$user_id]);
        echo json_encode(['success'=>true,'is_active'=>(int)$s->fetchColumn()]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

// ── Get full order detail ────────────────────────────────────
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_order') {
    header('Content-Type: application/json');
    $order_id = (int)($_GET['order_id'] ?? 0);
    if (!$order_id) { echo json_encode([]); exit; }
    $q = $pdo->prepare("
        SELECT o.*, u.full_name, u.email, u.phone,
               a.city, a.street, a.full_name AS ship_name, a.phone AS ship_phone
        FROM   orders o
        JOIN   users u        ON u.id = o.user_id
        LEFT JOIN addresses a ON a.id = o.address_id
        WHERE  o.id = ?
    ");
    $q->execute([$order_id]);
    $data = $q->fetch();
    if (!$data) { echo json_encode([]); exit; }
    $qi = $pdo->prepare("
        SELECT oi.*, COALESCE(pi.image_url,'placeholder.jpg') AS image_url
        FROM   order_items oi
        LEFT JOIN product_images pi ON pi.product_id=oi.product_id AND pi.is_primary=1
        WHERE  oi.order_id=?
    ");
    $qi->execute([$order_id]);
    $data['items'] = $qi->fetchAll();
    $qt = $pdo->prepare("SELECT * FROM order_tracking WHERE order_id=? ORDER BY happened_at ASC");
    $qt->execute([$order_id]);
    $data['tracking'] = $qt->fetchAll();
    echo json_encode($data);
    exit;
}

// ── Upload admin avatar ─────────────────────────────────────
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'upload_avatar') {
    header('Content-Type: application/json');
    $admin_id = (int)($_SESSION['user_id'] ?? 0);
    if (!$admin_id) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success'=>false,'message'=>'No file uploaded or upload error.']); exit;
    }

    $file     = $_FILES['avatar'];
    $allowed  = ['image/jpeg','image/png','image/gif','image/webp'];
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mime     = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        echo json_encode(['success'=>false,'message'=>'Only JPG, PNG, GIF and WebP images are allowed.']); exit;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success'=>false,'message'=>'File must be under 2 MB.']); exit;
    }

    // Save to images/avatars/
    $avatarDir = __DIR__ . '/images/avatars/';
    if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);

    // Delete old avatar file if exists
    $old = $pdo->prepare("SELECT avatar_url FROM users WHERE id=?");
    $old->execute([$admin_id]);
    $oldUrl = $old->fetchColumn();
    if ($oldUrl && file_exists(__DIR__ . '/' . $oldUrl)) {
        @unlink(__DIR__ . '/' . $oldUrl);
    }

    $ext      = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'][$mime];
    $filename = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
    $dest     = $avatarDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['success'=>false,'message'=>'Failed to save file.']); exit;
    }

    $relPath = 'images/avatars/' . $filename;
    $pdo->prepare("UPDATE users SET avatar_url=? WHERE id=?")->execute([$relPath, $admin_id]);
    echo json_encode(['success'=>true,'url'=>$relPath]);
    exit;
}

// ── Remove admin avatar ──────────────────────────────────────
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'remove_avatar') {
    header('Content-Type: application/json');
    $admin_id = (int)($_SESSION['user_id'] ?? 0);
    if (!$admin_id) { echo json_encode(['success'=>false]); exit; }

    $old = $pdo->prepare("SELECT avatar_url FROM users WHERE id=?");
    $old->execute([$admin_id]);
    $oldUrl = $old->fetchColumn();
    if ($oldUrl && file_exists(__DIR__ . '/' . $oldUrl)) {
        @unlink(__DIR__ . '/' . $oldUrl);
    }
    $pdo->prepare("UPDATE users SET avatar_url=NULL WHERE id=?")->execute([$admin_id]);
    echo json_encode(['success'=>true]);
    exit;
}

// ── Send reply email to message sender ──────────────────────
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'send_reply') {
    header('Content-Type: application/json');
    $msg_id  = (int)($_POST['msg_id']      ?? 0);
    $reply   = trim($_POST['reply_text']   ?? '');
    $to_email= trim($_POST['to_email']     ?? '');
    $to_name = trim($_POST['to_name']      ?? '');

    if (!$reply || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success'=>false,'message'=>'Invalid reply or email.']); exit;
    }

    // Include mailer if not already loaded
    if (!function_exists('sendEmail')) include __DIR__ . '/mailer.php';

    $subject  = 'Reply from VELVET — We received your message';
    $year     = date('Y');
    $safeReply = nl2br(htmlspecialchars($reply));
    $safeName  = htmlspecialchars($to_name);

    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='padding:40px 0;background:#f5f5f5;'>
<tr><td align='center'>
<table width='520' cellpadding='0' cellspacing='0'
       style='background:#fff;border-radius:4px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);'>
  <tr><td style='background:#111;padding:24px 40px;text-align:center;'>
    <span style='color:#fff;font-size:24px;font-weight:700;letter-spacing:4px;'>VELVET</span>
  </td></tr>
  <tr><td style='padding:32px 40px;'>
    <p style='color:#555;font-size:15px;margin:0 0 6px;'>Hi <strong>{$safeName}</strong>,</p>
    <p style='color:#555;font-size:14px;margin:0 0 20px;'>Thank you for reaching out to us. Here is our reply:</p>
    <div style='background:#f8f8f8;border-left:4px solid #111;padding:16px 20px;border-radius:4px;font-size:14px;color:#333;line-height:1.7;margin-bottom:24px;'>
      {$safeReply}
    </div>
    <p style='color:#999;font-size:12px;margin:0;'>If you have further questions, simply reply to this email.</p>
  </td></tr>
  <tr><td style='background:#f9f9f9;padding:14px 40px;text-align:center;'>
    <span style='color:#bbb;font-size:12px;'>&copy; {$year} VELVET. All rights reserved.</span>
  </td></tr>
</table>
</td></tr>
</table>
</body></html>";

    $result = sendEmail($to_email, $to_name, $subject, $html, $reply);

    if ($result === true) {
        // Mark as read automatically after replying
        $pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([$msg_id]);
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Mail error: ' . $result]);
    }
    exit;
}




// ── Mark message as read ────────────────────────────────────
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'mark_message_read') {
    header('Content-Type: application/json');
    $msg_id = (int)($_POST['msg_id'] ?? 0);
    if (!$msg_id) { echo json_encode(['success'=>false]); exit; }
    try {
        $pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([$msg_id]);
        echo json_encode(['success'=>true]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

// ── Delete message ───────────────────────────────────────────
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'delete_message') {
    header('Content-Type: application/json');
    $msg_id = (int)($_POST['msg_id'] ?? 0);
    if (!$msg_id) { echo json_encode(['success'=>false]); exit; }
    try {
        $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([$msg_id]);
        echo json_encode(['success'=>true]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

// ── Update admin email ───────────────────────────────────────
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'update_admin_email') {
    header('Content-Type: application/json');
    $new_email = trim($_POST['new_email'] ?? '');
    $password  = $_POST['password']   ?? '';
    $admin_id  = (int)($_SESSION['user_id'] ?? 0);
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success'=>false,'message'=>'Invalid email address.']); exit;
    }
    // Verify current password
    $u = $pdo->prepare("SELECT password_hash FROM users WHERE id=?");
    $u->execute([$admin_id]);
    $row = $u->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        echo json_encode(['success'=>false,'message'=>'Current password is incorrect.']); exit;
    }
    // Check email not taken
    $chk = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
    $chk->execute([$new_email, $admin_id]);
    if ($chk->fetch()) {
        echo json_encode(['success'=>false,'message'=>'This email is already in use.']); exit;
    }
    try {
        $pdo->prepare("UPDATE users SET email=? WHERE id=?")->execute([$new_email, $admin_id]);
        echo json_encode(['success'=>true,'new_email'=>$new_email]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

// ── Update admin password ────────────────────────────────────
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'update_admin_password') {
    header('Content-Type: application/json');
    $current  = $_POST['current_password'] ?? '';
    $new_pw   = $_POST['new_password']     ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $admin_id = (int)($_SESSION['user_id'] ?? 0);
    if (strlen($new_pw) < 6) {
        echo json_encode(['success'=>false,'message'=>'New password must be at least 6 characters.']); exit;
    }
    if ($new_pw !== $confirm) {
        echo json_encode(['success'=>false,'message'=>'New passwords do not match.']); exit;
    }
    $u = $pdo->prepare("SELECT password_hash FROM users WHERE id=?");
    $u->execute([$admin_id]);
    $row = $u->fetch();
    if (!$row || !password_verify($current, $row['password_hash'])) {
        echo json_encode(['success'=>false,'message'=>'Current password is incorrect.']); exit;
    }
    try {
        $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")
                ->execute([password_hash($new_pw, PASSWORD_DEFAULT), $admin_id]);
        echo json_encode(['success'=>true]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}


// ════════════════════════════════════════════════════════════
//  LOAD SETTINGS SAFELY
// ════════════════════════════════════════════════════════════
try {
    $settings = $pdo->query("SELECT * FROM store_settings WHERE id=1")->fetch();
} catch(Exception $e) {
    $settings = [];
}
// If store_settings table is empty or missing, use safe defaults
if (!$settings) {
    $settings = [
            'store_name'          => 'VELVET',
            'support_phone'       => '',
            'support_email'       => '',
            'instagram_url'       => '',
            'facebook_url'        => '',
            'low_stock_threshold' => 5,
            'shipping_fee'        => 20,
            'free_shipping_above' => 300,
            'cod_enabled'         => 1,
            'maintenance_mode'    => 0,
            'accent_color'        => '#3498db',
    ];
}
$threshold = (int)($settings['low_stock_threshold'] ?? 5);

// Admin user info (including avatar)
$adminUser = $pdo->prepare("SELECT id, full_name, email, avatar_url FROM users WHERE id=?");
$adminUser->execute([$_SESSION['user_id']]);
$adminUser = $adminUser->fetch();

// Contact messages
$contactMessages = $pdo->query("
    SELECT * FROM contact_messages ORDER BY is_read ASC, sent_at DESC
")->fetchAll();
$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();

// ════════════════════════════════════════════════════════════
//  DASHBOARD DATA
// ════════════════════════════════════════════════════════════

// Revenue: all non-cancelled orders
$totalRevenue = $pdo->query("
    SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled'
")->fetchColumn();

$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalUsers  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();

// LOW STOCK: count products by total stock (not individual variants)
// Includes products with NO variants at all (never stocked = 0)
$lowStmt = $pdo->prepare("
    SELECT p.id, p.name,
           COUNT(pv.id) AS variant_count,
           COALESCE(SUM(pv.stock_qty), 0) AS total_stock
    FROM   products p
    LEFT JOIN product_variants pv ON pv.product_id = p.id
    WHERE  p.is_active = 1
    GROUP  BY p.id, p.name
    HAVING COALESCE(SUM(pv.stock_qty), 0) <= ?
    ORDER  BY total_stock ASC, p.name ASC
");
$lowStmt->execute([$threshold]);
$lowStockItems = $lowStmt->fetchAll();
$lowStockCount = count($lowStockItems);

// Status counts
$statusCounts = [];
foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s) {
    $st = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status=?");
    $st->execute([$s]);
    $statusCounts[$s] = (int)$st->fetchColumn();
}

// All orders — LEFT JOIN so orders with no items still appear
$allOrders = $pdo->query("
    SELECT o.id, u.full_name, o.total_amount, o.status,
           o.payment_method, o.payment_status, o.ordered_at,
           COALESCE(
               GROUP_CONCAT(
                   oi.product_name,' (',COALESCE(oi.size,'?'),') x',oi.quantity
                   ORDER BY oi.id SEPARATOR ', '
               ), '—'
           ) AS items
    FROM   orders o
    JOIN   users u          ON u.id = o.user_id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP  BY o.id
    ORDER  BY o.ordered_at DESC
")->fetchAll();

// ── CHART DATA ─────────────────────────────────────────────
// Monthly revenue — last 6 months (non-cancelled)
$monthlyRevenue = [];
$monthlyLabels  = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $rv = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount),0)
        FROM orders
        WHERE DATE_FORMAT(ordered_at,'%Y-%m') = ? AND status != 'cancelled'
    ");
    $rv->execute([$month]);
    $monthlyRevenue[] = (float)$rv->fetchColumn();
    $monthlyLabels[]  = $label;
}

// Daily orders — last 7 days
$dailyOrders = [];
$dailyLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $day   = date('Y-m-d', strtotime("-$i days"));
    $label = date('D d', strtotime("-$i days"));
    $dc = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(ordered_at) = ?");
    $dc->execute([$day]);
    $dailyOrders[] = (int)$dc->fetchColumn();
    $dailyLabels[] = $label;
}

// Top 5 selling products by quantity
$topProducts = $pdo->query("
    SELECT oi.product_name, SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.status != 'cancelled'
    GROUP BY oi.product_name
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll();

// Revenue by category
$categoryRevenue = $pdo->query("
    SELECT 
        parent.name AS cat_name, 
        SUM(oi.unit_price * oi.quantity) AS revenue
    FROM categories child
    JOIN categories parent ON child.parent_id = parent.id
    JOIN products p ON p.category_id = child.id
    JOIN order_items oi ON oi.product_id = p.id
    JOIN orders o ON o.id = oi.order_id
    WHERE o.status != 'cancelled'
    GROUP BY parent.id, parent.name
    HAVING revenue > 0
")->fetchAll(PDO::FETCH_ASSOC);


// Products
$products = $pdo->query("
    SELECT p.id, p.name, p.slug, p.base_price, p.sale_price,
           p.is_sale, p.is_new, p.is_bestseller, p.is_active,
           p.description, p.category_id,
           c.slug AS cat_slug, c.name AS cat_name, cp.name AS parent_name,
           pi.image_url,
           COALESCE(SUM(pv.stock_qty),0) AS total_stock
    FROM   products p
    LEFT JOIN categories     c  ON c.id  = p.category_id
    LEFT JOIN categories     cp ON cp.id = c.parent_id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    LEFT JOIN product_variants pv ON pv.product_id = p.id
    GROUP  BY p.id
    ORDER  BY p.id DESC
")->fetchAll();

// Customers
$customers = $pdo->query("
    SELECT u.id, u.full_name, u.email, u.phone, u.is_active, u.created_at,
           COUNT(o.id) AS order_count,
           COALESCE(SUM(o.total_amount),0) AS total_spent
    FROM   users u
    LEFT JOIN orders o ON o.user_id = u.id
    WHERE  u.role='customer'
    GROUP  BY u.id
    ORDER  BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VELVET - Admin Manager</title>
    <link rel="stylesheet" href="manager_style.css">
    <link rel="stylesheet" href="manage_products.css">
    <link rel="stylesheet" href="settings.css">
    <link rel="stylesheet" href="dark_mode.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* Status colours missing from manager_style.css */
        .status.confirmed  { background:#cce5ff; color:#004085; }
        .status.processing { background:#fff3cd; color:#856404; }
        .status.cancelled  { background:#f8d7da; color:#721c24; }
        .status.failed     { background:#f8d7da; color:#721c24; }
        .status.paid       { background:#d4edda; color:#155724; }

        /* Order modal */
        #order-modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.55); z-index:9999;
            align-items:center; justify-content:center;
        }
        #order-modal-overlay.open { display:flex; }
        #order-modal-box {
            background:#fff; border-radius:14px; padding:32px;
            width:720px; max-width:95vw; max-height:90vh;
            overflow-y:auto; position:relative;
            box-shadow:0 20px 60px rgba(0,0,0,.3);
        }
        #order-modal-box h3 { margin:0 0 18px; font-size:19px; }
        #order-modal-box h4 { font-size:13px; color:#666; margin:18px 0 8px;
            text-transform:uppercase; letter-spacing:.06em; }
        .close-order-modal { position:absolute; top:14px; right:18px;
            background:none; border:none; font-size:24px; cursor:pointer; color:#aaa; }
        .close-order-modal:hover { color:#ff4757; }
        .order-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:20px; }
        .order-info-grid div { background:#f8f8f8; border-radius:8px; padding:10px 14px; font-size:13px; }
        .order-info-grid div b { display:block; font-size:10px; color:#999; text-transform:uppercase; margin-bottom:3px; }
        .order-items-table { width:100%; border-collapse:collapse; font-size:13px; margin-bottom:20px; }
        .order-items-table th { background:#f4f4f4; padding:9px 11px; text-align:left; font-size:11px; text-transform:uppercase; color:#888; }
        .order-items-table td { padding:9px 11px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
        .order-items-table img { width:42px; height:52px; object-fit:cover; border-radius:5px; }
        .tracking-event { display:flex; gap:12px; padding:8px 0; font-size:13px; border-bottom:1px solid #f0f0f0; }
        .tracking-event:last-child { border:none; }
        .tracking-dot { width:10px; height:10px; border-radius:50%; background:#28a745; flex-shrink:0; margin-top:4px; }
        .status-select { padding:7px 11px; border-radius:8px; border:1px solid #ddd; font-size:13px; cursor:pointer; }
        .update-status-btn { padding:8px 18px; background:#2c3e50; color:#fff; border:none; border-radius:8px; font-size:13px; cursor:pointer; margin-left:8px; transition:.2s; }
        .update-status-btn:hover { background:var(--accent); }

        /* Low stock */
        .low-stock-list { margin-top:10px; margin-bottom:30px; }
        .low-stock-item { display:flex; justify-content:space-between; align-items:center;
            padding:9px 14px; background:#fff8e1; border-radius:8px;
            margin-bottom:7px; font-size:13px; border-left:3px solid #f59e0b; }
        .low-stock-qty  { font-weight:700; color:#856404; }
        .out-of-stock   { background:#fff0f0 !important; border-left-color:#e74c3c !important; }
        .out-of-stock .low-stock-qty { color:#c0392b; }

        /* Status breakdown */
        .status-breakdown { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:30px; }
        .status-breakdown-box { background:#fff; border-radius:10px; padding:14px 20px;
            min-width:120px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .status-breakdown-box p { font-size:24px; font-weight:700; margin-top:6px; color:#2c3e50; }
        .chart-card-header{
            margin-bottom: 30px;
        }
        .chart-card{
            margin-bottom: 30px;
        }

        /* ════════════════════════════════════════
           MESSAGES SECTION
        ════════════════════════════════════════ */
        .messages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 18px;
        }
        .msg-card {
            background: #fff;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
            border-left: 4px solid transparent;
            transition: transform .15s, box-shadow .15s;
        }
        .msg-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.1); }
        .msg-unread { border-left-color: var(--accent, #3498db); }
        .msg-read   { border-left-color: #e0e0e0; opacity: .85; }
        .msg-header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
        .msg-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--accent, #3498db); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 16px; flex-shrink: 0;
        }
        .msg-meta  { flex: 1; min-width: 0; }
        .msg-name  { font-weight: 700; font-size: 14px; color: #2c3e50; display: flex; align-items: center; gap: 6px; }
        .msg-email { font-size: 12px; color: #888; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .msg-time  { font-size: 11px; color: #aaa; text-align: right; flex-shrink: 0; line-height: 1.6; }
        .msg-new-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--accent, #3498db); display: inline-block; flex-shrink: 0;
        }
        .msg-body {
            font-size: 13px; color: #444; line-height: 1.65;
            background: #f8f9fa; border-radius: 8px;
            padding: 10px 14px; margin-bottom: 14px;
            max-height: 100px; overflow-y: auto;
        }
        .msg-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .msg-btn {
            padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;
            border: none; cursor: pointer; text-decoration: none; display: inline-flex;
            align-items: center; gap: 5px; transition: .15s;
        }
        .msg-btn-reply  { background: var(--accent, #3498db); color: #fff; }
        .msg-btn-reply:hover { filter: brightness(1.12); }
        .msg-btn-read   { background: #e8f5e9; color: #27ae60; }
        .msg-btn-read:hover { background: #27ae60; color: #fff; }
        .msg-btn-delete { background: #ffeaea; color: #e74c3c; margin-left: auto; }
        .msg-btn-delete:hover { background: #e74c3c; color: #fff; }
        .msg-read-badge { font-size: 11px; color: #27ae60; display: flex; align-items: center; gap: 4px; }
        body.dark-mode .msg-card  { background: #1e1e1e; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
        body.dark-mode .msg-body  { background: #2a2a2a; color: #ccc; }
        body.dark-mode .msg-name  { color: #eee; }
        body.dark-mode .msg-email { color: #888; }
    </style>
</head>
<body>

<!-- Apply saved accent colour immediately -->
<script>
    (function(){
        var saved = localStorage.getItem('velvet_accent') || '<?= htmlspecialchars($settings['accent_color'] ?? '#3498db') ?>';
        document.documentElement.style.setProperty('--accent', saved);
        if (localStorage.getItem('velvet_dark') === '1') document.body.classList.add('dark-mode');
    })();
</script>

<div class="sidebar">
    <div class="sidebar-header"><h1><span>VELVET</span></h1></div>
    <div class="menu-items">
        <button class="subdiv active" data-target="dashboard-section"><b>Dashboard</b></button>
        <button class="subdiv" data-target="products-section"><b>Manage Products</b></button>
        <button class="subdiv" data-target="orders-section"><b>Orders</b></button>
        <button class="subdiv" data-target="customers-section"><b>Customers</b></button>
        <button class="subdiv" data-target="messages-section" id="messages-nav-btn">
            <b>Messages</b>
            <?php if ($unreadCount > 0): ?>
                <span id="msg-badge" style="
                background:#e74c3c;color:#fff;font-size:10px;font-weight:700;
                border-radius:10px;padding:1px 7px;margin-left:6px;vertical-align:middle;">
                <?= $unreadCount ?>
            </span>
            <?php endif; ?>
        </button>
        <button class="subdiv" data-target="settings-section"><b>Settings</b></button>
    </div>
    <div class="sidebar-footer">
        <a href="index.php" style="text-decoration:none;">
            <button class="subdiv logout-btn"><b>Back To The Store</b></button>
        </a>
        <div class="footer-bottom-text"><p>© 2026 VELVET Admin</p><p>v1.0.2</p></div>
    </div>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h1 id="page-title">Dashboard</h1>
            <p style="color:gray;">Today is <?= date('l, j F Y') ?></p>
        </div>
        <div class="user-info">
            <div style="text-align:right;">
                <b style="display:block;"><?= htmlspecialchars($adminUser['full_name'] ?? 'Admin') ?></b>
                <span style="font-size:12px;color:green;">● Online</span>
            </div>
                <div class="profile-pic" id="header-profile-pic"></div>
        </div>
    </div>

    <!-- ══════════════════════ DASHBOARD ══════════════════════ -->
    <div id="dashboard-section" class="content-section active">
        <div class="dash-container">
            <div class="dash">
                <h3>Total Revenue</h3>
                <p>₪<?= number_format($totalRevenue, 0) ?></p>
            </div>
            <div class="dash">
                <h3>Total Orders</h3>
                <p><?= $totalOrders ?></p>
            </div>
            <div class="dash">
                <h3>Customers</h3>
                <p><?= $totalUsers ?></p>
            </div>
            <div class="dash">
                <h3>Low Stock (≤ <?= $threshold ?>)</h3>
                <p><?= $lowStockCount ?></p>
            </div>
        </div>

<!--        <h2 class="table-title">Order Status Breakdown</h2>-->
<!--        <div class="status-breakdown">-->
<!--            --><?php //foreach ($statusCounts as $s => $count): ?>
<!--                <div class="status-breakdown-box">-->
<!--                    <span class="status --><?php //= $s ?><!--">--><?php //= ucfirst($s) ?><!--</span>-->
<!--                    <p>--><?php //= $count ?><!--</p>-->
<!--                </div>-->
<!--            --><?php //endforeach; ?>
<!--        </div>-->

        <!-- ══════════ CHARTS ══════════ -->
        <h2 class="table-title" style="margin-top:10px;">Analytics Overview</h2>
        <div class="charts-grid">

            <!-- Revenue line chart -->
            <div class="chart-card chart-wide">
                <div class="chart-card-header">
                    <span class="chart-card-title"><i class="fas fa-chart-line"></i> Monthly Revenue</span>
                    <span class="chart-card-sub">Last 6 months · non-cancelled orders</span>
                </div>
                <div class="chart-body">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Daily orders bar -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <span class="chart-card-title"><i class="fas fa-calendar-check"></i> Daily Orders</span>
                    <span class="chart-card-sub">Last 7 days</span>
                </div>
                <div class="chart-body">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>

            <!-- Status doughnut -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <span class="chart-card-title"><i class="fas fa-chart-pie"></i> Order Status</span>
                    <span class="chart-card-sub">All time distribution</span>
                </div>
                <div class="chart-body chart-body-doughnut">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Top products bar -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <span class="chart-card-title"><i class="fas fa-star"></i> Top Products</span>
                    <span class="chart-card-sub">By units sold</span>
                </div>
                <div class="chart-body">
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>

            <!-- Category revenue doughnut -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <span class="chart-card-title"><i class="fas fa-tags"></i> Revenue by Category</span>
                    <span class="chart-card-sub">Men vs Women</span>
                </div>
                <div class="chart-body chart-body-doughnut">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

        </div>

        <!-- Pass PHP data to JS -->
        <script>
            window.VELVET_CHARTS = {
                revenue: {
                    labels:  <?= json_encode($monthlyLabels)  ?>,
                    data:    <?= json_encode($monthlyRevenue)  ?>
                },
                daily: {
                    labels:  <?= json_encode($dailyLabels)  ?>,
                    data:    <?= json_encode($dailyOrders)  ?>
                },
                status: {
                    labels:  <?= json_encode(array_map('ucfirst', array_keys($statusCounts))) ?>,
                    data:    <?= json_encode(array_values($statusCounts)) ?>
                },
                topProducts: {
                    labels:  <?= json_encode(array_column($topProducts, 'product_name')) ?>,
                    data:    <?= json_encode(array_map('intval', array_column($topProducts, 'total_sold'))) ?>
                },
                category: {
                    labels:  <?= json_encode(array_column($categoryRevenue, 'cat_name')) ?>,
                    data:    <?= json_encode(array_map('floatval', array_column($categoryRevenue, 'revenue'))) ?>
                }
            };
        </script>
        <!-- ══════════ END CHARTS ══════════ -->

        <h2 class="table-title">Recent Orders</h2>
        <div class="orders-table-container">
            <table class="orders-table">
                <thead>
                <tr><th>ID</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                <?php if (empty($allOrders)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#888;padding:20px;">No orders yet.</td></tr>
                <?php else: ?>
                    <?php foreach (array_slice($allOrders, 0, 8) as $o): ?>
                        <tr>
                            <td><b>#<?= $o['id'] ?></b></td>
                            <td><?= htmlspecialchars($o['full_name']) ?></td>
                            <td style="font-size:12px;color:#555;max-width:220px;"><?= htmlspecialchars($o['items']) ?></td>
                            <td>₪<?= number_format($o['total_amount'], 2) ?></td>
                            <td><span class="status <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td><?= date('Y-m-d', strtotime($o['ordered_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ══════════════════════ ORDERS ══════════════════════ -->
    <div id="orders-section" class="content-section">
        <div class="section-header-flex">
            <h2 class="table-title">Orders (<?= count($allOrders) ?>)</h2>
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="order-search" placeholder="Search by customer, status, ID...">
            </div>
        </div>
        <div class="orders-table-container">
            <table class="orders-table" id="orders-table">
                <thead>
                <tr><th>ID</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Payment</th><th>Date</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php if (empty($allOrders)): ?>
                    <tr><td colspan="8" style="text-align:center;color:#888;padding:20px;">No orders yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($allOrders as $o): ?>
                        <tr>
                            <td><b>#<?= $o['id'] ?></b></td>
                            <td><?= htmlspecialchars($o['full_name']) ?></td>
                            <td style="font-size:12px;color:#555;max-width:180px;"><?= htmlspecialchars($o['items']) ?></td>
                            <td>₪<?= number_format($o['total_amount'], 2) ?></td>
                            <td><span class="status <?= $o['status'] ?>" id="status-badge-<?= $o['id'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td>
                                <?= $o['payment_method'] ?>
                                <span class="status <?= $o['payment_status'] ?>" style="font-size:10px;padding:2px 6px;margin-left:3px;">
                <?= ucfirst($o['payment_status']) ?>
              </span>
                            </td>
                            <td><?= date('Y-m-d', strtotime($o['ordered_at'])) ?></td>
                            <td><button class="view-btn" onclick="viewOrder(<?= $o['id'] ?>)">View</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ══════════════════════ PRODUCTS ══════════════════════ -->
    <div id="products-section" class="content-section">
        <div class="section-header-flex">
            <h2 class="table-title">Inventory (<?= count($products) ?>)</h2>
            <div style="display:flex;gap:15px;">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="product-search" placeholder="Search products...">
                </div>
                <button class="add-product-btn" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> New Product
                </button>
            </div>
        </div>
        <div class="product-grid" id="admin-product-grid">
            <?php foreach ($products as $p):
                $data = [
                        'id'=>$p['id'],'name'=>$p['name'],'slug'=>$p['slug'],
                        'base_price'=>$p['base_price'],'sale_price'=>$p['sale_price'],
                        'description'=>$p['description'],'cat_slug'=>$p['cat_slug'],
                        'image_url'=>$p['image_url'],'total_stock'=>$p['total_stock'],
                        'is_sale'=>$p['is_sale'],'is_new'=>$p['is_new'],
                        'is_bestseller'=>$p['is_bestseller'],'is_active'=>$p['is_active'],
                ];
                $catLabel   = ($p['parent_name']??'') . ' / ' . ($p['cat_name']??'');
                $stockWarn  = (int)$p['total_stock'] <= $threshold;
                $stockColor = (int)$p['total_stock'] === 0 ? 'color:#e74c3c;font-weight:bold;'
                        : ($stockWarn ? 'color:#f59e0b;font-weight:bold;' : '');
                ?>
                <div class="admin-product-card"
                     onclick='openModal("edit",<?= json_encode($data, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                    <div class="card-img-container">
                        <img src="<?= htmlspecialchars($p['image_url'] ?? 'placeholder.jpg') ?>" alt="">
                        <div class="card-overlay"><i class="fas fa-edit"></i> Edit Details</div>
                    </div>
                    <div class="card-info">
                        <h4><?= htmlspecialchars($p['name']) ?></h4>
                        <p class="card-category"><?= htmlspecialchars($catLabel) ?></p>
                        <div class="card-meta">
                            <span class="card-price">₪<?= number_format($p['base_price'],2) ?></span>
                            <span class="card-stock" style="<?= $stockColor ?>">
              Stock: <?= (int)$p['total_stock'] ?>
                                <?= (int)$p['total_stock'] === 0 ? ' ⚠' : '' ?>
            </span>
                        </div>
                        <?php if (!$p['is_active']): ?>
                            <span style="font-size:10px;background:#f8d7da;color:#721c24;
                       padding:2px 7px;border-radius:4px;margin-top:4px;display:inline-block;">
            Hidden
          </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ══════════════════════ PRODUCT MODAL ══════════════════════ -->
    <div id="product-modal" class="modal-overlay">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h2 id="modal-title">Product Management</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form id="product-form" class="modal-body" onsubmit="handleProductSubmit(event)" enctype="multipart/form-data">
                <div class="modal-grid-layout">
                    <div class="modal-col-left">
                        <label class="form-label">Product Images</label>
                        <div class="gallery-container">
                            <div class="main-product-view" id="main-view">
                                <button type="button" class="nav-arrow left" onclick="changeSlide(-1)">&#10094;</button>
                                <div id="active-image-container" class="active-img-wrapper">
                                    <img src="placeholder.jpg" id="current-display-img" alt="Preview">
                                </div>
                                <button type="button" class="nav-arrow right" onclick="changeSlide(1)">&#10095;</button>
                            </div>
                            <div id="image-album" class="album-grid-horizontal">
                                <div class="add-image-slot-small" onclick="document.getElementById('album-upload').click()">
                                    <i class="fas fa-plus"></i>
                                    <input type="file" id="album-upload" hidden multiple accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-col-right">
                        <div class="input-row">
                            <div class="input-group">
                                <label for="p-name">Product Name</label>
                                <input type="text" id="p-name" required>
                            </div>
                            <div class="input-group">
                                <label for="p-class">Classification</label>
                                <select id="p-class">
                                    <option value="top">Top</option>
                                    <option value="bottom">Bottom</option>
                                    <option value="one-piece">One-Piece</option>
                                </select>
                            </div>
                        </div>
                        <div class="input-row">
                            <div class="input-group">
                                <label for="p-gender">Target Audience</label>
                                <select id="p-gender">
                                    <option value="men">Men</option>
                                    <option value="women">Women</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label for="p-price">Price (₪)</label>
                                <input type="number" id="p-price" step="0.5" min="0" required>
                            </div>
                        </div>
                        <div class="input-row">
                            <div class="input-group">
                                <label for="p-sale">Sale Price (₪) <small style="color:gray;">Optional</small></label>
                                <input type="number" id="p-sale" step="0.5" min="0" style="color:#ff4757;font-weight:bold;">
                            </div>
                        </div>
                        <div class="input-row" style="gap:10px;align-items:center;margin-bottom:10px;">
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                                <input type="checkbox" id="p-is-new"> New Arrival
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                                <input type="checkbox" id="p-is-bestseller"> Best Seller
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                                <input type="checkbox" id="p-is-active" checked> Active (visible)
                            </label>
                        </div>
                        <label class="form-label">Variant Inventory</label>
                        <div class="variant-manager-container">
                            <div class="variant-controls">
                                <select id="var-size">
                                    <option value="XS">XS</option><option value="S">S</option>
                                    <option value="M">M</option><option value="L">L</option>
                                    <option value="XL">XL</option><option value="XXL">XXL</option>
                                </select>
                                <!-- FIX: Two independent inputs — color name (text) + hex (picker) -->
                                <input type="text" id="var-color" placeholder="Color name e.g. Pink" style="flex:2;">
                                <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;" title="Enter the color in HEX format">
                                    <input type="color" id="color-picker" value="#000000"
                                           style="width:38px;height:38px;border:1px solid #ddd;border-radius:4px;cursor:pointer;padding:2px;background:#fff;">
                                    <span style="font-size:10px;color:#aaa;white-space:nowrap;">Hex<br></span>
                                </div>

                                <input type="number" id="var-qty" placeholder="Qty" min="0" value="0">
                                <button type="button" class="add-variant-btn" onclick="addVariantRow()">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                            <div class="variant-table-wrapper">
                                <table class="variant-table" id="variant-table">
                                    <thead><tr><th>Size</th><th>Color</th><th>Qty</th><th></th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <label class="form-label">Product Description</label>
                        <textarea id="p-desc" class="form-textarea" placeholder="Describe the product…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="delete-btn" id="delete-btn" style="display:none;">Delete Product</button>
                    <button type="submit" class="save-btn" id="save-btn">Save to Collection</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════ ORDER VIEW MODAL ══════════════════════ -->
    <div id="order-modal-overlay">
        <div id="order-modal-box">
            <button class="close-order-modal" onclick="closeOrderModal()">&times;</button>
            <h3 id="order-modal-title">Order Details</h3>
            <div id="order-modal-body">
                <p style="text-align:center;padding:30px;color:#888;">Loading…</p>
            </div>
        </div>
    </div>

    <!-- ══════════════════════ CUSTOMERS ══════════════════════ -->
    <div id="customers-section" class="content-section">
        <div class="section-header-flex">
            <h2 class="table-title">Customers (<?= count($customers) ?>)</h2>
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="customer-search" placeholder="Search by name or email...">
            </div>
        </div>
        <div class="orders-table-container">
            <table class="orders-table" id="customers-table">
                <thead>
                <tr><th>Customer</th><th>Contact</th><th>Orders</th><th>Total Spent</th><th>Status</th><th>Joined</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td style="display:flex;align-items:center;gap:10px; padding-bottom: 30px;" >
                            <div style="width:35px;height:35px;background:var(--accent);border-radius:50%;
                          display:flex;align-items:center;justify-content:center;
                          color:white;font-weight:bold;font-size:14px;">
                                <?= strtoupper(substr($c['full_name'],0,1)) ?>
                            </div>
                            <b><?= htmlspecialchars($c['full_name']) ?></b>
                        </td>
                        <td>
                            <?= htmlspecialchars($c['email']) ?><br>
                            <small style="color:gray;"><?= htmlspecialchars($c['phone']??'') ?></small>
                        </td>
                        <td><?= $c['order_count'] ?></td>
                        <td>₪<?= number_format($c['total_spent'],2) ?></td>
                        <td>
              <span class="status <?= $c['is_active']?'delivered':'cancelled' ?>"
                    id="customer-status-<?= $c['id'] ?>">
                <?= $c['is_active']?'Active':'Inactive' ?>
              </span>
                        </td>
                        <td><?= date('Y-m-d', strtotime($c['created_at'])) ?></td>
                        <td>
                            <button class="view-btn"
                                    onclick="toggleCustomer(<?= $c['id'] ?>,<?= $c['is_active'] ?>)">
                                <?= $c['is_active']?'Deactivate':'Activate' ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ══════════════════════ SETTINGS ══════════════════════ -->
    <div id="settings-section" class="content-section">
        <h2 class="table-title">Settings</h2>
        <div class="settings-wrapper">

            <div class="settings-card">
                <div class="card-header"><i class="fas fa-store"></i><h3>Store Identity & Socials</h3></div>
                <div class="settings-body">
                    <div class="input-group"><label>Store Name</label>
                        <input type="text" id="set-store-name" value="<?= htmlspecialchars($settings['store_name']) ?>">
                    </div>
                    <div class="input-group"><label>Phone & Email</label>
                        <div style="display:flex;gap:10px;">
                            <input type="text"  id="set-phone" style="flex:1;" value="<?= htmlspecialchars($settings['support_phone']) ?>">
                            <input type="email" id="set-email" style="flex:1;" value="<?= htmlspecialchars($settings['support_email']) ?>">
                        </div>
                    </div>
                    <div class="input-group">
                        <label><i class="fab fa-instagram" style="color:#E1306C;"></i> Instagram</label>
                        <input type="text" id="set-insta" value="<?= htmlspecialchars($settings['instagram_url']) ?>">
                    </div>
                    <div class="input-group">
                        <label><i class="fab fa-facebook" style="color:#1877F2;"></i> Facebook</label>
                        <input type="text" id="set-fb" value="<?= htmlspecialchars($settings['facebook_url']) ?>">
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="card-header"><i class="fas fa-user-shield"></i><h3>Admin Profile</h3></div>
                <div class="settings-body">
                    <div class="profile-upload-area">
                        <div class="current-avatar" id="settings-avatar-preview"
                             style="<?= !empty($adminUser['avatar_url']) ? "background-image:url('" . htmlspecialchars($adminUser['avatar_url']) . "');background-size:cover;background-position:center;" : "" ?>">
                            <?php if (empty($adminUser['avatar_url'])): ?>
                            <?php endif; ?>
                        </div>
                        <div class="upload-controls">
                            <label for="avatar-upload" class="action-btn-outline"
                                   style="cursor:pointer;margin-bottom:5px;display:block;text-align:center;font-size:12px;">
                                <i class="fas fa-camera"></i> Change
                            </label>
                            <input type="file" id="avatar-upload" hidden accept="image/*" onchange="previewAvatar(this)">
                            <button class="remove-link" onclick="removeProfilePic()">Remove Picture</button>
                        </div>
                    </div>
                    <div class="input-group" style="margin-top:15px;"><label>Display Name</label>
                        <input type="text" id="set-admin-name" value="<?= htmlspecialchars($adminUser['full_name'] ?? 'Admin') ?>">
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;margin-top:5px;">
                        <button class="action-btn-outline" onclick="openEmailModal()">
                            <i class="fas fa-envelope"></i> Update Admin Email
                        </button>
                        <button class="action-btn-outline" onclick="openPassModal()">
                            <i class="fas fa-lock"></i> Update Password
                        </button>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="card-header"><i class="fas fa-truck"></i><h3>Store Limits & Shipping</h3></div>
                <div class="settings-body">
                    <div class="input-group"><label>Low Stock Alert Threshold</label>
                        <input type="number" id="set-low-stock" value="<?= $settings['low_stock_threshold'] ?>">
                        <p class="help-text">Products with stock ≤ this number appear as alerts.</p>
                    </div>
                    <div class="input-group"><label>Flat Shipping Rate (₪)</label>
                        <input type="number" id="set-shipping-fee" value="<?= $settings['shipping_fee'] ?>">
                    </div>
                    <div class="input-group"><label>Free Shipping Above (₪)</label>
                        <input type="number" id="set-free-ship" value="<?= $settings['free_shipping_above'] ?>">
                    </div>
                    <div class="setting-toggle">
                        <div class="toggle-info"><span>Cash on Delivery</span></div>
                        <label class="switch">
                            <input type="checkbox" id="set-cod" <?= $settings['cod_enabled'] ? 'checked' : '' ?>>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="card-header"><i class="fas fa-paint-brush"></i><h3>Appearance & Status</h3></div>
                <div class="settings-body">
                    <div class="setting-toggle theme-row">
                        <div class="toggle-label-group"><i class="fas fa-moon"></i><span>Dark Mode</span></div>
                        <label class="switch">
                            <input type="checkbox" id="theme-toggle" onchange="toggleDarkMode()">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="setting-toggle" style="margin-top:10px;">
                        <div class="toggle-info">
                            <span>Maintenance Mode</span>
                            <p class="help-text">Visitors see a maintenance page instead of the shop.</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="set-maintenance" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <div class="input-group" style="margin-top:15px;"><label>Theme Color</label>
                        <p class="help-text" style="margin-bottom:10px;">
                            Changes all elements across the dashboard (buttons, highlights, cards).
                        </p>
                        <div class="swatch-container">
                            <?php
                            $accents = [
                                    '#3498db' => 'Blue',
                                    '#ffcc00' => 'Gold',
                                    '#e74c3c' => 'Red',
                                    '#27ae60' => 'Green',
                                    '#2c3e50' => 'Dark',
                                    '#9b59b6' => 'Purple',
                                    '#e60073' => 'Pink',
                            ];
                            foreach ($accents as $col => $label):
                                ?>
                                <label class="swatch-item" title="<?= $label ?>">
                                    <input type="radio" name="accent" value="<?= $col ?>"
                                            <?= ($settings['accent_color'] ?? '#3498db') === $col ? 'checked' : '' ?>
                                           onchange="setAccent(this.value)">
                                    <span class="swatch-circle" style="background-color:<?= $col ?>;"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="settings-footer">
            <button class="save-settings-btn" onclick="saveAllSettings()">
                <i class="fas fa-save"></i> Save All Changes
            </button>
        </div>
    </div>

</div><!-- /.main-content -->
<!-- ══════════════════════ MESSAGES ══════════════════════ -->
<div id="messages-section" class="content-section">
    <div class="section-header-flex">
        <div class="search-container" style="margin-left:15px;">
            <i class="fas fa-search"></i>
            <input type="text" id="msg-search" placeholder="Search messages...">
        </div>
        <h2 class="table-title">
            <?php if ($unreadCount > 0): ?>
                <span style="font-size:13px;font-weight:400;color:#e74c3c; margin-right:40px;">
                    (<?= $unreadCount ?> unread)
                </span>
            <?php endif; ?>
        </h2>
    </div>

    <?php if (empty($contactMessages)): ?>
        <div style="text-align:center;padding:60px 20px;color:#aaa;">
            <i class="fas fa-inbox" style="font-size:3rem;opacity:.3;display:block;margin-bottom:16px;"></i>
            No messages yet.
        </div>
    <?php else: ?>
        <div class="messages-grid" id="messages-grid">
            <?php foreach ($contactMessages as $msg): ?>
                <div class="msg-card <?= $msg['is_read'] ? 'msg-read' : 'msg-unread' ?>"
                     id="msg-card-<?= $msg['id'] ?>"
                     data-name="<?= htmlspecialchars(strtolower($msg['name'])) ?>"
                     data-email="<?= htmlspecialchars(strtolower($msg['email'])) ?>"
                     data-message="<?= htmlspecialchars(strtolower($msg['message'])) ?>">

                    <!-- Header row -->
                    <div class="msg-header">
                        <div class="msg-avatar"><?= strtoupper(substr($msg['name'], 0, 1)) ?></div>
                        <div class="msg-meta">
                            <div class="msg-name">
                                <?= htmlspecialchars($msg['name']) ?>
                                <?php if (!$msg['is_read']): ?>
                                    <span class="msg-new-dot"></span>
                                <?php endif; ?>
                            </div>
                            <div class="msg-email">
                                <a href="mailto:<?= htmlspecialchars($msg['email']) ?>"
                                   style="color:var(--accent);text-decoration:none;">
                                    <?= htmlspecialchars($msg['email']) ?>
                                </a>
                            </div>
                        </div>
                        <div class="msg-time">
                            <i class="fas fa-clock" style="font-size:10px;margin-right:3px;"></i>
                            <?= date('M j, Y', strtotime($msg['sent_at'])) ?>
                            <br><span style="font-size:10px;color:#aaa;"><?= date('H:i', strtotime($msg['sent_at'])) ?></span>
                        </div>
                    </div>

                    <!-- Message body -->
                    <div class="msg-body">
                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    </div>

                    <!-- Actions -->
                    <div class="msg-actions">
                        <button class="msg-btn msg-btn-reply"
                                onclick="openReplyModal(<?= $msg['id'] ?>, '<?= htmlspecialchars(addslashes($msg['name'])) ?>', '<?= htmlspecialchars(addslashes($msg['email'])) ?>', '<?= htmlspecialchars(addslashes($msg['message'])) ?>')">
                            <i class="fas fa-reply"></i> Reply </button>
                        <?php if (!$msg['is_read']): ?>
                            <button class="msg-btn msg-btn-read"
                                    onclick="markRead(<?= $msg['id'] ?>, this)">
                                <i class="fas fa-check"></i> Mark as Read
                            </button>
                        <?php else: ?>
                            <span class="msg-read-badge"><i class="fas fa-check-double"></i> Read</span>
                        <?php endif; ?>
                        <button class="msg-btn msg-btn-delete"
                                onclick="deleteMessage(<?= $msg['id'] ?>, this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ══════════ REPLY MODAL ══════════ -->
<div id="reply-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:32px;width:520px;max-width:95vw;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <button onclick="closeReplyModal()" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:22px;cursor:pointer;color:#aaa;">&times;</button>
        <h3 style="margin:0 0 4px;font-size:17px;"><i class="fas fa-reply" style="color:var(--accent);margin-right:8px;"></i>Reply to Message</h3>
        <p style="color:#888;font-size:13px;margin-bottom:16px;">
            To: <strong id="reply-to-name"></strong>
            &lt;<span id="reply-to-email" style="color:var(--accent);"></span>&gt;
        </p>

        <!-- Original message -->
        <div style="background:#f8f8f8;border-left:3px solid #ddd;padding:10px 14px;border-radius:4px;margin-bottom:16px;font-size:13px;color:#666;line-height:1.6;max-height:90px;overflow-y:auto;">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#aaa;margin-bottom:4px;">Original message</div>
            <div id="reply-original-msg"></div>
        </div>

        <div id="reply-modal-error" style="display:none;color:#e74c3c;font-size:13px;background:#fff0f0;border-radius:6px;padding:8px 12px;margin-bottom:12px;"></div>
        <div id="reply-modal-success" style="display:none;color:#27ae60;font-size:13px;background:#f0fff4;border-radius:6px;padding:8px 12px;margin-bottom:12px;"></div>

        <label style="font-size:12px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.05em;">Your Reply</label>
        <textarea id="reply-text-input" rows="5"
                  placeholder="Type your reply here..."
                  style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;
                             margin:6px 0 16px;box-sizing:border-box;resize:vertical;line-height:1.6;"></textarea>

        <div style="display:flex;gap:10px;">
            <button onclick="submitReply()" id="reply-send-btn"
                    style="flex:1;padding:11px;background:var(--accent);color:#fff;border:none;
                               border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;">
                <i class="fas fa-paper-plane me-1"></i> Send Reply
            </button>
            <button onclick="closeReplyModal()"
                    style="padding:11px 20px;background:#f0f0f0;color:#555;border:none;
                               border-radius:8px;font-size:14px;cursor:pointer;">
                Cancel
            </button>
        </div>
    </div>
</div>




<!-- ══════════ EMAIL UPDATE MODAL ══════════ -->
<div id="email-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:32px;width:420px;max-width:95vw;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <button onclick="closeEmailModal()" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:22px;cursor:pointer;color:#aaa;">&times;</button>
        <h3 style="margin:0 0 6px;font-size:17px;"><i class="fas fa-envelope" style="color:var(--accent);margin-right:8px;"></i>Update Admin Email</h3>
        <p style="color:#888;font-size:13px;margin-bottom:20px;">Current: <strong id="current-email-display"><?= htmlspecialchars($adminUser['email'] ?? '') ?></strong></p>
        <div id="email-modal-error" style="display:none;color:#e74c3c;font-size:13px;background:#fff0f0;border-radius:6px;padding:8px 12px;margin-bottom:14px;"></div>
        <div id="email-modal-success" style="display:none;color:#27ae60;font-size:13px;background:#f0fff4;border-radius:6px;padding:8px 12px;margin-bottom:14px;"></div>
        <label style="font-size:12px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.05em;">New Email</label>
        <input type="email" id="new-email-input" placeholder="new@email.com"
               style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;margin:6px 0 14px;box-sizing:border-box;">
        <label style="font-size:12px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.05em;">Current Password (to confirm)</label>
        <input type="password" id="email-confirm-password" placeholder="Enter your password"
               style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;margin:6px 0 20px;box-sizing:border-box;">
        <button onclick="submitEmailUpdate()"
                style="width:100%;padding:11px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;">
            <i class="fas fa-save"></i> Save New Email
        </button>
    </div>
</div>

<!-- ══════════ PASSWORD UPDATE MODAL ══════════ -->
<div id="pass-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:32px;width:420px;max-width:95vw;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <button onclick="closePassModal()" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:22px;cursor:pointer;color:#aaa;">&times;</button>
        <h3 style="margin:0 0 20px;font-size:17px;"><i class="fas fa-lock" style="color:var(--accent);margin-right:8px;"></i>Update Password</h3>
        <div id="pass-modal-error" style="display:none;color:#e74c3c;font-size:13px;background:#fff0f0;border-radius:6px;padding:8px 12px;margin-bottom:14px;"></div>
        <div id="pass-modal-success" style="display:none;color:#27ae60;font-size:13px;background:#f0fff4;border-radius:6px;padding:8px 12px;margin-bottom:14px;"></div>
        <label style="font-size:12px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.05em;">Current Password</label>
        <input type="password" id="current-pass-input" placeholder="Your current password"
               style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;margin:6px 0 14px;box-sizing:border-box;">
        <label style="font-size:12px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.05em;">New Password</label>
        <input type="password" id="new-pass-input" placeholder="At least 6 characters"
               style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;margin:6px 0 14px;box-sizing:border-box;">
        <label style="font-size:12px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.05em;">Confirm New Password</label>
        <input type="password" id="confirm-pass-input" placeholder="Repeat new password"
               style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;margin:6px 0 20px;box-sizing:border-box;">
        <button onclick="submitPassUpdate()"
                style="width:100%;padding:11px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;">
            <i class="fas fa-shield-halved"></i> Update Password
        </button>
    </div>
</div>

<script src="Manager.js"></script>
</body>
</html>