<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Laporan Bahan Baku';
$active_page = 'laporan';

// Ambil data stok bahan
$stmt = $db->query("SELECT b.id_bahan, b.nama_bahan, 
                   k.nama_kategori, s.nama_satuan,
                   b.stok_minimal, b.harga_per_satuan,
                   COALESCE(SUM(sb.jumlah), 0) as stok_aktual
                   FROM bahan_baku b
                   JOIN kategori_bahan k ON b.id_kategori = k.id_kategori
                   JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
                   LEFT JOIN stok_bahan sb ON b.id_bahan = sb.id_bahan
                   GROUP BY b.id_bahan
                   ORDER BY b.nama_bahan");
$bahan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total nilai stok
$total_nilai = 0;
foreach ($bahan as $item) {
    $total_nilai += $item['stok_aktual'] * $item['harga_per_satuan'];
}

// Hitung jumlah bahan yang stoknya kurang
$total_bahan_kurang = 0;
foreach ($bahan as $item) {
    if ($item['stok_aktual'] < $item['stok_minimal']) {
        $total_bahan_kurang++;
    }
}

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
                                <li class="breadcrumb-item active" aria-current="page">Bahan</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->
            <div class="row">
                <div class="card">

                    <!-- [ Main Content ] start -->

                    <div class="card-header">
                        <h3>Laporan Stok Bahan Baku</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Jenis Bahan</h5>
                                        <h3 class="card-text"><?= count($bahan) ?> Jenis</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Bahan Kurang</h5>
                                        <h3 class="card-text"><?= $total_bahan_kurang ?> Jenis</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-dark">
                                    <tr class="text-center">
                                        <th>No</th>
                                        <th>Nama Bahan</th>
                                        <th>Kategori</th>
                                        <th>Stok</th>
                                        <th>Minimal</th>
                                        <th>Satuan</th>
                                        <th>Status Bahan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bahan as $i => $row):
                                        $status = $row['stok_aktual'] < $row['stok_minimal'] ? 'danger' : 'success';
                                        $status_text = $row['stok_aktual'] < $row['stok_minimal'] ? 'Kurang' : 'Aman';
                                    ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= $row['nama_bahan'] ?></td>
                                            <td><?= $row['nama_kategori'] ?></td>
                                            <td><?= $row['stok_aktual'] ?></td>
                                            <td><?= $row['stok_minimal'] ?></td>
                                            <td><?= $row['nama_satuan'] ?></td>
                                            <td>
                                                <span class="-<?= $status ?>"><?= $status_text ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            <a href="cetak_bahan.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-success">
                                <i class="fas fa-print"></i> Cetak Laporan
                            </a>
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