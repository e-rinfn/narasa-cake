<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Laporan Produksi';
$active_page = 'laporan';

// Default periode: bulan ini
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$id_jenis_kue = $_GET['id_jenis_kue'] ?? null;

// Query untuk laporan produksi
$sql = "SELECT p.id_produksi, p.tanggal_produksi, 
               r.nama_resep, r.versi, 
               k.nama_kue,
               p.jumlah_batch, p.total_kue,
               a.nama_lengkap as operator
        FROM produksi p
        JOIN resep_kue r ON p.id_resep = r.id_resep
        JOIN jenis_kue k ON r.id_jenis_kue = k.id_jenis_kue
        JOIN admin a ON p.id_admin = a.id_admin
        WHERE p.tanggal_produksi BETWEEN :start_date AND :end_date";

$params = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

if ($id_jenis_kue) {
    $sql .= " AND r.id_jenis_kue = :id_jenis_kue";
    $params[':id_jenis_kue'] = $id_jenis_kue;
}

$sql .= " ORDER BY p.tanggal_produksi DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$produksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total produksi
$total_kue = array_sum(array_column($produksi, 'total_kue'));

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
                                <li class="breadcrumb-item active" aria-current="page">Produksi</li>
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
                        <h3>Laporan Produksi</h3>
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
                                    <h5 class="card-title">Total Produksi Kue</h5>
                                    <h3 class="card-text"><?= ($total_kue) . " pcs" ?></h3>
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
                            <div class="col-md-3"></div>
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
                                <a href="produksi.php" class="btn btn-secondary w-100"><i class="fas fa-sync-alt "></i> </a>
                            </div>
                        </div>
                    </form>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Jenis Kue</th>
                                        <th>Resep</th>
                                        <th>Batch</th>
                                        <th>Total Kue</th>
                                        <th>Operator</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($produksi as $i => $row): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= tgl_indo($row['tanggal_produksi']) ?></td>
                                            <td><?= $row['nama_kue'] ?></td>
                                            <td><?= $row['nama_resep'] ?> (v<?= $row['versi'] ?>)</td>
                                            <td><?= $row['jumlah_batch'] ?></td>
                                            <td><?= $row['total_kue'] ?></td>
                                            <td><?= $row['operator'] ?></td>
                                            <td>
                                                <a href="../produksi/detail.php?id=<?= $row['id_produksi'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            <a href="cetak_produksi.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-success">
                                <i class="fas fa-print"></i> Cetak Laporan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [ Main Content ] end -->

        </div>
    </div>
    </div>

    <?php include '../../includes/footer.php'; ?>