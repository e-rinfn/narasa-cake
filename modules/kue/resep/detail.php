<?php
require_once '../../../config/config.php';
require_once '../../../includes/functions.php';
checkAuth();

$page_title = 'Detail Resep Kue';
$active_page = 'resep';

// Ambil ID resep dari URL
$id_resep = $_GET['id'] ?? 0;

// Ambil data resep utama
$stmt = $db->prepare("SELECT r.*, k.nama_kue 
                     FROM resep_kue r
                     JOIN jenis_kue k ON r.id_jenis_kue = k.id_jenis_kue
                     WHERE r.id_resep = ?");
$stmt->execute([$id_resep]);
$resep = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resep) {
    redirectWithMessage('../resep/index.php', 'danger', 'Resep tidak ditemukan');
}

// Ambil detail bahan resep
$stmt = $db->prepare("SELECT dr.*, b.nama_bahan, k.nama_kategori, s.nama_satuan, b.harga_per_satuan
                     FROM detail_resep dr
                     JOIN bahan_baku b ON dr.id_bahan = b.id_bahan
                     JOIN kategori_bahan k ON b.id_kategori = k.id_kategori
                     JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
                     WHERE dr.id_resep = ?
                     ORDER BY b.nama_bahan");
$stmt->execute([$id_resep]);
$bahan_resep = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung HPP per porsi
$total_hpp = 0;
foreach ($bahan_resep as $bahan) {
    $total_hpp += $bahan['jumlah'] * $bahan['harga_per_satuan'];
}
// $hpp_per_porsi = $total_hpp / $resep['porsi'];

include '../../../includes/header.php';
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

    <?php include '../../../includes/sidebar2.php'; ?>
    <?php include '../../../includes/navbar.php'; ?>

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
                                <li class="breadcrumb-item"><a href="#">Bahan Baku</a></li>
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
                        <h3>Detail Resep: <?= $resep['nama_kue'] ?></h3>
                        <div class="float-right">
                            <a href="edit.php?id=<?= $id_resep ?>" class="btn btn-warning">Edit</a>
                            <a href="../resep/index.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </div>
                    <div class="card-body mt-3">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="info-box">
                                    <h5>Informasi Resep</h5>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">Jenis Kue</th>
                                            <td><?= $resep['nama_kue'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Nama Resep</th>
                                            <td><?= $resep['nama_resep'] ?></td>
                                        </tr>
                                        <!-- <tr>
                                            <th>Versi</th>
                                            <td><?= $resep['versi'] ?></td>
                                        </tr> -->
                                        <!-- <tr>
                                            <th>Jumlah Porsi</th>
                                            <td><?= $resep['porsi'] ?> kue</td>
                                        </tr> -->
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                <span class="badge text-dark badge-<?= $resep['aktif'] ? 'success' : 'danger' ?>">
                                                    <?= $resep['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <!-- <tr>
                                            <th>HPP per Kue</th>
                                            <td><?= rupiah($hpp_per_porsi) ?></td>
                                        </tr> -->
                                        <!-- <tr>
                                            <th>Total HPP</th>
                                            <td><?= rupiah($total_hpp) ?> (untuk <?= $resep['porsi'] ?> kue)</td>
                                        </tr> -->
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box">
                                    <h5>Riwayat Produksi</h5>
                                    <?php
                                    // Ambil data produksi berdasarkan resep ini
                                    $stmt = $db->prepare("SELECT p.*, a.nama_lengkap as operator
                                        FROM produksi p
                                        JOIN admin a ON p.id_admin = a.id_admin
                                        WHERE p.id_resep = ?
                                        ORDER BY p.tanggal_produksi DESC
                                        LIMIT 5");
                                    $stmt->execute([$id_resep]);
                                    $produksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (count($produksi) > 0): ?>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>Batch</th>
                                                    <th>Total Kue</th>
                                                    <th>Operator</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($produksi as $p): ?>
                                                    <tr>
                                                        <td><?= tgl_indo($p['tanggal_produksi']) ?></td>
                                                        <td><?= $p['jumlah_batch'] ?></td>
                                                        <td><?= $p['total_kue'] ?></td>
                                                        <td><?= $p['operator'] ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <a href="../../produksi/index.php?id_resep=<?= $id_resep ?>" class="btn btn-sm btn-primary">Lihat Semua Produksi</a>
                                    <?php else: ?>
                                        <div class="alert alert-info">Belum ada riwayat produksi</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Komposisi Bahan </h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Bahan</th>
                                                    <th>Jumlah</th>
                                                    <!-- <th width="25%">Harga</th>
                                                    <th width="25%">Subtotal</th> -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bahan_resep as $bahan): ?>
                                                    <tr>
                                                        <td>
                                                            <?= $bahan['nama_bahan'] ?>
                                                            <?php if (!empty($bahan['catatan'])): ?>
                                                                <br><small class="text-muted"><?= $bahan['catatan'] ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= $bahan['jumlah'] ?> <?= $bahan['nama_satuan'] ?></td>
                                                        <!-- <td><?= rupiah($bahan['harga_per_satuan']) ?>/<?= $bahan['nama_satuan'] ?></td> -->
                                                        <!-- <td><?= rupiah($bahan['jumlah'] * $bahan['harga_per_satuan']) ?></td> -->
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- [ Main Content ] end -->
            </div>
        </div>
    </div>

    <?php include '../../../includes/footer.php'; ?>
</body>

</html>