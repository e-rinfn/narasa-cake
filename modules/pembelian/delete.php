<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Hanya admin dan superadmin yang bisa menghapus pembelian
if ($_SESSION['level'] != 'superadmin' && $_SESSION['level'] != 'admin') {
    redirectWithMessage('../index.php', 'danger', 'Anda tidak memiliki akses untuk menghapus pembelian.');
}

// Pastikan ID pembelian ada
if (!isset($_GET['id'])) {
    redirectWithMessage('../index.php', 'danger', 'ID Pembelian tidak valid.');
}

$id_pembelian = $_GET['id'];

try {
    $db->beginTransaction();

    // 1. Ambil detail pembelian untuk mengembalikan stok
    $stmt = $db->prepare("SELECT * FROM detail_pembelian WHERE id_pembelian = ?");
    $stmt->execute([$id_pembelian]);
    $detail_pembelian = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Kembalikan stok bahan yang terkait dengan pembelian ini
    foreach ($detail_pembelian as $item) {
        // Cek apakah stok dengan batch yang sama sudah ada
        $stmt = $db->prepare("SELECT * FROM stok_bahan 
                            WHERE id_bahan = ? AND batch_number = ? 
                            AND tanggal_masuk = (SELECT tanggal_pembelian FROM pembelian_bahan WHERE id_pembelian = ?)");
        $stmt->execute([$item['id_bahan'], $item['batch_number'] ?? null, $id_pembelian]);
        $stok = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($stok) {
            // Update stok yang sudah ada
            $stmt = $db->prepare("UPDATE stok_bahan SET jumlah = jumlah - ? 
                                WHERE id_stok = ?");
            $stmt->execute([$item['jumlah'], $stok['id_stok']]);

            // Jika stok menjadi <= 0, hapus record stok
            if (($stok['jumlah'] - $item['jumlah']) <= 0) {
                $stmt = $db->prepare("DELETE FROM stok_bahan WHERE id_stok = ?");
                $stmt->execute([$stok['id_stok']]);
            }
        }
    }

    // 3. Hapus detail pembelian
    $stmt = $db->prepare("DELETE FROM detail_pembelian WHERE id_pembelian = ?");
    $stmt->execute([$id_pembelian]);

    // 4. Hapus data pembelian
    $stmt = $db->prepare("DELETE FROM pembelian_bahan WHERE id_pembelian = ?");
    $stmt->execute([$id_pembelian]);

    $db->commit();

    // Catat aktivitas
    $stmt = $db->prepare("INSERT INTO log_aktivitas (id_admin, aktivitas, tabel_terkait, id_entitas) 
                         VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        'Menghapus pembelian bahan dengan ID ' . $id_pembelian,
        'pembelian_bahan',
        $id_pembelian
    ]);

    redirectWithMessage('../index.php', 'success', 'Pembelian berhasil dihapus.');
} catch (PDOException $e) {
    $db->rollBack();
    redirectWithMessage('../index.php', 'danger', 'Gagal menghapus pembelian: ' . $e->getMessage());
}
