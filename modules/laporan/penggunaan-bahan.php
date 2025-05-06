<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

$page_title = 'Laporan Penggunaan Bahan Baku';
$active_page = 'laporan';

// Default periode: bulan ini
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$id_bahan = $_GET['id_bahan'] ?? 'all';

// Ambil data bahan baku untuk dropdown
$stmt_bahan = $db->query("SELECT id_bahan, nama_bahan FROM bahan_baku ORDER BY nama_bahan");
$bahan_baku = $stmt_bahan->fetchAll(PDO::FETCH_ASSOC);

// Query untuk laporan
$query = "SELECT 
            p.id_produksi,
            p.tanggal_produksi,
            b.nama_bahan,
            s.nama_satuan,
            pb.jumlah_digunakan,
            (pb.jumlah_digunakan * sb.harga_per_satuan) as nilai
          FROM penggunaan_bahan pb
          JOIN bahan_baku b ON pb.id_bahan = b.id_bahan
          JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
          JOIN stok_bahan sb ON pb.id_stok = sb.id_stok
          JOIN produksi p ON pb.id_produksi = p.id_produksi
          WHERE p.tanggal_produksi BETWEEN :start_date AND :end_date";

if ($id_bahan != 'all') {
    $query .= " AND pb.id_bahan = :id_bahan";
}

$query .= " ORDER BY p.tanggal_produksi DESC, p.id_produksi, b.nama_bahan";

$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);

if ($id_bahan != 'all') {
    $stmt->bindParam(':id_bahan', $id_bahan);
}

$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total keseluruhan
$total_digunakan = 0;
$total_nilai = 0;

foreach ($data as $item) {
    $total_digunakan += $item['jumlah_digunakan'];
    $total_nilai += $item['nilai'];
}

// Query untuk grafik (data agregat)
$query_grafik = "SELECT 
            b.nama_bahan,
            s.nama_satuan,
            SUM(pb.jumlah_digunakan) as total_digunakan,
            SUM(pb.jumlah_digunakan * sb.harga_per_satuan) as total_nilai
          FROM penggunaan_bahan pb
          JOIN bahan_baku b ON pb.id_bahan = b.id_bahan
          JOIN satuan_bahan s ON b.id_satuan = s.id_satuan
          JOIN stok_bahan sb ON pb.id_stok = sb.id_stok
          JOIN produksi p ON pb.id_produksi = p.id_produksi
          WHERE p.tanggal_produksi BETWEEN :start_date AND :end_date";

if ($id_bahan != 'all') {
    $query_grafik .= " AND pb.id_bahan = :id_bahan";
}

$query_grafik .= " GROUP BY pb.id_bahan ORDER BY total_digunakan DESC";

$stmt_grafik = $db->prepare($query_grafik);
$stmt_grafik->bindParam(':start_date', $start_date);
$stmt_grafik->bindParam(':end_date', $end_date);

if ($id_bahan != 'all') {
    $stmt_grafik->bindParam(':id_bahan', $id_bahan);
}

$stmt_grafik->execute();
$grafik_data = $stmt_grafik->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>


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
                            <li class="breadcrumb-item active" aria-current="page">Penggunaan Bahan</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->
        <div class="row">
            <div class="card">
                <div class="card-header">
                    <h3>Laporan Penggunaan Bahan Baku</h3>


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
                                <label for="id_bahan" class="form-label">Jenis Kue</label>
                                <select id="id_bahan" name="id_bahan" class="form-control">
                                    <option value="all">Semua Bahan</option>
                                    <?php foreach ($bahan_baku as $bahan): ?>
                                        <option value="<?= $bahan['id_bahan'] ?>" <?= $id_bahan == $bahan['id_bahan'] ? 'selected' : '' ?>>
                                            <?= $bahan['nama_bahan'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-1 mb-2 text-end">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter "></i></button>
                            </div>
                            <div class="col-md-1 mb-2">
                                <a href="penggunaan-bahan.php" class="btn btn-secondary w-100"><i class="fas fa-sync-alt "></i> </a>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Periode Laporan</h5>
                                    <p class="card-text">
                                        <?= tgl_indo($start_date) ?> s/d <?= tgl_indo($end_date) ?>
                                    </p>
                                    <?php if ($id_bahan != 'all'): ?>
                                        <p class="card-text">
                                            <strong>Bahan Baku:</strong>
                                            <?= $bahan_baku[array_search($id_bahan, array_column($bahan_baku, 'id_bahan'))]['nama_bahan'] ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Total</h5>
                                    <p class="card-text">
                                        <strong>Jumlah Digunakan:</strong> <?= number_format($total_digunakan, 2) ?>
                                        <?= $data[0]['nama_satuan'] ?? '' ?>
                                    </p>
                                    <!-- <p class="card-text">
                                        <strong>Total Nilai:</strong> <?= rupiah($total_nilai) ?>
                                    </p> -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID Produksi</th>
                                    <th>Tanggal</th>
                                    <th>Nama Bahan</th>
                                    <th>Satuan</th>
                                    <th>Jumlah Digunakan</th>
                                    <!-- <th>Nilai</th> -->
                                </tr>
                            </thead>
                            <tbody>

                                <?php if (empty($data)): ?>

                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data penggunaan bahan baku</td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $current_id = null;
                                    foreach ($data as $row):
                                        if ($current_id !== $row['id_produksi']) {
                                            $current_id = $row['id_produksi'];
                                            $id_produksi = htmlspecialchars($row['id_produksi']);
                                            $tanggal = tgl_indo($row['tanggal_produksi']);
                                            echo "<tr class='table-info'>
                                                    <td colspan='6'>
                                                        <strong>ID Produksi:</strong> {$id_produksi} | 
                                                        <strong>Tanggal:</strong> {$tanggal}
                                                        <a href='../produksi/detail.php?id={$id_produksi}' target='_blank' class='btn btn-sm btn-link ms-5'>
                                                            <i class='fas fa-search'></i> Lihat Detail
                                                        </a>
                                                    </td>
                                                </tr>";
                                        }
                                    ?>
                                        <tr>
                                            <td><?= $row['id_produksi'] ?></td>
                                            <td><?= tgl_indo($row['tanggal_produksi']) ?></td>
                                            <td><?= $row['nama_bahan'] ?></td>
                                            <td><?= $row['nama_satuan'] ?></td>
                                            <td class="text-right"><?= number_format($row['jumlah_digunakan'], 2) ?></td>
                                            <!-- <td class="text-right"><?= rupiah($row['nilai']) ?></td> -->
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <a href="cetak_penggunaan_bahan.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-success">
                            <i class="fas fa-print"></i> Cetak Laporan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include '../../includes/footer.php'; ?>