<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metode request tidak valid');
    }

    $id_stok_kue = (int)$_POST['id_stok_kue'];
    $jumlah = (int)$_POST['jumlah'];
    $tanggal_produksi = $_POST['tanggal_produksi'];
    $tanggal_kadaluarsa = $_POST['tanggal_kadaluarsa'];

    // Validasi data
    if ($jumlah < 0) {
        throw new Exception('Jumlah tidak valid');
    }

    $stmt = $db->prepare("UPDATE stok_kue 
                         SET jumlah = ?, tanggal_produksi = ?, tanggal_kadaluarsa = ?
                         WHERE id_stok_kue = ?");
    $stmt->execute([$jumlah, $tanggal_produksi, $tanggal_kadaluarsa, $id_stok_kue]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
