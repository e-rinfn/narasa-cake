<?php
require_once 'config/config.php';

// Hancurkan semua data session
$_SESSION = array();

// Jika ingin menghancurkan session sepenuhnya, hapus juga session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Akhirnya, hancurkan session
session_destroy();

// Redirect ke halaman login
header("Location: login.php");
exit();
