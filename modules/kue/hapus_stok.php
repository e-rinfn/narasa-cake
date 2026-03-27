<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Ambil ID dari URL
$id_stok = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_jenis_kue = isset($_GET['id_jenis_kue']) ? intval($_GET['id_jenis_kue']) : 0;

if (!$id_stok || !$id_jenis_kue) {
    redirectWithMessage('index.php', 'danger', 'Parameter tidak valid');
}

try {
    // Cek apakah stok ada
    $stmt = $db->prepare("SELECT * FROM stok_kue WHERE id_stok_kue = ?");
    $stmt->execute([$id_stok]);
    $stok = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stok) {
        redirectWithMessage("stok.php?id=$id_jenis_kue", 'danger', 'Data stok tidak ditemukan');
    }

    // Hapus stok kue
    $stmt = $db->prepare("DELETE FROM stok_kue WHERE id_stok_kue = ?");
    $stmt->execute([$id_stok]);

    redirectWithMessage("stok.php?id=$id_jenis_kue", 'success', 'Produksi berhasil dibatalkan');
} catch (PDOException $e) {
    redirectWithMessage("stok.php?id=$id_jenis_kue", 'danger', 'Error: ' . $e->getMessage());
}
