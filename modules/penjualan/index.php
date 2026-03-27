<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';
checkAuth();

// Set timezone ke Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

$page_title = 'Daftar Penjualan';
$active_page = 'penjualan';

// Ambil parameter pencarian
$search = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Handle input form
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $start_date = date('Y-m-d', strtotime($_GET['start_date']));
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $end_date = date('Y-m-d', strtotime($_GET['end_date']));
}

// Query data penjualan dengan filter dinamis
$sql = "SELECT p.*, pl.nama_pelanggan, a.nama_lengkap as nama_admin
        FROM penjualan p
        LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
        JOIN admin a ON p.id_admin = a.id_admin
        WHERE (DATE(p.tanggal_penjualan) BETWEEN :start_date AND :end_date)";

$params = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

// Tambahkan kondisi pencarian jika ada
if (!empty($search)) {
    $sql .= " AND (p.id_penjualan LIKE :search 
              OR pl.nama_pelanggan LIKE :search 
              OR a.nama_lengkap LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY p.tanggal_penjualan DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$penjualan = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- [Head content] -->
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

            <?php displayMessage(); ?>

            <div class="row">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Daftar Penjualan</h3>
                            <a href="add.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Penjualan Baru
                            </a>
                        </div>
                    </div>


                    <div class="card-body">
                        <form method="GET" class="mb-4">
                            <div class="row g-3 align-items-end">
                                <!-- Search Box -->
                                <div class="col-md-4">
                                    <label>Cari Invoice/Pelanggan</label>
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control"
                                            placeholder="Masukkan kata kunci..."
                                            value="<?= htmlspecialchars($search) ?>">
                                        <button class="btn btn-light border" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Filter Tanggal -->
                                <div class="col-md-2">
                                    <label>Dari Tanggal</label>
                                    <input type="date" name="start_date" class="form-control"
                                        value="<?= htmlspecialchars($start_date) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label>Sampai Tanggal</label>
                                    <input type="date" name="end_date" class="form-control"
                                        value="<?= htmlspecialchars($end_date) ?>">
                                </div>

                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-filter "></i> Filter
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <a href="index.php" class="btn btn-secondary btn-sm w-100">
                                        <i class="fas fa-sync-alt "></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr class="text-center">
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Invoice</th>
                                        <th>Pelanggan</th>
                                        <th>Total</th>
                                        <th>Kasir</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($penjualan)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada data penjualan ditemukan</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($penjualan as $i => $row): ?>
                                            <tr>
                                                <td class="text-center"><?= $i + 1 ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($row['tanggal_penjualan'])) ?> WIB</td>
                                                <td class="text-center">INV-<?= str_pad($row['id_penjualan'], 6, '0', STR_PAD_LEFT) ?></td>
                                                <td><?= htmlspecialchars($row['nama_pelanggan'] ?? 'Umum') ?></td>
                                                <td class="text-right"><?= rupiah($row['total_bayar']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_admin']) ?></td>
                                                <td class="text-center">
                                                    <a href="detail.php?id=<?= $row['id_penjualan'] ?>" class="btn btn-sm btn-info" title="Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <a href="cetak_struk.php?id=<?= $row['id_penjualan'] ?>" class="btn btn-sm btn-warning" title="Cetak escpos" target="_blank">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    <a href="bon.php?id=<?= $row['id_penjualan'] ?>" class="btn btn-sm btn-danger" title="Cetak Bon" target="_blank">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    <a href="invoice.php?id=<?= $row['id_penjualan'] ?>" class="btn btn-sm btn-success" title="Cetak Invoice" target="_blank">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>