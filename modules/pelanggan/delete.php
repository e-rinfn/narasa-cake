<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Hapus Pelanggan';
$active_page = 'pelanggan';

// Ambil ID pelanggan dari URL
$id_pelanggan = $_GET['id'] ?? 0;

// Cek apakah pelanggan ada
$stmt = $db->prepare("SELECT id_pelanggan FROM pelanggan WHERE id_pelanggan = ?");
$stmt->execute([$id_pelanggan]);
if (!$stmt->fetch()) {
    redirectWithMessage('index.php', 'danger', 'Pelanggan tidak ditemukan');
}

// Cek apakah pelanggan memiliki transaksi
$stmt = $db->prepare("SELECT id_penjualan FROM penjualan WHERE id_pelanggan = ? LIMIT 1");
$stmt->execute([$id_pelanggan]);
if ($stmt->fetch()) {
    redirectWithMessage('index.php', 'danger', 'Pelanggan tidak dapat dihapus karena memiliki riwayat transaksi');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $db->prepare("DELETE FROM pelanggan WHERE id_pelanggan = ?");
        $stmt->execute([$id_pelanggan]);

        redirectWithMessage('index.php', 'success', 'Pelanggan berhasil dihapus');
    } catch (Exception $e) {
        redirectWithMessage('index.php', 'danger', 'Error: ' . $e->getMessage());
    }
}

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

                <div class="card">
                    <div class="card-header">
                        <h3>Konfirmasi Hapus Pelanggan</h3>
                    </div>
                    <div class="card-body">
                        <p>Anda yakin ingin menghapus pelanggan ini?</p>
                        <form method="POST">
                            <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                            <a href="index.php" class="btn btn-secondary">Batal</a>
                        </form>
                    </div>
                </div>

                <!-- [ Main Content ] end -->

            </div>
        </div>
    </div>


    <?php include '../../includes/footer.php'; ?>