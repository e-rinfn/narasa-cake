<?php
require_once __DIR__ . '/../config/config.php';

// Fungsi untuk memeriksa login
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Fungsi untuk memeriksa level akses
function checkAccess($requiredLevel)
{
    if (!isLoggedIn() || $_SESSION['level'] != $requiredLevel) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

// Fungsi login
function login($db, $username, $password)
{
    $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id_admin'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama'] = $user['nama_lengkap'];
        $_SESSION['level'] = $user['level'];
        return true;
    }
    return false;
}

// Fungsi logout
function logout()
{
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "login.php");
    exit();
}
