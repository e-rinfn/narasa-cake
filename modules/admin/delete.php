<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Hanya superadmin yang bisa mengakses
if ($_SESSION['level'] != 'superadmin') {
    redirectWithMessage('../../index.php', 'danger', 'Anda tidak memiliki akses ke halaman ini');
}

$id_admin = $_GET['id'] ?? 0;

// Cek apakah admin mencoba menghapus dirinya sendiri
if ($id_admin == $_SESSION['user_id']) {
    redirectWithMessage('../index.php', 'danger', 'Anda tidak dapat menghapus akun sendiri');
}

try {
    // Cek apakah admin ada
    $stmt = $db->prepare("SELECT id_admin FROM admin WHERE id_admin = ?");
    $stmt->execute([$id_admin]);

    if ($stmt->rowCount() == 0) {
        redirectWithMessage('index.php', 'danger', 'Admin tidak ditemukan');
    }

    // Hapus admin
    $stmt = $db->prepare("DELETE FROM admin WHERE id_admin = ?");
    $stmt->execute([$id_admin]);

    redirectWithMessage('index.php', 'success', 'Admin berhasil dihapus');
} catch (PDOException $e) {
    redirectWithMessage('index.php', 'danger', 'Error: ' . $e->getMessage());
}
