<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);
    exit;
}

$id_produksi = $_POST['id_produksi'] ?? null;
$total_kue = $_POST['total_kue'] ?? null;

if (!$id_produksi || !$total_kue) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE produksi SET total_kue = ? WHERE id_produksi = ?");
    $stmt->execute([$total_kue, $id_produksi]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
