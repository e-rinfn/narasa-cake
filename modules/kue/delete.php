<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

if (!isset($_GET['id'])) {
    redirectWithMessage('index.php', 'danger', 'ID jenis kue tidak valid');
}

$id_jenis_kue = $_GET['id'];

try {
    // Cek apakah ada resep terkait
    $stmt = $db->prepare("SELECT COUNT(*) FROM resep_kue WHERE id_jenis_kue = ?");
    $stmt->execute([$id_jenis_kue]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        redirectWithMessage('index.php', 'danger', 'Tidak bisa menghapus karena ada resep terkait');
    }

    // Cek apakah ada stok kue
    $stmt = $db->prepare("SELECT COUNT(*) FROM stok_kue WHERE id_jenis_kue = ?");
    $stmt->execute([$id_jenis_kue]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        redirectWithMessage('index.php', 'danger', 'Tidak bisa menghapus karena ada stok kue terkait');
    }

    // Hapus jenis kue
    $stmt = $db->prepare("DELETE FROM jenis_kue WHERE id_jenis_kue = ?");
    $stmt->execute([$id_jenis_kue]);

    redirectWithMessage('index.php', 'success', 'Jenis kue berhasil dihapus');
} catch (PDOException $e) {
    redirectWithMessage('index.php', 'danger', 'Error: ' . $e->getMessage());
}
