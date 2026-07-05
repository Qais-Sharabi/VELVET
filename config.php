<?php
// config.php — legacy shim, delegates to dbconnect.php
require_once __DIR__ . '/dbconnect.php';
// Provide $conn for any old mysqli code still referencing it
if (!isset($conn)) {
    $conn = new mysqli('localhost', 'root', '', 'velvet_shop');
    $conn->set_charset('utf8mb4');
}
