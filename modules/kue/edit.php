<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Edit Jenis Kue';
$active_page = 'kue';

if (!isset($_GET['id'])) {
    redirectWithMessage('index.php', 'danger', 'ID jenis kue tidak valid');
}

$id_jenis_kue = $_GET['id'];

// Ambil data jenis kue
$stmt = $db->prepare("SELECT * FROM jenis_kue WHERE id_jenis_kue = ?");
$stmt->execute([$id_jenis_kue]);
$kue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kue) {
    redirectWithMessage('index.php', 'danger', 'Jenis kue tidak ditemukan');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kue = $_POST['nama_kue'];
    $harga_jual = $_POST['harga_jual'];
    $deskripsi = $_POST['deskripsi'];
    $aktif = isset($_POST['aktif']) ? 1 : 0;

    try {
        $stmt = $db->prepare("UPDATE jenis_kue 
                             SET nama_kue = ?, 
                                 deskripsi = ?, 
                                 aktif = ?, 
                                 harga_jual = ?,
                                 diupdate_pada = NOW() 
                             WHERE id_jenis_kue = ?");
        $stmt->execute([
            $nama_kue,
            $deskripsi,
            $aktif,
            $harga_jual,
            $id_jenis_kue
        ]);

        // Log aktivitas
        $aktivitas = "Update jenis kue: $nama_kue (ID: $id_jenis_kue)";
        $stmt = $db->prepare("INSERT INTO log_aktivitas (id_admin, aktivitas, tabel_terkait, id_entitas) 
                             VALUES (?, ?, 'jenis_kue', ?)");
        $stmt->execute([$_SESSION['user_id'], $aktivitas, $id_jenis_kue]);

        redirectWithMessage('index.php', 'success', 'Jenis kue berhasil diperbarui');
    } catch (PDOException $e) {
        redirectWithMessage('edit.php?id=' . $id_jenis_kue, 'danger', 'Error: ' . $e->getMessage());
    }
}

include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title><?= $page_title ?></title>
</head>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->

    <?php include '../../includes/sidebar.php'; ?>
    <?php include '../../includes/navbar.php'; ?>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Manajemen</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Jenis Kue</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Ubah</li>
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
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Edit Bahan Baku</h3>
                        </div>
                    </div>

                    <div class="card-body">

                        <!-- Form Input -->
                        <form method="POST">
                            <div class="form-group">
                                <label>Nama Kue</label>
                                <input type="text" name="nama_kue" class="form-control" value="<?= $kue['nama_kue'] ?>" required>
                            </div>

                            <div class="form-group">
                                <label>± Harga Jual Kue</label>
                                <input type="number" name="harga_jual" class="form-control" value="<?= $kue['harga_jual'] ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="3"><?= $kue['deskripsi'] ?></textarea>
                            </div>

                            <div class="form-group form-check">
                                <input type="checkbox" name="aktif" class="form-check-input" id="aktif" <?= $kue['aktif'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="aktif">Aktif</label>
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <a href="index.php" class="btn btn-secondary">Kembali</a>
                        </form>

                    </div>
                </div>
                <!-- [ Main Content ] end -->
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>