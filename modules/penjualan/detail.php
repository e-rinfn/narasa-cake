<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Detail Penjualan';
$active_page = 'penjualan';

if (!isset($_GET['id'])) {
    redirectWithMessage('../index.php', 'danger', 'ID penjualan tidak valid');
}

$id_penjualan = $_GET['id'];

// Ambil data penjualan
$stmt = $db->prepare("SELECT p.*, pl.nama_pelanggan, pl.id_kategori_pelanggan, a.nama_lengkap as nama_admin 
                     FROM penjualan p
                     LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
                     JOIN admin a ON p.id_admin = a.id_admin
                     WHERE p.id_penjualan = ?");
$stmt->execute([$id_penjualan]);
$penjualan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$penjualan) {
    redirectWithMessage('../index.php', 'danger', 'Data penjualan tidak ditemukan');
}

// Ambil detail penjualan
$stmt = $db->prepare("SELECT dp.*, k.nama_kue 
                     FROM detail_penjualan dp
                     JOIN jenis_kue k ON dp.id_jenis_kue = k.id_jenis_kue
                     WHERE dp.id_penjualan = ?");
$stmt->execute([$id_penjualan]);
$detail = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                                <h5 class="m-b-10">Transaksi</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Penjualan</a></li>
                                <li class="breadcrumb-item active">Detail</li>

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
                        <h3>Detail Penjualan</h3>
                        <div class="float-right mt-3">
                            <a href="invoice.php?id=<?= $id_penjualan ?>" class="btn btn-success" target="_blank">Cetak Invoice</a>
                            <a href="index.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </div>
                    <div class="card-body mt-3">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">No. Invoice</th>
                                        <td>INV-<?= str_pad($id_penjualan, 6, '0', STR_PAD_LEFT) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal</th>
                                        <td><?= date('d/m/Y H:i', strtotime($penjualan['tanggal_penjualan'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Pelanggan</th>
                                        <td><?= $penjualan['nama_pelanggan'] ?? 'Umum' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Kasir</th>
                                        <td><?= $penjualan['nama_admin'] ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Metode Pembayaran</th>
                                        <td><?= ucfirst($penjualan['metode_pembayaran']) ?></td>
                                    </tr>
                                    <tr class="table-active">
                                        <th>Total Harga</th>
                                        <td><?= rupiah($penjualan['total_harga']) ?></td>
                                    </tr>
                                    <!-- <tr>
                                        <th>Total Diskon</th>
                                        <td><?= rupiah($penjualan['total_diskon']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Poin Dipakai</th>
                                        <td><?= $penjualan['total_poin_dipakai'] ?> poin (<?= rupiah($penjualan['nilai_poin_dipakai']) ?>)</td>
                                    </tr>
                                    <tr class="table-active">
                                        <th>Total Bayar</th>
                                        <th><?= rupiah($penjualan['total_bayar']) ?></th>
                                    </tr> -->
                                </table>
                            </div>
                        </div>

                        <h5>Item Penjualan</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr class="text-center">
                                        <th>No</th>
                                        <th>Nama Kue</th>
                                        <th>Harga Satuan</th>
                                        <!-- <th>Diskon</th> -->
                                        <th>Nabung/Kue</th>
                                        <th>Jumlah</th>
                                        <th>Subtotal</th>
                                        <th>Poin Nabung</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_harga = 0;
                                    $total_poin = 0;
                                    foreach ($detail as $i => $row):
                                        $total_harga += $row['subtotal'];
                                        $total_poin += $row['poin_diberikan'];
                                    ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= $row['nama_kue'] ?></td>
                                            <td class="text-right"><?= rupiah($row['harga_satuan']) ?></td>
                                            <td class="text-right"><?= number_format($row['poin_diberikan'] / $row['jumlah'], 2) ?></td>
                                            <!-- <td class="text-right"><?= rupiah($row['diskon_satuan']) ?></td> -->
                                            <td class="text-center"><?= $row['jumlah'] ?></td>
                                            <td class="text-right"><?= rupiah($row['subtotal']) ?></td>
                                            <td class="text-center"><?= rupiah($row['poin_diberikan']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="5" class="text-center">Total</th>
                                        <th class="text-right"><?= rupiah($total_harga) ?></th>
                                        <th class="text-center"><?= rupiah($total_poin) ?></th>
                                    </tr>
                                </tfoot>
                            </table>

                        </div>

                        <?php if ($penjualan['catatan']): ?>
                            <div class="mt-3">
                                <h5>Catatan</h5>
                                <p><?= nl2br($penjualan['catatan']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>