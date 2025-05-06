<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Detail Pembelian';
$active_page = 'pembelian';

if (!isset($_GET['id'])) {
    redirectWithMessage('index.php', 'danger', 'ID Pembelian tidak valid');
}

$id_pembelian = $_GET['id'];

// Ambil data pembelian
$stmt = $db->prepare("SELECT p.*, a.nama_lengkap 
                     FROM pembelian_bahan p
                     JOIN admin a ON p.id_admin = a.id_admin
                     WHERE p.id_pembelian = ?");
$stmt->execute([$id_pembelian]);
$pembelian = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pembelian) {
    redirectWithMessage('index.php', 'danger', 'Data pembelian tidak ditemukan');
}

// Ambil detail pembelian
$stmt = $db->prepare("SELECT d.*, b.nama_bahan, k.nama_kategori, s.nama_satuan 
                     FROM detail_pembelian d
                     JOIN bahan_baku b ON d.id_bahan = b.id_bahan
                     JOIN kategori_bahan k ON b.id_kategori = k.id_kategori
                     JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
                     WHERE d.id_pembelian = ?");
$stmt->execute([$id_pembelian]);
$detail_pembelian = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<!-- [Head] start -->

<!-- [Body] Start -->

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
                                <h5 class="m-b-10">Home</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../../dashboard/index.html">Home</a></li>
                                <li class="breadcrumb-item"><a href="javascript: void(0)">Dashboard</a></li>
                                <li class="breadcrumb-item" aria-current="page">Home</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->
            <div class="row">

                <!-- [ Main Content ] start -->

                <div class="card-header">
                    <h3>Detail Pembelian</h3>
                    <a href="index.php" class="btn btn-secondary float-right">Kembali</a>
                </div>
                <div class="card-body mt-3">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Informasi Pembelian</h5>
                                    <p class="card-text">
                                        <strong>No. Pembelian:</strong> <?= $pembelian['id_pembelian'] ?><br>
                                        <strong>Tanggal:</strong> <?= tgl_indo($pembelian['tanggal_pembelian']) ?><br>
                                        <strong>Supplier:</strong> <?= $pembelian['supplier'] ?><br>
                                        <strong>Admin:</strong> <?= $pembelian['nama_lengkap'] ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Pembayaran</h5>
                                    <p class="card-text">
                                        <strong>Status:</strong>
                                        <span class="badge badge-<?=
                                                                    $pembelian['status_pembayaran'] == 'lunas' ? 'success' : ($pembelian['status_pembayaran'] == 'pending' ? 'warning' : 'danger')
                                                                    ?>">
                                            <?= ucfirst($pembelian['status_pembayaran']) ?>
                                        </span><br>
                                        <strong>Metode:</strong> <?= ucfirst($pembelian['metode_pembayaran']) ?><br>
                                        <strong>Total:</strong> <?= rupiah($pembelian['total_harga']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5>Detail Barang</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Bahan</th>
                                    <th>Kategori</th>
                                    <th>Jumlah</th>
                                    <th>Harga Satuan</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail_pembelian as $i => $item): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= $item['nama_bahan'] ?></td>
                                        <td><?= $item['nama_kategori'] ?></td>
                                        <td><?= $item['jumlah'] ?> <?= $item['nama_satuan'] ?></td>
                                        <td><?= rupiah($item['harga_satuan']) ?></td>
                                        <td><?= rupiah($item['subtotal']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-right">Total Pembelian</th>
                                    <th><?= rupiah($pembelian['total_harga']) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if (!empty($pembelian['catatan'])): ?>
                        <div class="card bg-light mt-3">
                            <div class="card-header">
                                <h6>Catatan</h6>
                            </div>
                            <div class="card-body">
                                <p><?= nl2br($pembelian['catatan']) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>


    <?php include '../../includes/footer.php'; ?>