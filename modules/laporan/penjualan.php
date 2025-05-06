<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Laporan Penjualan';
$active_page = 'laporan';

// Default periode: bulan ini
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$id_pelanggan = $_GET['id_pelanggan'] ?? null;
$id_jenis_kue = $_GET['id_jenis_kue'] ?? null;

// Query untuk laporan penjualan
$sql = "SELECT p.id_penjualan, p.tanggal_penjualan, 
               pl.nama_pelanggan, 
               COUNT(d.id_detail_penjualan) as jumlah_item,
               SUM(d.jumlah) as total_kue,
               p.total_bayar
        FROM penjualan p
        LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
        JOIN detail_penjualan d ON p.id_penjualan = d.id_penjualan
        WHERE p.tanggal_penjualan BETWEEN :start_date AND :end_date
        AND p.status_pembayaran = 'lunas'";

$params = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

if ($id_pelanggan) {
    $sql .= " AND p.id_pelanggan = :id_pelanggan";
    $params[':id_pelanggan'] = $id_pelanggan;
}

if ($id_jenis_kue) {
    $sql .= " AND d.id_jenis_kue = :id_jenis_kue";
    $params[':id_jenis_kue'] = $id_jenis_kue;
}

$sql .= " GROUP BY p.id_penjualan ORDER BY p.tanggal_penjualan DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$penjualan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total penjualan
$total_penjualan = array_sum(array_column($penjualan, 'total_bayar'));

// Ambil data pelanggan untuk filter
$stmt = $db->query("SELECT * FROM pelanggan ORDER BY nama_pelanggan");
$pelanggan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data jenis kue untuk filter
$stmt = $db->query("SELECT * FROM jenis_kue ORDER BY nama_kue");
$jenis_kue = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                                <h5 class="m-b-10">Laporan</h5>
                            </div>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Laporan</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Penjualan</li>
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
                        <h3>Laporan Penjualan</h3>
                    </div>
                    <div class="row p-4 g-3 align-items-stretch">
                        <div class="col-md-6">
                            <div class="card bg-light h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Periode Laporan</h5>
                                    <p class="card-text">
                                        <?= tgl_indo($start_date) ?> s/d <?= tgl_indo($end_date) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Total Penjualan</h5>
                                    <h3 class="card-text"><?= rupiah($total_penjualan) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="GET" class="p-4">
                        <div class="row align-items-end">
                            <div class="col-md-2 mb-2">
                                <label for="start_date" class="form-label">Dari</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $start_date ?>">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label for="end_date" class="form-label">Sampai</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= $end_date ?>">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="id_pelanggan" class="form-label">Pelanggan</label>
                                <select id="id_pelanggan" name="id_pelanggan" class="form-control">
                                    <option value="">Semua Pelanggan</option>
                                    <?php foreach ($pelanggan as $p): ?>
                                        <option value="<?= $p['id_pelanggan'] ?>" <?= $id_pelanggan == $p['id_pelanggan'] ? 'selected' : '' ?>>
                                            <?= $p['nama_pelanggan'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="id_jenis_kue" class="form-label">Jenis Kue</label>
                                <select id="id_jenis_kue" name="id_jenis_kue" class="form-control">
                                    <option value="">Semua Kue</option>
                                    <?php foreach ($jenis_kue as $k): ?>
                                        <option value="<?= $k['id_jenis_kue'] ?>" <?= $id_jenis_kue == $k['id_jenis_kue'] ? 'selected' : '' ?>>
                                            <?= $k['nama_kue'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1 mb-2 text-end">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter "></i></button>
                            </div>
                            <div class="col-md-1 mb-2">
                                <a href="penjualan.php" class="btn btn-secondary w-100"><i class="fas fa-sync-alt "></i> </a>
                            </div>
                        </div>
                    </form>
                    <div class="card-body">


                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-dark">
                                    <tr class="text-center">
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Pelanggan</th>
                                        <th>Jumlah Item</th>
                                        <th>Total Kue</th>
                                        <th>Total Bayar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($penjualan as $i => $row): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($row['tanggal_penjualan'])) ?></td>
                                            <td><?= $row['nama_pelanggan'] ?: 'Umum' ?></td>
                                            <td><?= $row['jumlah_item'] ?></td>
                                            <td><?= $row['total_kue'] ?></td>
                                            <td><?= rupiah($row['total_bayar']) ?></td>
                                            <td class="text-center">
                                                <a href="../penjualan/invoice.php?id=<?= $row['id_penjualan'] ?>" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="fas fa-file-invoice"></i> Invoice
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <a href="cetak_penjualan.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-success">
                                <i class="fas fa-print"></i> Cetak Laporan
                            </a>
                        </div>
                    </div>

                    <!-- [ Main Content ] end -->

                </div>
            </div>
        </div>
    </div>
    <?php include '../../includes/footer.php'; ?>