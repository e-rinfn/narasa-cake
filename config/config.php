<?php
session_start();

$host = 'localhost';
$dbname = 'sistem_kue';
$username = 'root';
$password = '';

// buat koneksi mysqli
$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Base URL
define('BASE_URL', 'narasa-cake');

// Default Timezone
date_default_timezone_set('Asia/Jakarta');

// Check Authentication
function checkAuth()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

// Get User Data
function getUserData($db, $id)
{
    $stmt = $db->prepare("SELECT * FROM admin WHERE id_admin = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
