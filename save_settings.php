<?php
// ============================================================
//  save_settings.php
//  Called by Manager.js → saveAllSettings()
//  Updates the single row in store_settings (id = 1)
// ============================================================
header('Content-Type: text/plain');
include 'dbconnect.php';
/** @var PDO $pdo */

try {
    $pdo->prepare("
        UPDATE store_settings SET
            store_name           = ?,
            support_phone        = ?,
            support_email        = ?,
            instagram_url        = ?,
            facebook_url         = ?,
            low_stock_threshold  = ?,
            shipping_fee         = ?,
            free_shipping_above  = ?,
            cod_enabled          = ?,
            maintenance_mode     = ?,
            accent_color         = ?
        WHERE id = 1
    ")->execute([
        trim($_POST['store_name']            ?? 'VELVET'),
        trim($_POST['support_phone']         ?? ''),
        trim($_POST['support_email']         ?? ''),
        trim($_POST['instagram_url']         ?? ''),
        trim($_POST['facebook_url']          ?? ''),
        (int)($_POST['low_stock_threshold']  ?? 5),
        (float)($_POST['shipping_fee']       ?? 20),
        (float)($_POST['free_shipping_above']?? 300),
        (int)($_POST['cod_enabled']          ?? 1),
        (int)($_POST['maintenance_mode']     ?? 0),
        trim($_POST['accent_color']          ?? '#3498db'),
    ]);
    echo 'SUCCESS';
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
