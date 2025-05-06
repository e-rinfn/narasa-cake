<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Daftar Pembelian Bahan Baku';
$active_page = 'pembelian';

// Ambil data pembelian
$stmt = $db->query("SELECT p.*, a.nama_lengkap 
                   FROM pembelian_bahan p
                   JOIN admin a ON p.id_admin = a.id_admin
                   ORDER BY p.tanggal_pembelian DESC");
$pembelian = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <h3>Daftar Pembelian Bahan Baku</h3>
                    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Pembelian</a>
                </div>
                <div class="card-body mt-3">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Supplier</th>
                                <th>Total</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th>Admin</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pembelian as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= tgl_indo($row['tanggal_pembelian']) ?></td>
                                    <td><?= $row['supplier'] ?></td>
                                    <td><?= rupiah($row['total_harga']) ?></td>
                                    <td><?= ucfirst($row['metode_pembayaran']) ?></td>
                                    <td>
                                        <span class="badge badge-<?=
                                                                    $row['status_pembayaran'] == 'lunas' ? 'success' : ($row['status_pembayaran'] == 'pending' ? 'warning' : 'danger')
                                                                    ?>">
                                            <?= ucfirst($row['status_pembayaran']) ?>
                                        </span>
                                    </td>
                                    <td><?= $row['nama_lengkap'] ?></td>
                                    <td>
                                        <a href="detail.php?id=<?= $row['id_pembelian'] ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        <?php if ($_SESSION['role'] == 'superadmin' || $_SESSION['role'] == 'admin'): ?>
                                            <a href="add.php?edit=<?= $row['id_pembelian'] ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $row['id_pembelian'] ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Yakin ingin menghapus data ini?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>