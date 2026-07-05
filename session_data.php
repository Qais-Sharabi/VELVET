<?php
/**
 * session_data.php
 * Include this in every page that uses modals.js, inside a <script> tag area.
 * It outputs a small JS object with session state for the profile modal.
 */
if (!defined('SESSION_DATA_INCLUDED')) {
    define('SESSION_DATA_INCLUDED', true);
    $sd_logged = isset($_SESSION['user_id']);
    $sd_name   = $sd_logged ? htmlspecialchars($_SESSION['user_name']   ?? '') : '';
    $sd_letter = $sd_logged ? htmlspecialchars($_SESSION['user_letter'] ?? '') : '';
    $sd_admin  = $sd_logged && ($_SESSION['role'] ?? '') === 'admin';
    echo "<script>\n";
    echo "window.VELVET_SESSION = " . json_encode([
        'loggedIn' => $sd_logged,
        'name'     => $sd_name,
        'letter'   => $sd_letter,
        'isAdmin'  => $sd_admin,
    ]) . ";\n";
    echo "</script>\n";
}
