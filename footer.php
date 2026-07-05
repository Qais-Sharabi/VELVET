<?php
// footer.php
/** @var PDO $pdo */
// 1. Fetch Store Settings from DB
$settings_stmt = $pdo->prepare("SELECT * FROM store_settings LIMIT 1");
$settings_stmt->execute();
$settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);

// Fallback if DB row is missing
if (!$settings) {
    $settings = [
        'store_name' => 'VELVET',
        'support_email' => 'info@velvet.com',
        'support_phone' => '059xxxxxxx',
        'facebook_url' => '#',
        'instagram_url' => '#'
    ];
}
?>

<script src="loadfooter.js"></script>

<script>
    (function() {
        const storeSettings = <?php echo json_encode($settings); ?>;
        // Check if the function exists before calling to avoid errors
        if (typeof loadFooter === 'function') {
            loadFooter(storeSettings);
        }
    })();
</script>
