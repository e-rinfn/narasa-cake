<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Tambah Jenis Kue';
$active_page = 'kue';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kue = $_POST['nama_kue'];
    $harga_jual = $_POST['harga_jual'];
    $deskripsi = $_POST['deskripsi'];
    $aktif = isset($_POST['aktif']) ? 1 : 0;

    try {
        $stmt = $db->prepare("INSERT INTO jenis_kue (nama_kue, deskripsi, aktif, harga_jual) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama_kue, $deskripsi, $aktif, $harga_jual]);

        redirectWithMessage('index.php', 'success', 'Jenis kue berhasil ditambahkan');
    } catch (PDOException $e) {
        redirectWithMessage('add.php', 'danger', 'Error: ' . $e->getMessage());
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
                                <li class="breadcrumb-item active" aria-current="page">Tambah</li>
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
                            <h3 class="mb-0">Tambah Jenis Kue</h3>
                        </div>
                    </div>

                    <div class="card-body">

                        <!-- Form Input -->
                        <form method="POST">
                            <div class="form-group">
                                <label>Nama Kue</label>
                                <input type="text" name="nama_kue" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>± Harga Jual Kue</label>
                                <input type="number" name="harga_jual" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="form-group form-check">
                                <input type="checkbox" name="aktif" class="form-check-input" id="aktif" checked>
                                <label class="form-check-label" for="aktif">Aktif</label>
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="index.php" class="btn btn-secondary">Kembali</a>
                        </form>
                    </div>
                </div>
            </div>
            <!-- [ Main Content ] end -->
        </div>
    </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>