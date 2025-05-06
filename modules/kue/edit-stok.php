<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Edit Stok Kue';
$active_page = 'kue';

// Ambil data stok
$id_stok_kue = $_GET['id'] ?? 0;
$stmt = $db->prepare("SELECT * FROM stok_kue WHERE id_stok_kue = ?");
$stmt->execute([$id_stok_kue]);
$stok = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stok) {
    redirectWithMessage('index.php', 'danger', 'Data stok tidak ditemukan');
}

// Ambil data jenis kue
$stmt = $db->prepare("SELECT * FROM jenis_kue WHERE id_jenis_kue = ?");
$stmt->execute([$stok['id_jenis_kue']]);
$kue = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jumlah = (int)$_POST['jumlah'];
    $tanggal_produksi = $_POST['tanggal_produksi'];
    $tanggal_kadaluarsa = $_POST['tanggal_kadaluarsa'];

    try {
        $stmt = $db->prepare("UPDATE stok_kue 
                             SET jumlah = ?, tanggal_produksi = ?, tanggal_kadaluarsa = ?
                             WHERE id_stok_kue = ?");
        $stmt->execute([$jumlah, $tanggal_produksi, $tanggal_kadaluarsa, $id_stok_kue]);

        redirectWithMessage('stok.php?id=' . $stok['id_jenis_kue'], 'success', 'Stok berhasil diupdate');
    } catch (PDOException $e) {
        redirectWithMessage('edit_stok.php?id=' . $id_stok_kue, 'danger', 'Error: ' . $e->getMessage());
    }
}

include '../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Edit Stok Kue: <?= htmlspecialchars($kue['nama_kue']) ?></h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group">
                <label>Jumlah</label>
                <input type="number" name="jumlah" class="form-control"
                    value="<?= htmlspecialchars($stok['jumlah']) ?>" min="0" required>
            </div>

            <div class="form-group">
                <label>Tanggal Produksi</label>
                <input type="date" name="tanggal_produksi" class="form-control"
                    value="<?= htmlspecialchars($stok['tanggal_produksi']) ?>" required>
            </div>

            <div class="form-group">
                <label>Tanggal Kadaluarsa</label>
                <input type="date" name="tanggal_kadaluarsa" class="form-control"
                    value="<?= htmlspecialchars($stok['tanggal_kadaluarsa']) ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="stok.php?id=<?= $stok['id_jenis_kue'] ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>