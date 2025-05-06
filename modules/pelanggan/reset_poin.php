<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$id = $_GET['id'] ?? null;

if (!$id) {
    redirectWithMessage('index.php', 'error', 'ID pelanggan tidak valid.');
}

// Reset poin
$stmt = $db->prepare("UPDATE pelanggan SET total_poin = 0 WHERE id_pelanggan = ?");
$stmt->execute([$id]);

redirectWithMessage('index.php', 'success', 'Total poin berhasil direset ke 0.');
