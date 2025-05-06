<?php
require_once '../../../config/config.php';
require_once '../../../includes/functions.php';
checkAuth();

$id_resep = $_GET['id'] ?? 0;

// Cek apakah resep digunakan di produksi
$stmt = $db->prepare("SELECT COUNT(*) FROM produksi WHERE id_resep = ?");
$stmt->execute([$id_resep]);
$used = $stmt->fetchColumn();

if ($used > 0) {
    redirectWithMessage('../resep/index.php', 'danger', 'Resep tidak dapat dihapus karena sudah digunakan dalam produksi');
}

try {
    $db->beginTransaction();

    // Hapus detail resep terlebih dahulu
    $stmt = $db->prepare("DELETE FROM detail_resep WHERE id_resep = ?");
    $stmt->execute([$id_resep]);

    // Hapus resep
    $stmt = $db->prepare("DELETE FROM resep_kue WHERE id_resep = ?");
    $stmt->execute([$id_resep]);

    $db->commit();
    redirectWithMessage('../resep/index.php', 'success', 'Resep berhasil dihapus');
} catch (PDOException $e) {
    $db->rollBack();
    redirectWithMessage('../resep/index.php', 'danger', 'Error: ' . $e->getMessage());
}
