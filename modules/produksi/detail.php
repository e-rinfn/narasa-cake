<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Detail Produksi';
$active_page = 'produksi';

if (!isset($_GET['id'])) {
    redirectWithMessage('../index.php', 'danger', 'ID produksi tidak valid');
}

$id_produksi = $_GET['id'];

// Ambil data produksi
$stmt = $db->prepare("SELECT p.*, r.nama_resep, r.versi, k.nama_kue, a.nama_lengkap as operator
                     FROM produksi p
                     JOIN resep_kue r ON p.id_resep = r.id_resep
                     JOIN jenis_kue k ON r.id_jenis_kue = k.id_jenis_kue
                     JOIN admin a ON p.id_admin = a.id_admin
                     WHERE p.id_produksi = ?");
$stmt->execute([$id_produksi]);
$produksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produksi) {
    redirectWithMessage('../index.php', 'danger', 'Data produksi tidak ditemukan');
}

// Ambil detail bahan yang digunakan
$stmt = $db->prepare("SELECT pb.*, b.nama_bahan, s.nama_satuan, sb.tanggal_kadaluarsa
                     FROM penggunaan_bahan pb
                     JOIN bahan_baku b ON pb.id_bahan = b.id_bahan
                     JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
                     JOIN stok_bahan sb ON pb.id_stok = sb.id_stok
                     WHERE pb.id_produksi = ?");
$stmt->execute([$id_produksi]);
$bahan_digunakan = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

            <div class="row">
                <!-- [ Main Content ] start -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Detail Produksi</h3>
                            <a href="index.php" class="btn btn-secondary">Kembali</a>

                        </div>
                    </div>

                    <div class="card-body">

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">Tanggal Produksi</th>
                                        <td><?= tgl_indo($produksi['tanggal_produksi']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Jenis Kue</th>
                                        <td><?= $produksi['nama_kue'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Deskripsi Kue</th>
                                        <td><?= $produksi['nama_resep'] ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">Jumlah Batch</th>
                                        <td><?= $produksi['jumlah_batch'] ?></td>
                                    </tr>
                                    <!-- <tr>
                                        <th>Estimasi Hasil Kue</th>
                                        <td><?= $produksi['total_kue'] ?></td>
                                    </tr> -->
                                    <tr>
                                        <th>Operator</th>
                                        <td><?= $produksi['operator'] ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <h5>Bahan Baku yang Digunakan</h5>
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Bahan</th>
                                            <th>Jumlah Digunakan</th>
                                            <th>Satuan</th>
                                            <!-- <th>Tanggal Kadaluarsa</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bahan_digunakan as $i => $bahan): ?>
                                            <tr>
                                                <td><?= $i + 1 ?></td>
                                                <td><?= $bahan['nama_bahan'] ?></td>
                                                <td><?= $bahan['jumlah_digunakan'] ?></td>
                                                <td><?= $bahan['nama_satuan'] ?></td>
                                                <!-- <td><?= tgl_indo($bahan['tanggal_kadaluarsa']) ?></td> -->
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>

                        <?php if (!empty($produksi['catatan'])): ?>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6>Catatan Produksi</h6>
                                        </div>
                                        <div class="card-body">
                                            <?= nl2br($produksi['catatan']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
                <!-- [ Main Content ] end -->
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>