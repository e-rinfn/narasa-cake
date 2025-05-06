<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Edit Poin';
$active_page = 'pelanggan';

$id = $_GET['id'] ?? null;

if (!$id) {
    redirectWithMessage('index.php', 'error', 'ID pelanggan tidak valid.');
}

// Ambil data pelanggan
$stmt = $db->prepare("SELECT * FROM pelanggan WHERE id_pelanggan = ?");
$stmt->execute([$id]);
$pelanggan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pelanggan) {
    redirectWithMessage('index.php', 'error', 'Pelanggan tidak ditemukan.');
}

// Proses update jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total_poin = $_POST['total_poin'];

    $update = $db->prepare("UPDATE pelanggan SET total_poin = ? WHERE id_pelanggan = ?");
    $update->execute([$total_poin, $id]);

    redirectWithMessage('index.php', 'success', 'Total poin berhasil diperbarui.');
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/sidebar.php'; ?>
<?php include '../../includes/navbar.php'; ?>


<div class="pc-container">
    <div class="pc-content">

        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10">Pelanggan</h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Pelanggan</a></li>
                            <li class="breadcrumb-item"><a href="javascript: void(0)">Edit Nabung Pelanggan</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->
        <div class="row">
            <!-- [ Main Content ] start -->
            <div class="card">
                <div class="card-header">
                    <h3>Edit Nabung Pelanggan</h3>
                </div>

                <div class="card-body">
                    <form method="POST">
                        <div class="form-group mb-3">
                            <label>Nama Pelanggan</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($pelanggan['nama_pelanggan']) ?>" readonly>
                        </div>

                        <div class="form-group mb-3">
                            <label>Total Nabung</label>
                            <input type="number" name="total_poin" class="form-control" value="<?= $pelanggan['total_poin'] ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="index.php" class="btn btn-secondary">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>