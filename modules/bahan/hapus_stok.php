<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Ambil ID dari URL
$id_stok = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_bahan = isset($_GET['id_bahan']) ? intval($_GET['id_bahan']) : 0;

if (!$id_stok || !$id_bahan) {
    redirectWithMessage('index.php', 'danger', 'Parameter tidak valid');
}

try {
    // Cek apakah stok sudah digunakan (jumlah_masuk != jumlah berarti sudah ada yang keluar)
    $stmt = $db->prepare("SELECT * FROM stok_bahan WHERE id_stok = ?");
    $stmt->execute([$id_stok]);
    $stok = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stok) {
        redirectWithMessage("stok.php?id=$id_bahan", 'danger', 'Data stok tidak ditemukan');
    }

    // Cek apakah stok sudah digunakan
    $jumlah_keluar = $stok['jumlah_masuk'] - $stok['jumlah'];
    if ($jumlah_keluar > 0) {
        redirectWithMessage("stok.php?id=$id_bahan", 'danger', 'Stok tidak dapat dibatalkan karena sudah digunakan sebanyak ' . $jumlah_keluar);
    }

    // Hapus stok
    $stmt = $db->prepare("DELETE FROM stok_bahan WHERE id_stok = ?");
    $stmt->execute([$id_stok]);

    redirectWithMessage("stok.php?id=$id_bahan", 'success', 'Stok berhasil dibatalkan');
} catch (PDOException $e) {
    redirectWithMessage("stok.php?id=$id_bahan", 'danger', 'Error: ' . $e->getMessage());
}
