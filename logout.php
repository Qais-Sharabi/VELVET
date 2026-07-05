<?php
include 'dbconnect.php';

session_destroy();

// Clear remember-me cookie
setcookie('remember_user', '', time() - 3600, '/', '', false, true);

header('Location: login.php');
exit;
