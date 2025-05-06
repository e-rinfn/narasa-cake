<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Ambil ID dari URL
$id_bahan = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Cek apakah bahan digunakan dalam resep
$stmt = $db->prepare("SELECT COUNT(*) FROM detail_resep WHERE id_bahan = ?");
$stmt->execute([$id_bahan]);
$used_in_recipe = $stmt->fetchColumn();

if ($used_in_recipe > 0) {
    redirectWithMessage('index.php', 'danger', 'Bahan tidak dapat dihapus karena digunakan dalam resep');
}

// Cek apakah ada stok tersisa
$stmt = $db->prepare("SELECT SUM(jumlah) FROM stok_bahan WHERE id_bahan = ?");
$stmt->execute([$id_bahan]);
$remaining_stock = $stmt->fetchColumn();

if ($remaining_stock > 0) {
    redirectWithMessage('index.php', 'danger', 'Bahan tidak dapat dihapus karena masih memiliki stok');
}

try {
    // Hapus bahan dari database
    $stmt = $db->prepare("DELETE FROM bahan_baku WHERE id_bahan = ?");
    $stmt->execute([$id_bahan]);

    redirectWithMessage('index.php', 'success', 'Bahan baku berhasil dihapus');
} catch (PDOException $e) {
    redirectWithMessage('index.php', 'danger', 'Error: ' . $e->getMessage());
}
