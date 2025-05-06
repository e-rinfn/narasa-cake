<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Daftar Produksi Kue';
$active_page = 'produksi';

// Ambil data produksi
$stmt = $db->query("SELECT p.*, r.nama_resep, k.nama_kue 
                   FROM produksi p
                   JOIN resep_kue r ON p.id_resep = r.id_resep
                   JOIN jenis_kue k ON r.id_jenis_kue = k.id_jenis_kue
                   ORDER BY p.tanggal_produksi DESC");

$produksi = $stmt->fetchAll(PDO::FETCH_ASSOC);


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
                            <h3 class="mb-0">Daftar Produksi</h3>
                            <a href="add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Produksi Baru
                            </a>
                        </div>
                    </div>
                    <div class="card-body mt-3">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr class="text-center">
                                    <th>No</th>
                                    <th>Tanggal Produksi</th>
                                    <th>ID Produksi</th>
                                    <th>Jenis Kue</th>
                                    <th>Resep</th>
                                    <th>Jumlah Batch</th>
                                    <th>Total Kue</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produksi as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= tgl_indo($row['tanggal_produksi']) ?></td>
                                        <td><?= $row['id_produksi'] ?></td>
                                        <td><?= $row['nama_kue'] ?></td>
                                        <td><?= $row['nama_resep'] ?></td>
                                        <td><?= $row['jumlah_batch'] ?></td>
                                        <td><?= $row['total_kue'] ?></td>
                                        <td class="text-center">
                                            <a href="detail.php?id=<?= $row['id_produksi'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"> | Lihat Detail</i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- [ Main Content ] end -->
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>